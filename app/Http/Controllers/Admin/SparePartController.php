<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DetailPartServices;
use App\Models\DetailSparepartPenjualan;
use App\Models\KategoriSparepart;
use App\Models\RestokSparepart;
use App\Models\ReturSparepart;
use App\Models\Sparepart;
use App\Models\Hutang;
use App\Models\HargaKhusus;
use App\Models\SparepartRusak;
use App\Models\Supplier;
use App\Models\Order;
use App\Models\DetailOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Milon\Barcode\Facades\DNS1DFacade;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\SubKategoriSparepart;
use Illuminate\Support\Facades\DB;

class SparePartController extends Controller
{


    public function getTable($thead = '<th>No</th><th>Aksi</th>', $tbody = '')
    {
        $result = '<div class="table-responsive"><table class="table" id="dataEditTable">';
        $result .= '<thead>' . $thead . '</thead>';
        $result .= '<tbody>' . $tbody . '</body>';
        $result .= '</table></div>';
        return $result;
    }
    public function getHiddenItemForm($method = 'POST')
    {
        $result = '' . csrf_field() . '';
        $result .= '' . method_field($method) . '';
        return $result;
    }
    //Function View
    public function view_sparepart(Request $request)
    {
        $page = "Data Sparepart";
        $link_tambah = route('create_sparepart');

        // Definisikan header tabel dengan kolom checkbox
        $thead = '<th width="1%"><input type="checkbox" id="select-all-checkbox"></th>
                    <th width="5%">No</th>
                    <th>Kode</th>
                    <th>Nama Sparepart</th>
                    <th>Sub Kategori</th>
                    <th>Harga Beli</th>
                    <th>Harga Jual</th>
                    <th>Stok</th>
                    <th>Aksi</th>';

        $sparepart_data = Sparepart::with(['subKategori'])->where('kode_owner', '=', $this->getThisUser()->id_upline)->latest('updated_at')->get();

        // Buat body tabel dengan baris checkbox
        $tbody = '';
        $no = 1;
        foreach ($sparepart_data as $item) {
            $edit_route = route('EditSparepart', $item->id);
            $delete_route = route('DeleteSparepart', $item->id);
            $sub_kategori_name = $item->subKategori->nama_sub_kategori ?? '-';
            $tbody .= '<tr>
                            <td><input type="checkbox" class="item-checkbox" value="' . $item->id . '"></td>
                            <td>' . $no++ . '</td>
                            <td>' . $item->kode_sparepart . '</td>
                            <td>' . $item->nama_sparepart . '</td>
                            <td>' . $sub_kategori_name . '</td>
                            <td>Rp.' . number_format($item->harga_beli) . '</td>
                            <td>Rp.' . number_format($item->harga_jual) . '</td>
                            <td>' . $item->stok_sparepart . '</td>
                            <td>
                                <form action="' . $delete_route . '" onsubmit="return confirm(\'Anda yakin?\')" method="POST" style="display:inline;">
                                    ' . $this->getHiddenItemForm('DELETE') . '
                                    <a href="' . $edit_route . '" class="btn btn-sm btn-warning my-1"><i class="fas fa-edit"></i></a>
                                    <button type="submit" class="btn btn-sm btn-danger my-1"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>';
        }

        $data = $this->getTable($thead, $tbody);
        $kategori = KategoriSparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->get();

        // Render view konten (index.blade.php) ke dalam layout utama 'blank_page'
        $content = view('admin.page.sparepart.index', compact('page', 'link_tambah', 'data', 'kategori'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }


    // FUNGSI 2: Untuk mengambil data item yang akan diedit
    public function getDetailsForEdit(Request $request)
    {
         $request->validate(['ids' => 'required|array']);

        // TAMBAHKAN 'hargaKhusus' untuk mengambil data harga spesial
        $spareparts = Sparepart::with(['kategori', 'subKategori', 'hargaKhusus'])
            ->whereIn('id', $request->ids)
            ->get();

        return response()->json($spareparts);
    }


    // FUNGSI 3: Untuk menyimpan semua perubahan dari modal
    public function bulkUpdate(Request $request)
{
    $request->validate(['items' => 'required|array']);
    $itemsData = $request->input('items');

    try {
        DB::beginTransaction();

        foreach ($itemsData as $id => $data) {
            $sparepart = Sparepart::find($id);
            if ($sparepart) {
                // 1. Update data utama sparepart
                $sparepart->update([
                    'harga_beli' => $data['harga_beli'] ?? $sparepart->harga_beli,
                    'harga_jual' => $data['harga_jual'] ?? $sparepart->harga_jual,
                    'harga_pasang' => $data['harga_pasang'] ?? $sparepart->harga_pasang,
                    'stok_sparepart' => $data['stok_sparepart'] ?? $sparepart->stok_sparepart,
                ]);

                // 2. Ambil nilai harga khusus dari data input, default ke 0 jika tidak ada
                $hargaToko = $data['harga_khusus_toko'] ?? 0;
                $hargaSatuan = $data['harga_khusus_satuan'] ?? 0;
                $diskonNilai = $data['diskon_nilai'] ?? 0;

                // 3. Cek apakah ada nilai harga khusus yang valid (lebih dari 0)
                $hasHargaKhusus = $hargaToko > 0 || $hargaSatuan > 0 || $diskonNilai > 0;

                if ($hasHargaKhusus) {
                    // 4a. Jika ada, buat atau update data HargaKhusus
                    \App\Models\HargaKhusus::updateOrCreate(
                        ['id_sp' => $id], // Kondisi pencarian
                        [                 // Data untuk disimpan
                            'harga_toko' => $hargaToko,
                            'harga_satuan' => $hargaSatuan,
                            'diskon_tipe' => $data['diskon_tipe'] ?? null,
                            'diskon_nilai' => $diskonNilai,
                        ]
                    );
                } else {
                    // 4b. Jika tidak ada, hapus data HargaKhusus yang mungkin sudah ada
                    // Ini memungkinkan pengguna untuk menghapus harga khusus dengan mengosongkan input
                    $hargaKhusus = \App\Models\HargaKhusus::where('id_sp', $id)->first();
                    if ($hargaKhusus) {
                        $hargaKhusus->delete();
                    }
                }
            }
        }

        DB::commit();
        return response()->json(['message' => 'Sebanyak ' . count($itemsData) . ' item berhasil diperbarui.']);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Bulk update error: ' . $e->getMessage());
        return response()->json(['message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()], 500);
    }
}
    public function view_kategori(Request $request)
    {
        $page = "Data Kategori Sparepart";
        $link_tambah = route('create_kategori_sparepart');
        $thead = '<th width="5%">No</th><th width="15%">Image</th><th width="60%">Nama</th><th width="20%">Aksi</th>';
        $kategori = KategoriSparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        $tbody = '';
        $no = 1;
        foreach ($kategori as $item) {
            $edit = Request::create(route('EditKategoriSparepart', $item->id));
            $delete = Request::create(route('DeleteKategoriSparepart', $item->id));
            $foto = '<img src="' . asset('public/img/no_image.png') . '" width="100%" height="100%" class="img" id="view-img">';
            if ($item->foto_kategori != '-') {
                $foto =  '<img src="' . asset('public/uploads/' . $item->foto_kategori) . '" class="img" width="100%" height="100%">';
            }
            $tbody .= '<tr>
                            <td>' . $no++ . '</td>
                            <td>' . $foto . '</td>
                            <td>' . $item->nama_kategori . '</td>
                            <td>
                                <form action="' . $delete->url() . '" onsubmit="' . "return confirm('Apakah Anda yakin ?')" . '" method="POST">
                                    ' . $this->getHiddenItemForm('DELETE') . '
                                    <a href="' . $edit->url() . '" class="btn btn-warning"><i class="fas fa-edit"></i></a>
                                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </tr>';
        }
        $data = $this->getTable($thead, $tbody);
        return view('admin.layout.card_layout', compact(['page', 'data', 'link_tambah']));
    }

    public function view_opname(Request $request)
    {
        $page = "Opname Stok Sparepart";
        $sparepart = Sparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        $data_sparepart_rusak = SparepartRusak::join('spareparts', 'sparepart_rusaks.kode_barang', '=', 'spareparts.id')->where('sparepart_rusaks.kode_owner', '=', $this->getThisUser()->id_upline)->get(['sparepart_rusaks.id as id_rusak', 'sparepart_rusaks.*', 'spareparts.*']);
        $content = view('admin.page.opname_sparepart', compact(['sparepart', 'data_sparepart_rusak']));
        return view('admin.layout.blank_page', compact(['page', 'content']));
    }

    public function opname_ubah_stok(Request $request, $id)
    {
        $data = Sparepart::findOrFail($id);
        $data->update([
            'stock_asli' => $request->stock_asli
        ]);
        if ($data) {
            $update = SparepartRusak::where([['kode_barang', '=', $id]])->get()->first();
            if ($update) {
                $update->update([
                    'jumlah_rusak' => $request->jumlah_rusak
                ]);
            }

            return redirect()->back();
        }
    }
    //update stock opname
    public function update_stok_sparepart(Request $request)
    {
        // Dapatkan semua data sparepart
        $spareparts = Sparepart::all();

        // Loop melalui setiap sparepart
        foreach ($spareparts as $sparepart) {
            // Dapatkan stock_asli dari database
            $stock_asli = $sparepart->stock_asli;

            // Periksa apakah stock_asli lebih dari 0
            if ($stock_asli != null) {
                // Jika ya, update stok_sparepart dengan nilai stock_asli
                $sparepart->update([
                    'stok_sparepart' => strval($stock_asli)
                ]);

                // Nol kan stock_alis
                $sparepart->update([
                    'stock_asli' => null
                ]);
            }
        }

        return redirect()->back();
    }
    //update stock opname
    //Create Functions
    public function create_sparepart(Request $request)
    {
        $page = "Tambah Sparepart";
        $kategori = KategoriSparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        $sub_kategori = collect(); // Empty collection by default
        return view('admin.forms.sparepart', compact(['page', 'kategori','sub_kategori']));
    }
    public function create_kategori(Request $request)
    {
        $page = "Tambah Kategori Sparepart";
        return view('admin.forms.kategori_sparepart', compact(['page']));
    }
    public function store_kategori_sparepart(Request $request)
{
    $validate = $request->validate([
        'nama_kategori' => ['required'],
    ]);

    if ($validate) {
        $file = $request->file('foto_kategori');
        $foto = $file != null ? date('Ymdhis') . $file->getClientOriginalName() : '-';

        if ($file != null) {
            $file->move('public/uploads/', $foto);
        }

        $create = KategoriSparepart::create([
            'foto_kategori' => $foto,
            'nama_kategori' => $request->nama_kategori,
            'kode_owner' => $this->getThisUser()->id_upline
        ]);

        if ($create) {
            return redirect()->route('kategori_sparepart')
                ->with(['success' => 'Kategori Sparepart Berhasil Ditambahkan']);
        }

        return redirect()->back()->with('error', "Oops, Something Went Wrong");
    } else {
        return redirect()->back()->with('error', "Validating Error, Please Fill Required Field");
    }
}
     // Function to get subcategories by category ID (for AJAX)
     public function get_sub_kategori_by_kategori($kategori_id)
     {
         $sub_kategori = SubKategoriSparepart::where('kategori_id', $kategori_id)
                                 ->where('kode_owner', $this->getThisUser()->id_upline)
                                 ->get();

         return response()->json($sub_kategori);
     }

    // Store Functions
    public function store_sparepart(Request $request)
{
    $validate = $request->validate([
        'nama_sparepart' => ['required'],
        'kode_kategori' => ['required'],
        'stok_sparepart' => ['required'],
        'harga_beli' => ['required'],
        'harga_jual' => ['required'],
        'harga_pasang' => ['required'],
    ]);

    if ($validate) {
        $file = $request->file('foto_sparepart');
        $foto = $file != null ? date('Ymdhis') . $file->getClientOriginalName() : '-';

        if ($file != null) {
            $file->move('public/uploads/', $foto);
        }

        $count = Sparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get()->count();
        $kode_sparepart = 'SP' . date('Ymdhis') . $count;

        $create = Sparepart::create([
            'foto_sparepart' => $foto,
            'kode_sparepart' => $kode_sparepart,
            'nama_sparepart' => $request->nama_sparepart,
            'desc_sparepart' => $request->desc_sparepart,
            'kode_kategori' => $request->kode_kategori,
            'kode_sub_kategori' => $request->kode_sub_kategori,
            'stok_sparepart' => $request->stok_sparepart,
            'harga_beli' => $request->harga_beli,
            'harga_jual' => $request->harga_jual,
            'harga_pasang' => $request->harga_pasang,
            'kode_owner' => $this->getThisUser()->id_upline
        ]);

        if ($create) {
            // MODIFIED: Check if any special price value is greater than 0
            $hasHargaKhusus = ($request->filled('harga_khusus_toko') && $request->harga_khusus_toko > 0) ||
                             ($request->filled('harga_khusus_satuan') && $request->harga_khusus_satuan > 0) ||
                             ($request->filled('diskon_nilai') && $request->diskon_nilai > 0);

            // Create Harga Khusus only if there is valid input
            if ($hasHargaKhusus) {
                HargaKhusus::create([
                    'id_sp' => $create->id,
                    'harga_toko' => $request->harga_khusus_toko ?? 0,
                    'harga_satuan' => $request->harga_khusus_satuan ?? 0,
                    'diskon_tipe' => $request->diskon_tipe,
                    'diskon_nilai' => $request->diskon_nilai ?? 0,
                ]);
            }

            return redirect()->route('sparepart')
                ->with([
                    'success' => 'Sparepart Berhasil Ditambahkan'
                ]);
        }

        return redirect()->back()->with('error', "Oops, Something Went Wrong");
    } else {
        return redirect()->back()->with('error', "Validating Error, Please Fill Required Field");
    }
}

    //Edit Functions
    public function edit_sparepart(Request $request, $id)
    {
        $page = "Edit Sparepart";
        $kategori = KategoriSparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        $data = Sparepart::with('hargaKhusus')->findOrFail($id);

        // Get subcategories for the selected category
        $sub_kategori = SubKategoriSparepart::where('kategori_id', $data->kode_kategori)
                              ->where('kode_owner', $this->getThisUser()->id_upline)
                              ->get();

        return view('admin.forms.sparepart', compact(['page', 'data', 'kategori','sub_kategori']));
    }
    public function edit_kategori_sparepart(Request $request, $id)
    {
        $page = "Edit Kategori Sparepart";
        $data = KategoriSparepart::findOrFail($id);
        return view('admin.forms.kategori_sparepart', compact(['page', 'data']));
    }

    // Update Functions
    public function update_sparepart(Request $request, $id)
{
    $validate = $request->validate([
        'nama_sparepart' => ['required'],
        'kode_kategori' => ['required'],
        'stok_sparepart' => ['required'],
        'harga_beli' => ['required'],
        'harga_jual' => ['required'],
        'harga_pasang' => ['required'],
    ]);

    if ($validate) {
        $update = Sparepart::findOrFail($id);
        $file = $request->file('foto_sparepart');
        $foto = $file != null ? date('Ymdhis') . $file->getClientOriginalName() : $update->foto_sparepart;

        if ($file != null) {
            $file->move('public/uploads/', $foto);
        }

        $update->update([
            'foto_sparepart' => $foto,
            'nama_sparepart' => $request->nama_sparepart,
            'desc_sparepart' => $request->desc_sparepart,
            'kode_kategori' => $request->kode_kategori,
            'kode_sub_kategori' => $request->kode_sub_kategori,
            'stok_sparepart' => $request->stok_sparepart,
            'harga_beli' => $request->harga_beli,
            'harga_jual' => $request->harga_jual,
            'harga_pasang' => $request->harga_pasang,
        ]);

        if ($update) {
            // MODIFIED: Check if any special price value is greater than 0
            $hasHargaKhusus = ($request->filled('harga_khusus_toko') && $request->harga_khusus_toko > 0) ||
                             ($request->filled('harga_khusus_satuan') && $request->harga_khusus_satuan > 0) ||
                             ($request->filled('diskon_nilai') && $request->diskon_nilai > 0);

            if ($hasHargaKhusus) {
                // Update or create special price only if there is valid input
                HargaKhusus::updateOrCreate(
                    ['id_sp' => $update->id],
                    [
                        'harga_toko' => $request->harga_khusus_toko ?? 0,
                        'harga_satuan' => $request->harga_khusus_satuan ?? 0,
                        'diskon_tipe' => $request->diskon_tipe,
                        'diskon_nilai' => $request->diskon_nilai ?? 0,
                    ]
                );
            } else {
                // If special price fields are empty or 0, delete the existing record
                $hargaKhusus = HargaKhusus::where('id_sp', $update->id)->first();
                if ($hargaKhusus) {
                    $hargaKhusus->delete();
                }
            }

            return redirect()->route('sparepart')
                ->with([
                    'success' => 'Sparepart Berhasil DiUpdate'
                ]);
        }

        return redirect()->back()->with('error', "Oops, Something Went Wrong");
    } else {
        return redirect()->back()->with('error', "Validating Error, Please Fill Required Field");
    }
}

    public function update_kategori_sparepart(Request $request, $id)
    {
        $validate = $request->validate([
            'nama_kategori' => ['required'],
        ]);
        if ($validate) {
            $data_kategori = KategoriSparepart::findOrFail($id);
            $file = $request->file('foto_kategori');
            $foto = $file != null ? date('Ymdhis') . $file->getClientOriginalName() : $data_kategori->foto_kategori;
            if ($file != null) {
                $file->move('public/uploads/', $foto);
            }
            $data_kategori->update([
                'foto_kategori' => $foto,
                'nama_kategori' => $request->nama_kategori
            ]);
            if ($data_kategori) {
                return redirect()->route('kategori_sparepart')
                    ->with([
                        'success' => 'Kategori Sparepart Berhasil DiUpdate'
                    ]);
            }
            return redirect()->back()->with('error', "Oops, Something Went Wrong");
        } else {
            return redirect()->back()->with('error', "Validating Error, Please Fill Required Field");
        }
    }
    //Delete Functions
    public function delete_kategori_sparepart($id)
    {
        $data = KategoriSparepart::findOrFail($id);
        if ($data->foto_kategori != '-') {
            File::delete(public_path('uploads/' . $data->foto_kategori));
        }
        $data->delete();
        if ($data) {
            return redirect()->route('kategori_sparepart')
                ->with([
                    'success' => 'Kategori Sparepart Berhasil Dihapus'
                ]);
        }
        return redirect()->route('kategori_sparepart')->with('error', "Oops, Something Went Wrong");
    }
    //Delete Functions
    public function delete_sparepart($id)
    {
        $data = Sparepart::findOrFail($id);
        $data->stockHistory()->delete();
         $data->stockNotifications()->delete();

        if ($data->foto_sparepart != '-') {
            File::delete(public_path('uploads/' . $data->foto_sparepart));
        }
        $data->delete();
        if ($data) {
            return redirect()->route('sparepart')
                ->with([
                    'success' => 'Sparepart Berhasil Dihapus'
                ]);
        }
        return redirect()->route('sparepart')->with('error', "Oops, Something Went Wrong");
    }

    public function view_stok(Request $request)
    {
        $page = "Data Stok Sparepart";

        // Ambil input filter
        $filter_kategori = $request->input('filter_kategori');
        $filter_spl = $request->input('filter_spl');

        // Ambil data terjual dan terpakai
        $view_terjual = DetailSparepartPenjualan::join('penjualans', 'detail_sparepart_penjualans.kode_penjualan', '=', 'penjualans.id')
            ->where('penjualans.status_penjualan', '=', '1')
            ->groupBy('detail_sparepart_penjualans.kode_sparepart')
            ->selectRaw('kode_sparepart, sum(qty_sparepart) as total_terjual')
            ->get();

        $view_terpakai = DetailPartServices::join('sevices', 'detail_part_services.kode_services', '=', 'sevices.id')
            ->where('sevices.status_services', '!=', 'Cancel')
            ->groupBy('detail_part_services.kode_sparepart')
            ->selectRaw('kode_sparepart, sum(qty_part) as total_terpakai')
            ->get();

        // Ambil data sparepart dengan filter
        $data_sparepart = Sparepart::query();

        // Tambahkan filter kategori dan SPL
        if ($filter_kategori) {
            $data_sparepart->where('kode_kategori', $filter_kategori);
        }

        if ($filter_spl) {
            $data_sparepart->where('kode_spl', $filter_spl);
        }

        $data_sparepart = $data_sparepart->latest()->get();

        // Data lain tetap diambil tanpa filter
        // $data_sparepart_rusak = SparepartRusak::join('spareparts', 'sparepart_rusaks.kode_barang', '=', 'spareparts.id')
        //     ->where('sparepart_rusaks.kode_owner', '=', $this->getThisUser()->id_upline)
        //     ->get(['sparepart_rusaks.id as id_rusak', 'sparepart_rusaks.*', 'spareparts.*']);

        $data_sparepart_rusak = SparepartRusak::join('spareparts', 'sparepart_rusaks.kode_barang', '=', 'spareparts.id')
            ->where('sparepart_rusaks.kode_owner', '=', $this->getThisUser()->id_upline)
            ->when($filter_kategori, function ($query) use ($filter_kategori) {
                return $query->where('spareparts.kode_kategori', $filter_kategori);
            })
            ->get(['sparepart_rusaks.id as id_rusak', 'sparepart_rusaks.*', 'spareparts.*']);

        $data_sparepart_retur = ReturSparepart::join('spareparts', 'retur_spareparts.kode_barang', '=', 'spareparts.id')
            ->join('suppliers', 'retur_spareparts.kode_supplier', '=', 'suppliers.id')
            ->where('retur_spareparts.kode_owner', '=', $this->getThisUser()->id_upline)
            ->get(['retur_spareparts.id as id_retur', 'retur_spareparts.*', 'spareparts.*', 'suppliers.*']);

        $data_sparepart_restok = RestokSparepart::join('spareparts', 'restok_spareparts.kode_barang', '=', 'spareparts.id')
            ->join('suppliers', 'restok_spareparts.kode_supplier', '=', 'suppliers.id')
            ->where('restok_spareparts.kode_owner', '=', $this->getThisUser()->id_upline)
            ->get(['restok_spareparts.id as id_restok', 'spareparts.*', 'restok_spareparts.*', 'suppliers.*']);

        // Ambil data kategori dan SPL untuk dropdown
        $data_kategori = KategoriSparepart::all();
        $data_spl = Supplier::all();

        $content = view('admin.page.stok_sparepart', compact(['data_sparepart', 'data_sparepart_rusak', 'data_sparepart_retur', 'data_sparepart_restok', 'view_terjual', 'view_terpakai', 'data_kategori', 'data_spl', 'filter_kategori', 'filter_spl']));

        return view('admin.layout.blank_page', compact(['page', 'content']));
    }
    //list order
    public function store(Request $request)
    {
        $request->validate([
            'qty' => 'required|integer|min:1',
            'id_barang' => 'required',
            'id_kategori' => 'required',
            'id_spl' => 'required',
        ]);

        // Cek apakah ada order yang sudah ada dengan SPL yang sama dan status 0
        $existingOrder = Order::where('spl_kode', $request->id_spl)
            ->where('status_order', 0)
            ->first();

        if ($existingOrder) {
            // Jika ada, gunakan kode order yang sudah ada
            $orderId = $existingOrder->id;
        } else {
            do {
                $kode_order = 'ORD' . mt_rand(100000, 999999);
            } while (Order::where('kode_order', $kode_order)->exists());

            // Jika tidak ada, buat order baru
            $order = new Order();
            $order->kode_order = $kode_order; // Contoh kode order, sesuaikan dengan logika Anda
            $order->spl_kode = $request->id_spl;
            $order->kode_owner = $this->getThisUser()->id_upline; // Ambil kode owner sesuai logika Anda
            $order->status_order = 0; // Status order 0
            $order->save();

            $orderId = $order->id;
        }


        // Ambil nama barang dari tabel sparepart
        $sparepart = Sparepart::find($request->id_barang);
        $nama_barang = $sparepart->nama_sparepart; // Ambil nama barang
        $beli_terakhir = $sparepart->harga_beli;
        $pasang_terakhir = $sparepart->harga_jual;
        $ecer_terakhir = $sparepart->harga_ecer;
        $jasa_terakhir = $sparepart->harga_pasang;

        // Menyimpan detail order
        $detailOrder = new DetailOrder();
        $detailOrder->id_order = $orderId;
        $detailOrder->id_barang = $request->id_barang;

        // Misalkan Anda mendapatkan id_pesanan dari request atau logika lain
        $detailOrder->id_pesanan = $request->id_pesanan ?? null; // Atau sesuaikan dengan logika Anda
        $detailOrder->id_kategori = $request->id_kategori;
        $detailOrder->nama_barang = $nama_barang; // Isi nama barang yang diambil dari sparepart
        $detailOrder->qty = $request->qty;
        $detailOrder->beli_terakhir = $beli_terakhir ?? null;
        $detailOrder->pasang_terakhir = $pasang_terakhir ?? null;
        $detailOrder->ecer_terakhir = $ecer_terakhir ?? null;
        $detailOrder->jasa_terakhir = $jasa_terakhir ?? null;

        // Set nilai lainnya sesuai kebutuhan
        $detailOrder->save();

        return redirect()->back()->with('success', 'Order berhasil ditambahkan.');
    }
    public function view_order(Request $request)
    {
        $page = "List Data Order";
        // Ambil semua SPL
        $activeSpls = Supplier::all();

        // Ambil data order berdasarkan SPL yang dipilih atau tampilkan salah satu SPL secara default
        $selectedSplId = $request->get('filter_spl', $activeSpls->first()->id ?? null); // Ambil SPL pertama jika tidak ada filter

        // Ambil data order berdasarkan SPL yang dipilih
        $orders = DetailOrder::with('order') // Pastikan Anda mendefinisikan relasi di model
            ->whereHas('order', function ($query) use ($selectedSplId) {
                $query->where('spl_kode', $selectedSplId)
                    ->where('status_order', 0);
            })
            ->get();

        // Mengambil semua DetailOrder yang terkait dengan order yang memiliki status = 1
        // serta informasi Supplier yang terkait dengan order
        $detailOrders = DetailOrder::whereHas('order', function ($query) {
            $query->where('status_order', 1)
                ->where('update', 0); // Pastikan kolom status sesuai dengan yang ada di tabel 'order'
        })
            ->with(['order.supplier'])  // Eager load supplier melalui order
            ->get();


        $content = view('admin.page.list_order', compact(['orders', 'activeSpls', 'selectedSplId', 'detailOrders']));
        return view('admin.layout.blank_page', compact('page', 'content'));
    }
    public function updateStatus(Request $request)
    {
        // Validasi
        $request->validate([
            'kode_order' => 'required|string',
        ]);

        // Temukan order berdasarkan kode_order
        $order = Order::where('kode_order', $request->kode_order)->first();

        if (!$order) {
            return redirect()->back()->with('error', 'Order tidak ditemukan.');
        }

        // Update status order menjadi 1
        $order->status_order = 1;
        $order->save();


        return redirect()->back()->with('success', 'Status order berhasil diperbarui.');
    }
    public function updateSpl(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:order,kode_order',
            'spl_id' => 'required|exists:suppliers,id',
        ]);

        // Temukan order berdasarkan kode_order
        $order = Order::where('kode_order', $request->order_id)->first();

        // Update SPL pada order
        if ($order) {
            $order->spl_kode = $request->spl_id; // Update kode SPL
            $order->save();

            return redirect()->back()->with('success', 'SPL berhasil diperbarui.');
        }

        return redirect()->back()->with('error', 'Order tidak ditemukan.');
    }
    public function updateOrderToStock(Request $request, $id)
    {

        // Validasi data yang dikirimkan
        $request->validate([
            'data.nama_barang' => 'required|string|max:255',
            'data.qty' => 'required|numeric',  // Pastikan qty juga angka
            'data.beli_terakhir' => 'required|numeric',
            'data.pasang_terakhir' => 'required|numeric',
            'data.ecer_terakhir' => 'required|numeric',
            'data.jasa_terakhir' => 'required|numeric',
        ]);

        // Ambil data yang dikirimkan melalui request
        $restockData = $request->data;
        // Cari sparepart berdasarkan ID
        $sparepart = Sparepart::find($id);

        if ($sparepart) {
            $sparepart->update([
                'nama_sparepart' => $restockData['nama_barang'],
                'stok_sparepart' => isset($restockData['qty']) ? $sparepart->stok_sparepart + $restockData['qty'] : $sparepart->stok_sparepart,
                'harga_beli' => $restockData['beli_terakhir'],
                'harga_jual' => $restockData['pasang_terakhir'],
                'harga_ecer' => $restockData['ecer_terakhir'],
                'harga_pasang' => $restockData['jasa_terakhir'],
                'kode_kategori' => $restockData['kode_kategori'] ?? $sparepart->kode_kategori,
                'kode_spl' => $restockData['kode_spl'] ?? $sparepart->kode_spl,
            ]);
            DetailOrder::where('id_barang', $id)->update(['update' => 1]);
            // Kirim respons sukses jika data berhasil disimpan
            return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui!']);
        }

        return response()->json(['success' => false, 'message' => 'Sparepart tidak ditemukan!'], 404);
    }
    // end list order
    //Sparepart Rusak
    public function create_sparepart_rusak(Request $request)
    {
        $page = "Tambah Sparepart Rusak";
        $sparepart = Sparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        $supplier = Supplier::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        return view('admin.forms.sparepart_rusak', compact(['page', 'sparepart', 'supplier']));
    }
    public function edit_sparepart_rusak(Request $request, $id)
    {
        $page = "Edit Barang Rusak";
        $data = SparepartRusak::findOrFail($id);
        $sparepart = Sparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        return view('admin.forms.sparepart_rusak', compact(['page', 'sparepart', 'data']));
    }

    public function store_sparepart_rusak(Request $request)
    {
        $data_barang = Sparepart::findOrFail($request->kode_barang);
        if ($data_barang->stok_sparepart < $request->jumlah_rusak) {
            return redirect()->back()
                ->with([
                    'error' => 'Jumlah Sparepart Yang Rusak Tidak Boleh Melebihi Stok Barang'
                ]);
        }
        $create = SparepartRusak::create([
            'tgl_rusak_barang' => $request->tgl_rusak_barang,
            'kode_barang' => $request->kode_barang,
            'jumlah_rusak' => $request->jumlah_rusak,
            'catatan_rusak' => $request->catatan_rusak != null ? $request->catatan_rusak : '-',
            'user_input' => auth()->user()->id,
            'kode_owner' => $request->kode_owner,
        ]);
        if ($create) {
            $stok_awal = $data_barang->stok_sparepart;
            $stok_baru = $stok_awal - $request->jumlah_rusak;
            $data_barang->update([
                'stok_sparepart' => $stok_baru,
            ]);
            return redirect()->route('stok_sparepart')
                ->with([
                    'success' => 'Sparepart Rusak Berhasil DiTambah'
                ]);
        }
        return redirect()->route('stok_sparepart')->with('error', "Oops, Something Went Wrong");
    }

    public function update_sparepart_rusak(Request $request, $id)
    {

        $update = SparepartRusak::findOrFail($id);
        $data_barang = Sparepart::findOrFail($update->kode_barang);
        if ($data_barang != null) {
            $stok_awal = $data_barang->stok_sparepart + $update->jumlah_rusak;
            if ($request->jumlah_rusak <= $stok_awal) {
                $stok_baru = $stok_awal - $request->jumlah_rusak;
                $data_barang->update([
                    'stok_sparepart' => $stok_baru
                ]);
                if ($data_barang) {
                    $update->update([
                        'tgl_rusak_barang' => $request->tgl_rusak_barang,
                        'jumlah_rusak' => $request->jumlah_rusak,
                        'catatan_rusak' => $request->catatan_rusak != null ? $request->catatan_rusak : '-',
                        'user_input' => auth()->user()->id,
                    ]);
                    return redirect()->route('stok_sparepart')
                        ->with([
                            'success' => 'Sparepart Rusak Berhasil Diedit'
                        ]);
                }
                return redirect()->route('stok_sparepart')->with('error', "Oops, Something Went Wrong");
            } else {
                return redirect()->back()
                    ->with([
                        'error' => 'Jumlah SPAREPART Yang Rusak Tidak Boleh Melebihi Stok Sparepart'
                    ]);
            }
        }
    }
    public function delete_sparepart_rusak(Request $request, $id) {}

    // Restok Sparepart

    public function create_sparepart_restok(Request $request)
    {
        $page = "Restok Sparepart";
        $sparepart = Sparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        $supplier = Supplier::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        return view('admin.forms.restok_sparepart', compact(['page', 'sparepart', 'supplier']));
    }
    public function edit_sparepart_restok(Request $request, $id)
    {
        $page = "Edit Restok Sparepart";
        $data = RestokSparepart::findOrFail($id);
        $sparepart = Sparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        $supplier = Supplier::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        return view('admin.forms.restok_sparepart', compact(['page', 'sparepart', 'data', 'supplier']));
    }
    public function store_sparepart_restok(Request $request)
    {
        $data_restok = RestokSparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        $kode_restok = 'RS' . date('Ymd') . $data_restok->count();
        $create = RestokSparepart::create([
            'kode_owner' => $request->kode_owner,
            'kode_restok' => $kode_restok,
            'tgl_restok' => $request->tgl_restok,
            'kode_barang' => $request->kode_barang,
            'kode_supplier' => $request->kode_supplier,
            'jumlah_restok' => $request->jumlah_restok,
            'status_restok' => $request->status_restok,
            'catatan_restok' => $request->catatan_restok != null ? $request->catatan_restok : '-',
            'user_input' => auth()->user()->id,
        ]);
        if ($create) {
            $update = Sparepart::findOrFail($request->kode_barang);
            if ($request->status_restok == 'Success') {
                if ($update != null) {
                    $stok_awal = $update->stok_sparepart;
                    $stok_baru = $stok_awal + $request->jumlah_restok;
                    $update->update([
                        'stok_sparepart' => $stok_baru
                    ]);
                }
            }
            if ($update != null) {
                $update->update([
                    'harga_beli' => $request->harga_beli,
                    'harga_jual' => $request->harga_jual,
                    'harga_pasang' => $request->harga_pasang,
                ]);
            }

            return redirect()->route('stok_sparepart')
                ->with([
                    'success' => 'Tambah Restok Berhasil'
                ]);
        }
        return redirect()->route('stok_sparepart')->with('error', "Oops, Something Went Wrong");
    }
    public function update_sparepart_restok(Request $request, $id)
    {
        $update = RestokSparepart::findOrFail($id);
        $update->update([
            'tgl_restok' => $request->tgl_restok,
            'jumlah_restok' => $request->jumlah_restok,
            'status_restok' => $request->status_restok,
            'catatan_restok' => $request->catatan_restok != null ? $request->catatan_restok : '-',
            'user_input' => auth()->user()->id
        ]);
        if ($update) {
            $updates = Sparepart::findOrFail($update->kode_barang);
            if ($request->status_restok == 'Success') {

                if ($updates != null) {
                    $stok_awal = $updates->stok_sparepart;
                    $stok_baru = $stok_awal + $request->jumlah_restok;
                    $updates->update([
                        'stok_sparepart' => $stok_baru
                    ]);
                    return redirect()->route('stok_sparepart')
                        ->with([
                            'success' => 'Edit Restok Berhasil'
                        ]);
                }
            }
            if ($updates != null) {
                $updates->update([
                    'harga_beli' => $request->harga_beli,
                    'harga_jual' => $request->harga_jual,
                    'harga_pasang' => $request->harga_pasang,
                ]);
            }
            return redirect()->route('stok_sparepart')
                ->with([
                    'success' => 'Edit Restok Berhasil'
                ]);
        }
        return redirect()->route('stok_produk')->with('error', "Oops, Something Went Wrong");
    }
    public function delete_sparepart_restok(Request $request, $id)
    {
        $data = RestokSparepart::findOrFail($id);
        $data->delete();
        if ($data) {
            return redirect()->route('stok_sparepart')
                ->with([
                    'success' => 'Restok Berhasil Dihapus'
                ]);
        }
        return redirect()->route('stok_sparepart')->with('error', "Oops, Something Went Wrong");
    }

    // Retur Sparepart

    public function create_sparepart_retur(Request $request)
    {
        $page = "Retur Sparepart";
        $sparepart = Sparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        $supplier = Supplier::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        return view('admin.forms.retur_sparepart', compact(['page', 'sparepart', 'supplier']));
    }
    public function store_sparepart_retur(Request $request)
    {
        $data_barang = Sparepart::findOrFail($request->kode_barang);
        if ($data_barang->stok_sparepart < $request->jumlah_retur) {
            return redirect()->back()
                ->with([
                    'error' => 'Jumlah Sparepart Yang Diretur Tidak Boleh Melebihi Stok Barang'
                ]);
        }

        $create = ReturSparepart::create([
            'tgl_retur_barang' => $request->tgl_retur_barang  ?: today('Y-m-d'),
            'kode_barang' => $request->kode_barang,
            'kode_supplier' => $request->kode_supplier,
            'jumlah_retur' => $request->jumlah_retur,
            'catatan_retur' => $request->catatan_retur ?: '-',
            'user_input' => auth()->user()->id,
            'kode_owner' => $request->kode_owner,
        ]);
        if ($create) {
            $stok_awal = $data_barang->stok_sparepart;
            $stok_baru = $stok_awal - $request->jumlah_retur;
            $data_barang->update([
                'stok_sparepart' => $stok_baru,
            ]);
            return redirect()->route('stok_sparepart')
                ->with([
                    'success' => 'Retur Berhasil DiTambah'
                ]);
        }
        return redirect()->route('stok_sparepart')->with('error', "Oops, Something Went Wrong");
    }
    // baru ditambahkan untuk retur
    public function store_sparepart_retur_toko(Request $request)
    {
        $data_sparepart = Sparepart::findOrFail($request->kode_barang);
        try {
            $detail_penjualan = DetailSparepartPenjualan::where('kode_penjualan', $request->penjualan_id)
                ->where('kode_sparepart', $request->kode_barang)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return back()->with('error', 'Barang tidak ditemukan untuk penjualan ini.');
        }
        if ($detail_penjualan->qty_sparepart < $request->jumlah_retur) {
            return redirect()->back()
                ->with([
                    'error' => 'Jumlah Sparepart Yang Diretur Tidak Boleh Melebihi Stok Barang'
                ]);
        }
        if ($request->jenis_retur == 'supplier') {
            $create = ReturSparepart::create([
                'tgl_retur_barang' => $request->tgl_retur_barang  ?: now()->toDateString(),
                'kode_barang' => $request->kode_barang,
                'kode_supplier' => $request->kode_supplier,
                'jumlah_retur' => $request->jumlah_retur,
                'catatan_retur' => $request->catatan_retur ?: '-',
                'user_input' => auth()->user()->id,
                'kode_owner' => $this->getThisUser()->id_upline,

            ]);
            if ($create) {
                $qty_awal = $detail_penjualan->qty_sparepart;
                $qty_baru = $qty_awal - $request->jumlah_retur;
                $detail_penjualan->update([
                    'status_rf' => 1,
                    'qty_sparepart' => $qty_baru,
                ]);
                return redirect()->back()
                    ->with([
                        'success' => 'Retur Berhasil DiTambah'
                    ]);
            }
        } else {
            $qty_awal = $detail_penjualan->qty_sparepart;
            $qty_baru = $qty_awal - $request->jumlah_retur;
            $detail_penjualan->update([
                'status_rf' => 1,
                'qty_sparepart' => $qty_baru,
            ]);
            $stock_awal = $data_sparepart->stok_sparepart;
            $stok_baru = $stock_awal + $request->jumlah_retur;
            $data_sparepart->update([
                'stok_sparepart' => $stok_baru,
            ]);
            return redirect()->back()
                ->with([
                    'success' => 'Retur Berhasil DiTambah'
                ]);
        }


        return redirect()->back()->with('error', "Oops, Something Went Wrong");
    }
    // end baru

    public function ubah_status_retur(Request $request, $id)
    {
        $update = ReturSparepart::findOrFail($id);
        $update->update([
            'status_retur' => $request->status_retur
        ]);
        if ($update) {
            $data_barang = Sparepart::findOrFail($update->kode_barang);
            $stok_awal = $data_barang->stok_sparepart;
            $stok_baru = $stok_awal + $update->jumlah_retur;
            $data_barang->update([
                'stok_sparepart' => $stok_baru,
            ]);
            return redirect()->back();
        }
    }
    public function plus()
    {
        $page = "Pembelian";
        $kategori = KategoriSparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        $supplier = Supplier::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        $sparepart = Sparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();

        return view('admin.page.plus', compact(['page', 'kategori', 'sparepart', 'supplier']));
    }
    public function updateHargaEcer()
    {
        // Ambil semua data sparepart
        $spareparts = Sparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->get();

        // Loop melalui setiap sparepart
        foreach ($spareparts as $sparepart) {
            // Ambil harga_jual dari database sesuai dengan ID sparepart
            $harga_jual = $sparepart->harga_jual;

            // Update harga_ecer dengan nilai harga_jual
            $sparepart->update([
                'harga_ecer' => $harga_jual
            ]);
        }

        return redirect()->back()->with('success', 'Harga ecer telah diperbarui.');
    }

    public function processData(Request $request)
    {
        // Validasi data
        $request->validate([
            'kode_kategori' => 'required',
            'kode_supplier' => 'required',
            'spareparts.*.kode' => 'required',
            'restocks.*.kode_harga' => 'required',
            'kode_nota' => 'nullable',
        ]);

        // Dapatkan data dari request
        $kodeKategori = $request->input('kode_kategori');
        $kodeSupplier = $request->input('kode_supplier');
        $spareparts = $request->input('spareparts');
        $restocks = $request->input('restocks');
        $kodeNota = $request->input('kode_nota'); // Ambil kode_nota dari request

        $totalHutang = 0; // Initialize total hutang


        // Cek apakah data spareparts
        if (!empty($spareparts)) {
            // Simpan data ke tabel "sparepart"
            foreach ($spareparts as $index => $sparepartData) {

                // Check if spare part name already exists
                $existingSparepart = Sparepart::where('nama_sparepart', $sparepartData['nama'])->first();

                if ($existingSparepart) {
                    // If spare part name already exists, skip this iteration
                    continue;
                }

                // Parsing kode untuk mendapatkan informasi harga
                $parsedData = $this->parseKode($sparepartData['kode']);

                $sparepart = new Sparepart;
                $sparepart->nama_sparepart = $sparepartData['nama'];
                $sparepart->stok_sparepart = $sparepartData['stok'];
                $sparepart->harga_beli = $parsedData['hargaBeli'];
                $sparepart->harga_jual = $parsedData['hargaJual'];
                $sparepart->harga_ecer = $parsedData['hargaEcer'];
                $sparepart->harga_pasang = $parsedData['hargaPasang'];
                $sparepart->kode_kategori = $kodeKategori;
                $sparepart->kode_spl = $kodeSupplier;
                $sparepart->foto_sparepart = '-';

                // Generate dynamic spare part code
                $count = Sparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get()->count();
                $dynamicCount = $count + $index + 1; // Add index to ensure uniqueness for each spare part
                $kode_sparepart = 'SP' . date('Ymdhis') . $dynamicCount;

                $sparepart->kode_sparepart = $kode_sparepart;
                $sparepart->kode_owner = $this->getThisUser()->id_upline;
                $sparepart->save();
                // Tambahkan harga beli ke total hutang
                $totalHutang += $parsedData['hargaBeli'] * $sparepartData['stok'];
            }
        }

        // Cek apakah data restocks
        if (!empty($restocks)) {
            // Simpan data ke tabel "sparepart"
            foreach ($restocks as $index => $restockData) {
                // Lakukan operasi update pada data restocks berdasarkan ID
                $sparepart = Sparepart::find($restockData['id']);

                if ($sparepart) {
                    // Parsing kode untuk mendapatkan informasi harga
                    $parsedData = $this->parseKode($restockData['kode_harga']);

                    $sparepart->nama_sparepart = $restockData['nama_sparepart'];
                    $sparepart->stok_sparepart += $restockData['stok_sparepart'];
                    $sparepart->harga_beli = $parsedData['hargaBeli'];
                    // Biarkan nilai yang lain tidak berubah jika tidak ada input
                    $sparepart->harga_jual = $parsedData['hargaJual'] ?? $sparepart->harga_jual;
                    $sparepart->harga_ecer = $parsedData['hargaEcer'] ?? $sparepart->harga_ecer;
                    $sparepart->harga_pasang = $parsedData['hargaPasang'] ?? $sparepart->harga_pasang;
                    $sparepart->kode_kategori = $kodeKategori;
                    $sparepart->kode_spl = $kodeSupplier;
                    $sparepart->foto_sparepart = '-';

                    // Simpan perubahan pada data restock
                    $sparepart->save();
                    // Tambahkan harga beli ke total hutang
                    $totalHutang += $parsedData['hargaBeli'] * $restockData['stok_sparepart'];
                }
            }
        }
        // Simpan total hutang ke tabel "hutang"
        if ($kodeNota) {
            // Cek apakah kode_nota sudah ada
            $existingHutang = Hutang::where('kode_nota', $kodeNota)->first();

            if (!$existingHutang) {
                // Hanya simpan jika tidak ada entri dengan kode_nota yang sama
                $hutang = new Hutang;
                $hutang->kode_supplier = $kodeSupplier;
                $hutang->kode_owner = $this->getThisUser()->id_upline;
                $hutang->total_hutang = $totalHutang;
                $hutang->kode_nota = $kodeNota; // Pastikan kode_nota disimpan
                $hutang->status = 1;
                $hutang->save();
            }
        }


        // Berikan respons yang sesuai
        return response()->json(['message' => 'Data berhasil disimpan'], 200);
    }

    private function parseKode($kode)
    {
        $teks = $kode;
        $hasil_parsing = preg_split('/[\/n,]+/', $teks);

        $label = ['hargaBeli', 'hargaJual', 'hargaEcer', 'hargaPasang'];
        $hasil_final = array_combine($label, $hasil_parsing);

        // Rumus konversi
        $konversi = [
            'a' => 0,
            'b' => 1,
            'c' => 2,
            'd' => 3,
            'e' => 4,
            'f' => 5,
            'g' => 6,
            'h' => 7,
            'i' => 8,
            'j' => 9,
            'r' => '000',
            's' => '00'
        ];

        // Mengonversi nilai
        foreach ($hasil_final as $label => $nilai) {
            // Menambahkan kondisi untuk memeriksa apakah nilai adalah angka
            if (is_numeric($nilai)) {
                // Jika nilai adalah angka, pisahkan dengan garis miring
                $nilai = ($nilai); // Pisahkan angka dengan format 120000 menjadi 120/000
            } else {
                // Jika bukan angka, lakukan konversi sesuai dengan tabel konversi
                $nilai = strtr($nilai, $konversi);
            }
            $hasil_final[$label] = $nilai;
        }

        // Mengonversi nilai
        // foreach ($hasil_final as $label => $nilai) {
        //     $hasil_final[$label] = strtr($nilai, $konversi);
        // }

        // Mengembalikan hasil setelah konversi
        return $hasil_final;
    }

    //sub kategori
    public function view_sub_kategori(Request $request, $kategori_id = null)
    {
        $page = "Data Sub Kategori Sparepart";

        // Filter by kategori_id if provided
        if ($kategori_id) {
            $kategori = KategoriSparepart::findOrFail($kategori_id);
            $page .= " - " . $kategori->nama_kategori;
            $link_tambah = route('create_sub_kategori_sparepart', $kategori_id);
            $sub_kategori = SubKategoriSparepart::where('kategori_id', $kategori_id)
                                    ->where('kode_owner', $this->getThisUser()->id_upline)
                                    ->latest()
                                    ->get();
        } else {
            $link_tambah = route('create_sub_kategori_sparepart');
            $sub_kategori = SubKategoriSparepart::where('kode_owner', $this->getThisUser()->id_upline)
                                     ->latest()
                                     ->get();
        }

        $thead = '<th width="5%">No</th>
                 <th width="15%">Image</th>
                 <th width="20%">Kategori</th>
                 <th width="40%">Nama Sub Kategori</th>
                 <th width="20%">Aksi</th>';

        $tbody = '';
        $no = 1;

        foreach ($sub_kategori as $item) {
            $edit = Request::create(route('EditSubKategoriSparepart', $item->id));
            $delete = Request::create(route('DeleteSubKategoriSparepart', $item->id));

            $foto = '<img src="' . asset('public/img/no_image.png') . '" width="100%" height="100%" class="img" id="view-img">';
            if ($item->foto_sub_kategori != '-') {
                $foto =  '<img src="' . asset('public/uploads/' . $item->foto_sub_kategori) . '" class="img" width="100%" height="100%">';
            }

            $tbody .= '<tr>
                        <td>' . $no++ . '</td>
                        <td>' . $foto . '</td>
                        <td>' . $item->kategori->nama_kategori . '</td>
                        <td>' . $item->nama_sub_kategori . '</td>
                        <td>
                            <form action="' . $delete->url() . '" onsubmit="' . "return confirm('Apakah Anda yakin ?')" . '" method="POST">
                                ' . $this->getHiddenItemForm('DELETE') . '
                                <a href="' . $edit->url() . '" class="btn btn-warning"><i class="fas fa-edit"></i></a>
                                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                      </tr>';
        }

        $data = $this->getTable($thead, $tbody);

        return view('admin.layout.card_layout', compact(['page', 'data', 'link_tambah']));
    }

    public function create_sub_kategori(Request $request, $kategori_id = null)
    {
        $page = "Tambah Sub Kategori Sparepart";
        $kategori = KategoriSparepart::where('kode_owner', $this->getThisUser()->id_upline)->latest()->get();
        $selected_kategori = $kategori_id ? KategoriSparepart::findOrFail($kategori_id) : null;

        return view('admin.forms.sub_kategori_sparepart', compact(['page', 'kategori', 'selected_kategori']));
    }

    public function store_sub_kategori_sparepart(Request $request)
    {
        $validate = $request->validate([
            'kategori_id' => ['required'],
            'nama_sub_kategori' => ['required'],
        ]);

        if ($validate) {
            $file = $request->file('foto_sub_kategori');
            $foto = $file != null ? date('Ymdhis') . $file->getClientOriginalName() : '-';

            if ($file != null) {
                $file->move('public/uploads/', $foto);
            }

            $create = SubKategoriSparepart::create([
                'kategori_id' => $request->kategori_id,
                'foto_sub_kategori' => $foto,
                'nama_sub_kategori' => $request->nama_sub_kategori,
                'kode_owner' => $this->getThisUser()->id_upline
            ]);

            if ($create) {
                return redirect()->route('sub_kategori_sparepart')
                    ->with([
                        'success' => 'Sub Kategori Sparepart Berhasil Ditambahkan'
                    ]);
            }

            return redirect()->back()->with('error', "Oops, Something Went Wrong");
        } else {
            return redirect()->back()->with('error', "Validating Error, Please Fill Required Field");
        }
    }

    public function edit_sub_kategori_sparepart(Request $request, $id)
    {
        $page = "Edit Sub Kategori Sparepart";
        $data = SubKategoriSparepart::findOrFail($id);
        $kategori = KategoriSparepart::where('kode_owner', $this->getThisUser()->id_upline)->latest()->get();

        return view('admin.forms.sub_kategori_sparepart', compact(['page', 'data', 'kategori']));
    }

    public function update_sub_kategori_sparepart(Request $request, $id)
    {
        $validate = $request->validate([
            'kategori_id' => ['required'],
            'nama_sub_kategori' => ['required'],
        ]);

        if ($validate) {
            $data_sub_kategori = SubKategoriSparepart::findOrFail($id);
            $file = $request->file('foto_sub_kategori');
            $foto = $file != null ? date('Ymdhis') . $file->getClientOriginalName() : $data_sub_kategori->foto_sub_kategori;

            if ($file != null) {
                $file->move('public/uploads/', $foto);
            }

            $data_sub_kategori->update([
                'kategori_id' => $request->kategori_id,
                'foto_sub_kategori' => $foto,
                'nama_sub_kategori' => $request->nama_sub_kategori
            ]);

            if ($data_sub_kategori) {
                return redirect()->route('sub_kategori_sparepart')
                    ->with([
                        'success' => 'Sub Kategori Sparepart Berhasil DiUpdate'
                    ]);
            }

            return redirect()->back()->with('error', "Oops, Something Went Wrong");
        } else {
            return redirect()->back()->with('error', "Validating Error, Please Fill Required Field");
        }
    }

    public function delete_sub_kategori_sparepart($id)
    {
        $data = SubKategoriSparepart::findOrFail($id);

        if ($data->foto_sub_kategori != '-') {
            File::delete(public_path('uploads/' . $data->foto_sub_kategori));
        }

        $data->delete();

        if ($data) {
            return redirect()->route('sub_kategori_sparepart')
                ->with([
                    'success' => 'Sub Kategori Sparepart Berhasil Dihapus'
                ]);
        }

        return redirect()->route('sub_kategori_sparepart')->with('error', "Oops, Something Went Wrong");
    }

    // zakat
    public function view_zakat(Request $request)
    {
        $page = "Perhitungan Zakat Usaha";

        // Hitung total nilai stok barang dagangan (menggunakan harga jual)
        $total_stok_barang = Sparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)
            ->get()
            ->sum(function($item) {
                return $item->stok_sparepart * $item->harga_jual;
            });

        // Hitung total piutang (dari penjualan yang belum lunas)
        // Asumsi: status_penjualan = 0 berarti belum lunas
        // $total_piutang = penjualan::where('kode_owner', '=', $this->getThisUser()->id_upline)
        //     ->where('status_penjualan', '=', 0)
        //     ->sum('total_penjualan');

        // Hitung total kas (asumsi ada tabel kas atau bisa manual input)
        // Untuk sementara gunakan input manual
        $total_kas = $request->input('kas', 0);

        // Hitung total hutang
        $total_hutang = Hutang::where('kode_owner', '=', $this->getThisUser()->id_upline)
            ->where('status', '=', 1) // hutang yang masih aktif
            ->sum('total_hutang');

        // Hitung total aset kena zakat
        $total_aset_zakat = $total_stok_barang + $total_kas - $total_hutang;
        // $total_aset_zakat = $total_stok_barang + $total_piutang + $total_kas - $total_hutang;

        // Nisab (setara 85 gram emas - asumsi harga emas Rp 1,000,000/gram)
        $harga_emas_per_gram = $request->input('harga_emas', 1000000);
        $nisab = 85 * $harga_emas_per_gram;

        // Cek apakah wajib zakat
        $wajib_zakat = $total_aset_zakat >= $nisab;

        // Hitung zakat (2.5%)
        $jumlah_zakat = $wajib_zakat ? ($total_aset_zakat * 0.025) : 0;

        // Data detail untuk ditampilkan
        $data_zakat = [
            'total_stok_barang' => $total_stok_barang,
            // 'total_piutang' => $total_piutang,
            'total_kas' => $total_kas,
            'total_hutang' => $total_hutang,
            'total_aset_zakat' => $total_aset_zakat,
            'nisab' => $nisab,
            'harga_emas_per_gram' => $harga_emas_per_gram,
            'wajib_zakat' => $wajib_zakat,
            'jumlah_zakat' => $jumlah_zakat,
            'persentase_zakat' => 2.5
        ];

        // Data stok per kategori untuk detail
        $stok_per_kategori = Sparepart::join('kategori_spareparts', 'spareparts.kode_kategori', '=', 'kategori_spareparts.id')
            ->where('spareparts.kode_owner', '=', $this->getThisUser()->id_upline)
            ->selectRaw('kategori_spareparts.nama_kategori,
                        SUM(spareparts.stok_sparepart * spareparts.harga_jual) as total_nilai,
                        SUM(spareparts.stok_sparepart) as total_qty')
            ->groupBy('kategori_spareparts.id', 'kategori_spareparts.nama_kategori')
            ->get();

        $content = view('admin.page.zakat_usaha', compact(['data_zakat', 'stok_per_kategori']));
        return view('admin.layout.blank_page', compact(['page', 'content']));
    }

    public function update_data_zakat(Request $request)
    {
        // Method untuk update manual data kas dan harga emas
        return redirect()->back()->with('success', 'Data berhasil diupdate');
    }
}
