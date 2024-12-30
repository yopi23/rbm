<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Catatan;
use App\Models\DetailPartServices;
use App\Models\ListOrder;
use App\Models\PemasukkanLain;
use App\Models\PengeluaranOperasional;
use App\Models\PengeluaranToko;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use App\Models\Sevices as modelServices;
use App\Models\Sparepart;
use App\Models\DetailBarangPenjualan;
use App\Models\DetailSparepartPenjualan;
use App\Models\Supplier;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\Penarikan;
use App\Models\Laci;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use App\Traits\KategoriLaciTrait;
use Symfony\Component\Translation\Dumper\YamlFileDumper;

class DashboardController extends Controller
{
    use KategoriLaciTrait;
    //
    public function index(Request $request)
    {
        $user = $this->getThisUser(); // Pastikan metode ini mengambil pengguna yang sedang login

        // Ambil detail pengguna dari tabel userdetail
        $userDetail = UserDetail::where('kode_user', $user->kode_user)->first();

        // Cek jabatan pengguna
        if (!$userDetail || !in_array($userDetail->jabatan, [1, 2])) {
            // Alihkan pengguna ke halaman lain jika jabatan bukan 0, 1, atau 2
            return redirect()->route('profile')->with('error', 'Anda tidak memiliki akses ke dashboard.');
        }
        // endpengalihan
        $page = "Dashboard";
        $listLaci = $this->getKategoriLaci();
        $pengambilanKode = $this->getOrCreatePengambilan();
        if ($this->getThisUser()->jabatan != '0') {
            // Penarikan

            $penarikanQuery = Penarikan::join('users', 'penarikans.kode_user', '=', 'users.id')
                ->where('penarikans.kode_owner', '=', $this->getThisUser()->id_upline)
                ->where('penarikans.created_at', '>=', now()->subMinutes(30)); // Memfilter data dalam satu jam terakhir

            $penarikan = $penarikanQuery->orderBy('penarikans.created_at', 'desc')
                ->take(2)
                ->get(['penarikans.id as id_penarikan', 'penarikans.*', 'users.name']);
            // dd($penarikan);
            // End Penarikan

            $service = modelServices::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
            $penjualan = Penjualan::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
            $sparepart = Sparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
            $kode_service = 'SV' . date('Ymd') . rand(500, 1000) . $service->count();
            $catatan = Catatan::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
            $pemasukkan_lain = PemasukkanLain::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
            $supplier = Supplier::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
            $list_order = ListOrder::where('list_orders.kode_owner', '=', $this->getThisUser()->id_upline)
                ->get();
            $p1 = PengeluaranToko::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
            $p2 = PengeluaranOperasional::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
            $total_service = 0;
            $total_penjualan = 0;
            $total_pemasukkan_lain = 0;
            $total_pengeluaran = 0;
            foreach ($service as $item) {
                if ($item->updated_at->isToday()) {
                    if ($item->status_services == 'Diambil') {
                        $total_service += $item->total_biaya;
                    } else {
                        $total_service += $item->dp;
                    }
                }
            }
            foreach ($penjualan as $item) {
                if ($item->tgl_penjualan == date('Y-m-d') && $item->status_penjualan == '1') {
                    $total_penjualan += $item->total_penjualan;
                }
            }
            foreach ($pemasukkan_lain as $item) {
                if ($item->tgl_pemasukkan == date('Y-m-d')) {
                    $total_pemasukkan_lain += $item->jumlah_pemasukkan;
                }
            }
            foreach ($p1 as $item) {
                if ($item->tanggal_pengeluaran == date('Y-m-d')) {
                    $total_pengeluaran += $item->jumlah_pengeluaran;
                }
            }
            foreach ($p2 as $item) {
                if ($item->tgl_pengeluaran == date('Y-m-d')) {
                    $total_pengeluaran += $item->jml_pengeluaran;
                }
            }
            $today = date('Y-m-d');
            $hasLaciEntry = Laci::where('kode_owner', $this->getThisUser()->id_upline)
                ->whereDate('tanggal', $today)
                ->whereNotNull('receh')
                ->exists();
            $laciData = Laci::where('tanggal', $today)->get();


            $totalPenarikan = Penarikan::join('user_details', 'penarikans.kode_user', '=', 'user_details.kode_user')
                ->where([
                    ['penarikans.status_penarikan', '=', '1'],
                    ['penarikans.kode_owner', '=', $this->getThisUser()->id_upline],
                ])
                ->whereDate('penarikans.created_at', '=', $today)
                ->sum('penarikans.jumlah_penarikan');

            $debit = $total_pengeluaran + $totalPenarikan;
            // Hitung total receh jika diperlukan
            $toReceh = $laciData->sum('receh');
            $sumreal = $laciData->sum('real');
            $totalReceh = $toReceh + $total_service + $total_penjualan + $total_pemasukkan_lain - $debit;

            // kode trx
            $kodetrx = Penjualan::where([['user_input', '=', auth()->user()->id], ['kode_owner', '=', $this->getThisUser()->id_upline], ['status_penjualan', '=', '0']])->get()->first();
            $count = Penjualan::where([['user_input', '=', auth()->user()->id], ['kode_owner', '=', $this->getThisUser()->id_upline]])->get()->count();
            if (!$kodetrx) {
                $kode = 'TRX' . date('Ymd') . auth()->user()->id . $count;
                $create = Penjualan::create([
                    'kode_penjualan' => $kode,
                    'kode_owner' => $this->getThisUser()->id_upline,
                    'nama_customer' => '-',
                    'catatan_customer' => '',
                    'total_bayar' => '0',
                    'total_penjualan' => '0',
                    'user_input' => auth()->user()->id,
                    'status_penjualan' => '0',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
                if ($create) {
                    $kodetrx = Penjualan::where([['user_input', '=', auth()->user()->id], ['kode_owner', '=', $this->getThisUser()->id_upline], ['status_penjualan', '=', '0']])->get()->first();
                }
            }
            $detailbarang = DetailBarangPenjualan::join('handphones', 'detail_barang_penjualans.kode_barang', '=', 'handphones.id')->where([['detail_barang_penjualans.kode_penjualan', '=', $kodetrx->id]])->get(['detail_barang_penjualans.id as id_detail', 'detail_barang_penjualans.*', 'handphones.*']);
            $detailsparepart = DetailSparepartPenjualan::join('spareparts', 'detail_sparepart_penjualans.kode_sparepart', '=', 'spareparts.id')->where([['detail_sparepart_penjualans.kode_penjualan', '=', $kodetrx->id]])->get(['detail_sparepart_penjualans.id as id_detail', 'detail_sparepart_penjualans.*', 'spareparts.*']);
            // kode trx
            //pengambilan
            // Dapatkan atau buat pengambilan


            if ($pengambilanKode) {
                // Ambil layanan berdasarkan pengambilan ID
                $pengambilanServices = $this->getServices($pengambilanKode->id);

                // Lakukan sesuatu dengan $services
                // return response()->json($pengambilanServices);
            }
            $done_service = $pengambilanServices['done_service'];
            // return response()->json(['message' => 'Pengambilan tidak ditemukan.'], 404);
            //end pengambilan
            $jab = [1, 2];
            $user = UserDetail::where('id_upline', $this->getThisUser()->id_upline)
                ->whereNotIn('jabatan', $jab)
                ->get();

            $isModalRequired = !$hasLaciEntry;
            return view('admin.index', compact([
                'total_pengeluaran',
                'list_order',
                'total_pemasukkan_lain',
                'total_service',
                'total_penjualan',
                'pemasukkan_lain',
                'page',
                'kode_service',
                'sparepart',
                'catatan',
                'service',
                'penjualan',
                'supplier',
                'penarikan',
                'isModalRequired',
                'totalReceh',
                'sumreal',
                'debit',
                'listLaci',
                'kodetrx',
                'detailsparepart',
                'detailbarang',
                'pengambilanKode',
                'pengambilanServices',
                'done_service',
                'user'
            ]));
        }
        if ($this->getThisUser()->jabatan == '0') {
            $data = User::join('user_details', 'users.id', '=', 'user_details.kode_user')->where('user_details.jabatan', '=', '1')->get(['users.*', 'user_details.*', 'users.id as id_user']);
            return view('admin.index', compact(['page', 'data']));
        }
    }
    public function create_catatan(Request $request)
    {
        $create = Catatan::create([
            'tgl_catatan' => $request->tgl_catatan,
            'judul_catatan' => $request->judul_catatan,
            'catatan' => $request->catatan,
            'kode_owner' => $this->getThisUser()->id_upline
        ]);
        if ($create) {
            return redirect()->back()->with('success', 'Tambah Catatan Berhasil');
        }
        return redirect()->back()->with('success', 'Tambah Catatan Gagal, Ada Kendala Teknis');
    }
    public function delete_catatan(Request $request, $id)
    {
        $data = Catatan::findOrFail($id);
        $data->delete();
        if ($data) {
            return redirect()->back()->with('success', 'Delete Catatan Berhasil');
        }
        return redirect()->back()->with('success', 'Delete Catatan Gagal, Ada Kendala Teknis');
    }
    public function create_list_order(Request $request)
    {
        $create = ListOrder::create([
            'tgl_order' => $request->tgl_order,
            'nama_order' => $request->nama_order,
            'catatan_order' => $request->catatan_order,
            'user_input' => auth()->user()->id,
            'kode_owner' => $this->getThisUser()->id_upline
        ]);
        if ($create) {
            return redirect()->back()->with('success', 'Tambah List Order Berhasil');
        }
        return redirect()->back()->with('success', 'Tambah Catatan Gagal, Ada Kendala Teknis');
    }
    public function delete_list_order(Request $request, $id)
    {
        $data = ListOrder::findOrFail($id);
        $data->delete();
        if ($data) {
            return redirect()->back()->with('success', 'Delete List Order Berhasil');
        }
        return redirect()->back()->with('success', 'Delete List Order Gagal, Ada Kendala Teknis');
    }
    public function create_pemasukkan_lain(Request $request)
    {
        $validate = $request->validate([
            'tgl_pemasukan' => ['required'],
            'jumlah_pemasukan' => ['required'],
        ]);

        if ($validate) {
            $create = PemasukkanLain::create([
                'tgl_pemasukkan' => $request->tgl_pemasukan,
                'judul_pemasukan' => $request->judul_pemasukan,
                'catatan_pemasukkan' => $request->catatan_pemasukan,
                'jumlah_pemasukkan' => $request->jumlah_pemasukan,
                'kode_owner' => $this->getThisUser()->id_upline
            ]);

            // laci
            // Misalnya, ambil kategori dari request
            $kategoriId = $request->input('id_kategorilaci');
            $uangMasuk = $request->input('jumlah_pemasukan');
            $keterangan = $request->input('judul_pemasukan') . "-" . $request->input('catatan_pemasukan');

            // Catat histori laci
            $this->recordLaciHistory($kategoriId, $uangMasuk, null, $keterangan);
            //end laci

            if ($create) {
                return redirect()->back()->with('success', 'Tambah Pemasukkan Berhasil');
            }
            return redirect()->back()->with('error', 'Tambah Pemasukkan Gagal, Ada Kendala Teknis');
        }
    }
    public function delete_pemasukkan_lain(Request $request, $id)
    {
        $data = PemasukkanLain::findOrFail($id);
        $data->delete();
        if ($data) {
            return redirect()->back()->with('success', 'Pemasukkan Lain Berhasil Dihapus');
        }
    }
    public function create_service(Request $request)
    {
        $validate = $request->validate([
            'kode_service' => ['required'],
            'tgl_service' => ['required'],
        ]);
        if ($validate) {
            // Cek apakah kode service sudah ada dalam database
            $existingService = modelServices::where('kode_service', $request->kode_service)->first();

            if ($existingService) {
                return redirect()->back()->with('error', 'Kode Service sudah ada, pilih kode yang berbeda.');
            }
            $create = modelServices::create([
                'kode_service' => $request->kode_service,
                'tgl_service' => $request->tgl_service,
                'nama_pelanggan' => $request->nama_pelanggan,
                'no_telp' => $request->no_telp,
                'type_unit' => $request->type_unit,
                'keterangan' => $request->ket,
                'total_biaya' => $request->biaya_servis,
                'dp' => $request->dp,
                'status_services' => 'Antri',
                'kode_owner' => $this->getThisUser()->id_upline
            ]);

            if ($create) {
                if ($request->kode_sparepart != null) {
                    $data_service = modelServices::where([['kode_service', '=', $request->kode_service]])->get()->first();
                    for ($i = 0; $i < count($request->kode_sparepart); $i++) {
                        if ($request['kode_sparepart'][$i] != null) {
                            $update_sparepart = Sparepart::findOrFail($request['kode_sparepart'][$i]);
                            DetailPartServices::create([
                                'kode_services' => $data_service->id,
                                'kode_sparepart' => $request['kode_sparepart'][$i],
                                'detail_modal_part_service' => $update_sparepart->harga_beli,
                                'detail_harga_part_service' => $update_sparepart->harga_jual,
                                'qty_part' => $request['qty_kode_sparepart'][$i],
                                'user_input' => auth()->user()->id,
                            ]);
                            $stok_baru = $update_sparepart->stok_sparepart - $request['qty_kode_sparepart'][$i];
                            $update_sparepart->update([
                                'stok_sparepart' => $stok_baru,
                            ]);
                        }
                    }
                }

                return redirect()->back()->with('success', 'Tambah Service Berhasil');
            }
            return redirect()->back()->with('error', 'Tambah Service Gagal, Ada Kendala Teknis');
        }
    }
    public function create_service_api(Request $request)
    {
        // Validasi input tanpa 'kode_service'
        $validate = $request->validate([
            'tgl_service' => ['nullable'],
            'nama_pelanggan' => ['nullable', 'string'],
            'no_telp' => ['nullable', 'string'],
            'type_unit' => ['nullable', 'string'],
            'ket' => ['nullable', 'string'],
            'biaya_servis' => ['nullable', 'numeric'],
            'dp' => ['nullable', 'numeric'],
            'kode_sparepart' => ['nullable', 'array'],
            'qty_kode_sparepart' => ['nullable', 'array'],
        ]);

        if ($validate) {
            // Generate kode_service otomatis
            $kode_service = $this->generateKodeService();

            // Simpan data service dengan kode_service yang dihasilkan
            $create = modelServices::create([
                'kode_service' => $kode_service,
                'tgl_service' => $request->tgl_service ?: Carbon::now()->format('Y-m-d'),
                'nama_pelanggan' => $request->nama_pelanggan,
                'no_telp' => $request->no_telp,
                'type_unit' => $request->type_unit,
                'keterangan' => $request->ket,
                'total_biaya' => $request->biaya_servis,
                'dp' => $request->dp,
                'status_services' => 'Antri',
                'kode_owner' => 2
            ]);

            if ($create) {
                if ($request->kode_sparepart != null) {
                    $data_service = modelServices::where([['kode_service', '=', $kode_service]])->first();

                    for ($i = 0; $i < count($request->kode_sparepart); $i++) {
                        if ($request['kode_sparepart'][$i] != null) {
                            $update_sparepart = Sparepart::findOrFail($request['kode_sparepart'][$i]);
                            DetailPartServices::create([
                                'kode_services' => $data_service->id,
                                'kode_sparepart' => $request['kode_sparepart'][$i],
                                'detail_modal_part_service' => $update_sparepart->harga_beli,
                                'detail_harga_part_service' => $update_sparepart->harga_jual,
                                'qty_part' => $request['qty_kode_sparepart'][$i],
                                'user_input' => 1,
                            ]);

                            $stok_baru = $update_sparepart->stok_sparepart - $request['qty_kode_sparepart'][$i];
                            $update_sparepart->update([
                                'stok_sparepart' => $stok_baru,
                            ]);
                        }
                    }
                }

                return response()->json([
                    'status' => 'success',
                    'message' => 'Tambah Service Berhasil',
                ], 200);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Tambah Service Gagal, Ada Kendala Teknis',
            ], 500);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Validasi Gagal',
        ], 400);
    }

    private function generateKodeService()
    {
        // Format tanggal hari ini
        $date = date('Ymd'); // YYYYMMDD

        // Generate nomor acak dalam rentang 000 hingga 999
        $randomNumber = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);

        // Pastikan nomor acak tersebut belum ada dalam database untuk hari yang sama
        while (modelServices::where('kode_service', 'like', $date . $randomNumber . '%')->exists()) {
            $randomNumber = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        }

        // Format kode service
        return 'SV' . $date . $randomNumber;
    }
    public function delete_detail_sparepart(Request $request, $id)
    {
        $data = DetailSparepartPenjualan::findOrFail($id);
        $data->delete();
        if ($data) {
            $update = Sparepart::findOrFail($data->kode_sparepart);
            $stok_baru = $update->stok_sparepart + $data->qty_sparepart;
            $update->update([
                'stok_sparepart' => $stok_baru,
            ]);
        }
    }
    public function getDetail($id)
    {
        $servicesData = $this->getServices($id); // Memanggil fungsi di trait
        return response()->json($servicesData);
    }
    // API
    public function get_pending_services(Request $request)
    {
        // Ambil data service dengan status 'Antri'
        $services = modelServices::where([
            ['kode_owner', '=', $this->getThisUser()->id_upline],
            ['status_services', '=', 'Antri'],
        ])->latest()->get();

        // Cek apakah ada data
        if ($services->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Tidak ada data service yang antri.',
                'data' => [],
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data service yang antri berhasil diambil.',
            'data' => $services,
        ], 200);
    }
}
