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
use App\Models\SparepartRusak;
use App\Models\Supplier;
use App\Models\Order;
use App\Models\DetailOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Milon\Barcode\Facades\DNS1DFacade;

class SparePartController extends Controller
{


    public function getTable($thead = '<th>No</th><th>Aksi</th>', $tbody = '')
    {
        $result = '<div class="table-responsive"><table class="table" id="dataTable">';
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
        $thead = '<th width="5%">No</th>
                    <th>Image</th>
                    <th>Kode Sparepart</th>
                    <th>Nama Sparepart</th>
                    <th>Harga Beli</th>
                    <th>Harga Jual</th>
                    <th>Harga Pasang</th>
                    <th>Stok</th>
                    <th>Aksi</th>';
        $sparepart = Sparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        $tbody = '';
        $no = 1;
        foreach ($sparepart as $item) {
            $edit = Request::create(route('EditSparepart', $item->id));
            $delete = Request::create(route('DeleteSparepart', $item->id));
            $foto = '<img src="' . asset('public/img/no_image.png') . '" width="100%" height="100%" class="img" id="view-img">';
            if ($item->foto_sparepart != '-') {
                $foto =  '<img src="' . asset('public/uploads/' . $item->foto_sparepart) . '" class="img" width="100%" height="100%">';
            }
            $qr = DNS1DFacade::getBarcodeHTML($item->kode_sparepart, "C39", 1, 100);
            $tbody .= '<tr>
                            <td>' . $no++ . '</td>
                            <td>' . $foto . '</td>
                            <td>' . $item->kode_sparepart . '</td>
                            <td>' . $item->nama_sparepart . '</td>
                            <td>Rp.' . number_format($item->harga_beli) . '</td>
                            <td>Rp.' . number_format($item->harga_jual) . '</td>
                            <td>Rp.' . number_format($item->harga_pasang) . '</td>
                            <td>' . $item->stok_sparepart . '</td>
                            <td>
                                <form action="' . $delete->url() . '" onsubmit="' . "return confirm('Apakah Anda yakin ?')" . '" method="POST">
                                    ' . $this->getHiddenItemForm('DELETE') . '
                                    <a href="' . $edit->url() . '" class="btn btn-sm btn-warning my-2"><i class="fas fa-edit"></i></a>
                                    <button type="submit" class="btn btn-sm btn-danger my-2"><i class="fas fa-trash"></i></button>
                                </form>
                                <a href="' . route('Barcode_barang', $item->id) . '" target="_blank" class="btn btn-sm btn-primary mt-2"><i class="fas fa-print"></i></a>
                            </td>
                            </tr>';
        }
        $data = $this->getTable($thead, $tbody);
        return view('admin.layout.card_layout', compact(['page', 'data', 'link_tambah']));
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
        return view('admin.forms.sparepart', compact(['page', 'kategori']));
    }
    public function create_kategori(Request $request)
    {
        $page = "Tambah Kategori Sparepart";
        return view('admin.forms.kategori_sparepart', compact(['page']));
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
                'stok_sparepart' => $request->stok_sparepart,
                'harga_beli' => $request->harga_beli,
                'harga_jual' => $request->harga_jual,
                'harga_pasang' => $request->harga_pasang,
                'kode_owner' => $this->getThisUser()->id_upline
            ]);
            if ($create) {
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
                    ->with([
                        'success' => 'Kategori Sparepart Berhasil Ditambahkan'
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
        $data = Sparepart::findOrFail($id);
        return view('admin.forms.sparepart', compact(['page', 'data', 'kategori']));
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
                'stok_sparepart' => $request->stok_sparepart,
                'harga_beli' => $request->harga_beli,
                'harga_jual' => $request->harga_jual,
                'harga_pasang' => $request->harga_pasang,
            ]);
            if ($update) {
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



        $content = view('admin.page.list_order', compact(['orders', 'activeSpls', 'selectedSplId']));
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
            'tgl_retur_barang' => $request->tgl_retur_barang,
            'kode_barang' => $request->kode_barang,
            'kode_supplier' => $request->kode_supplier,
            'jumlah_retur' => $request->jumlah_retur,
            'catatan_retur' => $request->catatan_retur != null ? $request->catatan_retur : '-',
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
}
