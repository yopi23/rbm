<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangRusak;
use App\Models\DetailBarangPenjualan;
use App\Models\DetailBarangPesanan;
use App\Models\Handphone;
use App\Models\KategoriHandphone;
use App\Models\RestokBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Milon\Barcode\DNS1D;
use Milon\Barcode\Facades\DNS1DFacade;

class HandphoneController extends Controller
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
    //View Functions
    public function view_produk(Request $request){
        $page = "Data Barang";
        $link_tambah = route('create_produk');
        $thead = '<th width="5%">No</th>
                    <th>Barcode</th>
                    <th>Image</th>
                    <th>Kode Barang</th>
                    <th>Nama</th>
                    <th>Harga Beli</th>
                    <th>Harga Jual</th>
                    <th>Kondisi</th>
                    <th>Stok</th>
                    <th>Aksi</th>';
        $data_barang = Handphone::where('kode_owner','=',$this->getThisUser()->id_upline)->latest()->get();
        $tbody = '';
        $no = 1;
        
        foreach ($data_barang as $item) {
            $edit = Request::create(route('edit_produk',$item->id));
            $delete = Request::create(route('delete_produk',$item->id));
            $foto = '<img src="'.asset('public/img/no_image.png').'" width="100%" height="100%" class="img" id="view-img">';
            if($item->foto_barang != '-'){
               $foto =  '<img src="'.asset('public/uploads/'.$item->foto_barang).'" class="img" width="100%" height="100%">';
            }
            $qr = DNS1DFacade::getBarcodeHTML($item->kode_barang, "C39" ,1,100);
            $tbody .= '<tr>
                            <td>'.$no++.'</td>
                            <td>'.$qr.'</td>
                            <td>'.$foto.'</td>
                            <td>'.$item->kode_barang.'</td>
                            <td>'.$item->nama_barang.'</td>
                            <td>Rp.'.number_format($item->harga_beli_barang).',-</td>
                            <td>Rp.'.number_format($item->harga_jual_barang).',-</td>
                            <td>'.$item->kondisi_barang.'</td>
                            <td>'.$item->stok_barang.'</td>
                            <td>
                                <form action="'.$delete->url().'" onsubmit="'."return confirm('Apakah Anda yakin ?')".'" method="POST">
                                    '.$this->getHiddenItemForm('DELETE').'
                                    <a href="'.$edit->url().'" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                    <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                                </form>
                            </tr>';
        }
        $data = $this->getTable($thead,$tbody);
        return view('admin.layout.card_layout',compact(['page','link_tambah','data']));
    }
    
    public function view_kategori(Request $request){
        $page = "Data Kategori Produk";
        $link_tambah = route('create_kategori_produk');
        $thead = '<th width="5%">No</th><th width="15%">Image</th><th>Nama</th><th>Aksi</th>';
        $kategori = KategoriHandphone::where('kode_owner','=',$this->getThisUser()->id_upline)->latest()->get();
        $tbody = '';
        $no = 1;
        
        foreach($kategori as $item){
            $edit = Request::create(route('EditKategoriProduk',$item->id));
            $delete = Request::create(route('DeleteKategoriProduk',$item->id));
            $foto = '<img src="'.asset('public/img/no_image.png').'" width="100%" height="100%" class="img" id="view-img">';
            if($item->foto_kategori != '-'){
               $foto =  '<img src="'.asset('public/uploads/'.$item->foto_kategori).'" class="img" width="100%" height="100%">';
            }
            $tbody .= '<tr>
                            <td>'.$no++.'</td>
                            <td>'.$foto.'</td>
                            <td>'.$item->nama_kategori.'</td>
                            <td>
                                <form action="'.$delete->url().'" onsubmit="'."return confirm('Apakah Anda yakin ?')".'" method="POST">
                                    '.$this->getHiddenItemForm('DELETE').'
                                    <a href="'.$edit->url().'" class="btn btn-warning"><i class="fas fa-edit"></i> Edit</a>
                                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Hapus</button>
                                </form>
                            </tr>';
        }
        $data = $this->getTable($thead,$tbody);
        return view('admin.layout.card_layout',compact(['page','link_tambah','data']));
    }
   

    // Create Functions
    public function create_produk(Request $request){
        $page = "Tambah Produk";
        $kategori = KategoriHandphone::where('kode_owner','=',$this->getThisUser()->id_upline)->latest()->get();
        return view('admin.forms.produk',compact(['page','kategori']));
    }
    public function create_kategori_produk(Request $request){
        $page = "Tambah Kategori Produk";
        return view('admin.forms.kategori_produk',compact(['page']));
    }

    // Store Functions
    public function store_produk(Request $request){
        $validate = $request->validate([
            'nama_barang' => ['required'],
            'kondisi_barang' => ['required'],
            'harga_beli_barang' => ['required'],
            'harga_jual_barang' => ['required'],
        ]);
        if($validate){
            $file = $request->file('foto_barang');
            $foto = $file != null ? date('Ymdhis').$file->getClientOriginalName() : '-';
            if($file != null){
                $file->move('public/uploads/',$foto);
            }
            $count = Handphone::where('kode_owner','=',$this->getThisUser()->id_upline)->latest()->get()->count();
            $kode_barang = 'BG'.date('Ymdhis').$count;
            $create = Handphone::create([
                'kode_barang' => $kode_barang,
                'kode_kategori' => $request->kode_kategori,
                'foto_barang' => $foto,
                'nama_barang' => $request->nama_barang,
                'desc_barang' => $request->desc_barang != null ? $request->desc_barang : '-',
                'merk_barang' => $request->merk_barang,
                'stok_barang' => $request->stok_barang,
                'kondisi_barang' => $request->kondisi_barang,
                'harga_beli_barang' => $request->harga_beli_barang,
                'harga_jual_barang' => $request->harga_jual_barang,
                'status_barang' => $request->status_barang,
                'kode_owner' => $this->getThisUser()->id_upline
            ]);
            if($create){
                return redirect()->route('produk')
                ->with([
                    'success' => 'Produk Berhasil Ditambahkan'
                ]);
            }
            return redirect()->back()->with('error',"Oops, Something Went Wrong"); 
        }else{
            return redirect()->back()->with('error',"Validating Error, Please Fill Required Field"); 
        }
    }
    public function store_kategori_produk(Request $request){
        $validate = $request->validate([
            'nama_kategori' => ['required'],
        ]);
        if($validate){
            $file = $request->file('foto_kategori');
            $foto = $file != null ? date('Ymdhis').$file->getClientOriginalName() : '-';
            if($file != null){
                $file->move('public/uploads/',$foto);
            }
            $create = KategoriHandphone::create([
                'foto_kategori' => $foto,
                'nama_kategori' => $request->nama_kategori,
                'kode_owner' => $this->getThisUser()->id_upline
            ]);
            if($create){
                return redirect()->route('kategori_produk')
                ->with([
                    'success' => 'Kategori Produk Berhasil Ditambahkan'
                ]);
            }
            return redirect()->back()->with('error',"Oops, Something Went Wrong"); 
        }else{
            return redirect()->back()->with('error',"Validating Error, Please Fill Required Field"); 
        }
    }


    // Edit Functions
    public function edit_produk(Request $request,$id){
        $page = "Edit Produk";
        $data = Handphone::findOrFail($id);
        $kategori = KategoriHandphone::where('kode_owner','=',$this->getThisUser()->id_upline)->
        latest()->get();
        return view('admin.forms.produk',compact(['page','data','kategori']));
    }
    public function edit_kategori_produk(Request $request,$id){
        $page = "Edit Kategori Produk";
        $data = KategoriHandphone::findOrFail($id);
        return view('admin.forms.kategori_produk',compact(['page','data']));
    }

    // Update Functions
    public function update_produk(Request $request,$id){
        $validate = $request->validate([
            'nama_barang' => ['required'],
            'kondisi_barang' => ['required'],
            'harga_beli_barang' => ['required'],
            'harga_jual_barang' => ['required'],
        ]);
        if($validate){
            $update = Handphone::findOrFail($id);
            $file = $request->file('foto_barang');
            $foto = $file != null ? date('Ymdhis').$file->getClientOriginalName() : $update->foto_barang;
            if($file != null){
                $file->move('public/uploads/',$foto);
            }
            $update->update([
                'kode_kategori' => $request->kode_kategori,
                'foto_barang' => $foto,
                'nama_barang' => $request->nama_barang,
                'desc_barang' => $request->desc_barang != null ? $request->desc_barang : '-',
                'merk_barang' => $request->merk_barang,
                'kondisi_barang' => $request->kondisi_barang,
                'harga_beli_barang' => $request->harga_beli_barang,
                'harga_jual_barang' => $request->harga_jual_barang,
                'status_barang' => $request->status_barang,
            ]);
            if($update){
                return redirect()->route('produk')
                ->with([
                    'success' => 'Produk Berhasil DiEdit'
                ]);
            }
            return redirect()->back()->with('error',"Oops, Something Went Wrong"); 
        }else{
            return redirect()->back()->with('error',"Validating Error, Please Fill Required Field"); 
        }
    }
    public function update_kategori_produk(Request $request,$id){
        $validate = $request->validate([
            'nama_kategori' => ['required'],
        ]);
        if($validate){
            $data_kategori = KategoriHandphone::findOrFail($id);
            $file = $request->file('foto_kategori');
            $foto = $file != null ? date('Ymdhis').$file->getClientOriginalName() : $data_kategori->foto_kategori;
            if($file != null){
                $file->move('public/uploads/',$foto);
            }
            $data_kategori->update([
                'foto_kategori' => $foto,
                'nama_kategori' => $request->nama_kategori
            ]);
            if($data_kategori){
                return redirect()->route('kategori_produk')
                ->with([
                    'success' => 'Kategori Produk Berhasil DiUpdate'
                ]);
            }
            return redirect()->back()->with('error',"Oops, Something Went Wrong"); 
        }else{
            return redirect()->back()->with('error',"Validating Error, Please Fill Required Field"); 
        }
    }

    // Delete Functions
    public function delete_produk($id){
        $data = Handphone::findOrFail($id);
        if($data->foto_barang != '-'){
            File::delete(public_path('uploads/'.$data->foto_barang));
        }
        $data->delete();
        if($data){
            return redirect()->route('produk')
            ->with([
                'success' => 'Produk Berhasil Dihapus'
            ]);
        }
        return redirect()->route('produk')->with('error',"Oops, Something Went Wrong");
    }
    public function delete_kategori_produk($id){
        $data = KategoriHandphone::findOrFail($id);
        if($data->foto_kategori != '-'){
            File::delete(public_path('uploads/'.$data->foto_kategori));
        }
        $data->delete();
        if($data){
            return redirect()->route('kategori_produk')
            ->with([
                'success' => 'Kategori Produk Berhasil Dihapus'
            ]);
        }
        return redirect()->route('kategori_produk')->with('error',"Oops, Something Went Wrong");
    }

    public function view_stok(Request $request){
        $page = "Stok Barang";
        $data_barang = Handphone::where('kode_owner','=',$this->getThisUser()->id_upline)->
        latest()->get();
        $view_barang = DetailBarangPenjualan::join('penjualans','detail_barang_penjualans.kode_penjualan','=','penjualans.id')->where([['penjualans.status_penjualan','=','1']])->get();
        $barang_rusak = BarangRusak::join('handphones','barang_rusaks.kode_barang','=','handphones.id')->where('barang_rusaks.kode_owner','=',$this->getThisUser()->id_upline)->get(['barang_rusaks.*','handphones.*','barang_rusaks.id as id_rusak']);
        $restok_barang = RestokBarang::join('handphones','restok_barangs.kode_barang','=','handphones.id')->where('restok_barangs.kode_owner','=',$this->getThisUser()->id_upline)->get(['restok_barangs.*','handphones.*','restok_barangs.id as id_restok']);
        $content = view('admin.page.stok_produk',compact(['data_barang','barang_rusak','restok_barang','view_barang']));
        return view('admin.layout.blank_page',compact(['page','content']));
    }

    // Restok Barang

    public function create_restok(Request $request){
        $page = "Restok Barang";
        $barang = Handphone::where('kode_owner','=',$this->getThisUser()->id_upline)->latest()->get();
        return view('admin.forms.restok',compact(['page','barang']));
    }
    public function edit_restok(Request $request,$id){
        $page = "Restok Barang";
        $data = RestokBarang::findOrFail($id);
        $barang = Handphone::where('kode_owner','=',$this->getThisUser()->id_upline)->latest()->get();
        return view('admin.forms.restok',compact(['page','barang','data']));
    }
    public function store_restok(Request $request){
        $data_restok = RestokBarang::where('kode_owner','=',$this->getThisUser()->id_upline)->latest()->get();
        $kode_restok = 'RB'.date('Ymd').$data_restok->count();
        $create = RestokBarang::create([
            'kode_restok' => $kode_restok,
            'tgl_restok' => $request->tgl_restok,
            'kode_barang' => $request->kode_barang,
            'jumlah_restok' => $request->jumlah_restok,
            'status_restok' => $request->status_restok,
            'catatan_restok' => $request->catatan_restok != null ? $request->catatan_restok : '-',
            'user_input' => auth()->user()->id,
            'kode_owner' => $this->getThisUser()->id_upline
        ]);
        if($create){
            if($request->status_restok == 'Success'){
                $update = Handphone::findOrFail($request->kode_barang);
                if($update != null){
                    $stok_awal = $update->stok_barang;
                    $stok_baru = $stok_awal + $request->jumlah_restok;
                    $update->update([
                        'stok_barang' => $stok_baru
                    ]);
                }
            }
            return redirect()->route('stok_produk')
            ->with([
                'success' => 'Restok Berhasil'
            ]);
        }
        return redirect()->route('stok_produk')->with('error',"Oops, Something Went Wrong");
    }
    public function update_restok(Request $request,$id){
        $update = RestokBarang::findOrFail($id);
        $update->update([
            'tgl_restok' => $request->tgl_restok,
            'jumlah_restok' => $request->jumlah_restok,
            'status_restok' => $request->status_restok,
            'catatan_restok' => $request->catatan_restok != null ? $request->catatan_restok : '-',
            'user_input' => auth()->user()->id
        ]);
        if($update){
            if($request->status_restok == 'Success'){
                $updates = Handphone::findOrFail($update->kode_barang);
                if($updates != null){
                    $stok_awal = $updates->stok_barang;
                    $stok_baru = $stok_awal + $request->jumlah_restok;
                    $updates->update([
                        'stok_barang' => $stok_baru
                    ]);
                    return redirect()->route('stok_produk')
                    ->with([
                        'success' => 'Restok Berhasil'
                    ]);
                }
            }
            return redirect()->route('stok_produk')
            ->with([
                'success' => 'Restok Berhasil Diedit'
            ]);
        }
        return redirect()->route('stok_produk')->with('error',"Oops, Something Went Wrong");
    }
    public function delete_restok(Request $request,$id){
        $data = RestokBarang::findOrFail($id);
        $data->delete();
        if($data){
            return redirect()->route('stok_produk')
            ->with([
                'success' => 'Restok Berhasil Dihapus'
            ]);
        }
        return redirect()->route('stok_produk')->with('error',"Oops, Something Went Wrong");
    }
    // Barang Rusak

    public function create_barang_rusak(Request $request){
        $page = "Tambah Barang Rusak";
        $barang = Handphone::where('kode_owner','=',$this->getThisUser()->id_upline)->latest()->get();
        return view('admin.forms.barang_rusak',compact(['page','barang']));
    }
    public function edit_barang_rusak(Request $request,$id){
        $page = "Edit Barang Rusak";
        $data = BarangRusak::findOrFail($id);
        $barang = Handphone::where('kode_owner','=',$this->getThisUser()->id_upline)->latest()->get();
        return view('admin.forms.barang_rusak',compact(['page','barang','data']));
    }
    public function store_barang_rusak(Request $request){
        $data_barang = Handphone::findOrFail($request->kode_barang);
        if($data_barang->stok_barang < $request->jumlah_rusak){
            return redirect()->back()
            ->with([
                'error' => 'Jumlah Barang Yang Rusak Tidak Boleh Melebihi Stok Barang'
            ]);
        }
            $create = BarangRusak::create([
                'tgl_rusak_barang' => $request->tgl_rusak_barang,
                'kode_barang' => $request->kode_barang,
                'jumlah_rusak' => $request->jumlah_rusak,
                'catatan_rusak' => $request->catatan_rusak != null ? $request->catatan_rusak : '-',
                'user_input' => auth()->user()->id,
                'kode_owner' => $this->getThisUser()->id_upline
            ]);
            if($create){
                $stok_awal = $data_barang->stok_barang;
                $stok_baru = $stok_awal - $request->jumlah_rusak;
                $data_barang->update([
                    'stok_barang' => $stok_baru,
                ]);
                return redirect()->route('stok_produk')
                ->with([
                    'success' => 'Barang Rusak Berhasil DiTambah'
                ]);
            }
            return redirect()->route('stok_produk')->with('error',"Oops, Something Went Wrong");
    }
    public function update_barang_rusak(Request $request,$id){
        
        $update = BarangRusak::findOrFail($id);
        $data_barang = Handphone::findOrFail($update->kode_barang);
        if($data_barang != null){
            $stok_awal = $data_barang->stok_barang + $update->jumlah_rusak;
            if($request->jumlah_rusak <= $stok_awal){
                $stok_baru = $stok_awal - $request->jumlah_rusak;
                $data_barang->update([
                    'stok_barang' => $stok_baru
                ]);
                if($data_barang){
                    $update->update([
                        'tgl_rusak_barang' => $request->tgl_rusak_barang,
                        'jumlah_rusak' => $request->jumlah_rusak,
                        'catatan_rusak' => $request->catatan_rusak != null ? $request->catatan_rusak : '-',
                        'user_input' => auth()->user()->id,
                    ]);
                    return redirect()->route('stok_produk')
                    ->with([
                        'success' => 'Barang Rusak Berhasil Diedit'
                    ]);
                }
                return redirect()->route('stok_produk')->with('error',"Oops, Something Went Wrong");
            }else{
                return redirect()->back()
                ->with([
                    'error' => 'Jumlah Barang Yang Rusak Tidak Boleh Melebihi Stok Barang'
                ]);
            }
            
            
        }
        
        
    }
    public function delete_barang_rusak(Request $request,$id){
        
    }
}
