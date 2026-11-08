<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Shift;
use Illuminate\Http\Request;

class SupplierController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $page = "Supplier";
        $data = Supplier::where('kode_owner','=',$this->getThisUser()->id_upline)->latest()->get();
        $link_tambah = route('supplier.create');
        $data = view('admin.tables.supplier_table', compact('data'))->render();
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
