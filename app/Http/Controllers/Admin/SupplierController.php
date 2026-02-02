<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Shift;
use Illuminate\Http\Request;

class SupplierController extends Controller
{

    public function getTable($thead = '<th>No</th><th>Aksi</th>',$tbody = ''){
        $result = '<div class="table-responsive"><table class="table" id="dataTable">';
        $result .= '<thead>'.$thead.'</thead>';
        $result .= '<tbody>'.$tbody.'</body>';
        $result .= '</table></div>';
        return $result;
    }
    public function getHiddenItemForm($method = 'POST'){
        $result = ''.csrf_field().'';
        $result .= ''.method_field($method).'';
        return $result;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $page = "Supplier";
        $thead = '<th width="5%">No</th>
        <th>Nama</th>
        <th>Alamat</th>
        <th>No Telp</th>
        <th>Aksi</th>';
        $data = Supplier::where('kode_owner','=',$this->getThisUser()->id_upline)->latest()->get();
        $tbody = '';
        $no = 1;
        foreach($data as $item){
            $edit = Request::create(route('supplier.edit',$item->id));
            $delete = Request::create(route('supplier.destroy',$item->id));
            $tbody .= '<tr><td>'.$no++.'</td>
                        <td>'.$item->nama_supplier.'</td>
                        <td>'.$item->alamat_supplier.'</td>
                        <td>'.$item->no_telp_supplier.'</td>
                        <th><form action="'.$delete->url().'" onsubmit="'."return confirm('Apakah Anda yakin ?')".'" method="POST">
                                '.$this->getHiddenItemForm('DELETE').'
                                <a href="'.$edit->url().'" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </form>
                    </th>';
        }
        $link_tambah = route('supplier.create');
        $data = $this->getTable($thead,$tbody);
        return view('admin.layout.card_layout',compact(['page','data','link_tambah']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $page = "Tambah Supplier";
        return view('admin.forms.supplier',compact(['page']));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $validate = $this->validate($request,[
            'nama_supplier' => ['required'],
            'alamat_supplier' => ['required'],
            'no_telp_supplier' => ['required'],
        ]);
        if($validate){
            $create = Supplier::create([
                'kode_owner' => $request->kode_owner,
                'nama_supplier' => $request->nama_supplier,
                'alamat_supplier' =>$request->alamat_supplier,
                'no_telp_supplier' => $request->no_telp_supplier,
            ]);
            if($create){
                    return redirect()->route('supplier.index')
                    ->with([
                        'success' => 'Supplier Berhasil Ditambahkan'
                    ]);
                return redirect()->back()->with('error',"Oops, Something Went Wrong"); 
            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        $page = "Edit Supplier";
        $data = Supplier::findOrFail($id);
        return view('admin.forms.supplier',compact(['page','data']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $validate = $this->validate($request,[
            'nama_supplier' => ['required'],
            'alamat_supplier' => ['required'],
            'no_telp_supplier' => ['required'],
        ]);
        if($validate){
            $update = Supplier::findOrFail($id);
            $update->update([
                'nama_supplier' => $request->nama_supplier,
                'alamat_supplier' =>$request->alamat_supplier,
                'no_telp_supplier' => $request->no_telp_supplier,
            ]);
            if($update){
                    return redirect()->route('supplier.index')
                    ->with([
                        'success' => 'Supplier Berhasil Di Edit'
                    ]);
                return redirect()->back()->with('error',"Oops, Something Went Wrong"); 
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // Get Active Shift
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return redirect()->back()->with('error', 'Shift belum dibuka. Silakan buka shift terlebih dahulu.');
        }

        //
        $data = Supplier::findOrFail($id);
        $data->delete();
        if($data){
            return redirect()->route('supplier.index')
            ->with([
                'success' => 'Supplier Berhasil Di Hapus'
            ]);
        }
        return redirect()->back()->with('error',"Oops, Something Went Wrong"); 
    }
}
