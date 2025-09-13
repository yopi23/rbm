<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengeluaranOperasional;
use App\Models\PengeluaranToko;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\BebanOperasional;
use Illuminate\Http\Request;
use App\Traits\KategoriLaciTrait;
use App\Traits\ManajemenKasTrait; // 1. Impor Trait
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PengeluaranController extends Controller
{
    use KategoriLaciTrait;
    use ManajemenKasTrait;
    //
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
    public function view_toko(Request $request)
    {
        $page = "Pengeluaran Toko";
        $link_tambah = route('create_pengeluaran_toko');
        $thead = '<th width="5%">No</th>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>Catatan</th>
                    <th>Harga</th>
                    <th>Aksi</th>';
        $tbody = '';
        $no = 1;
        $data_pengeluaran = PengeluaranToko::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        foreach ($data_pengeluaran as $item) {
            $edit = Request::create(route('edit_pengeluaran_toko', $item->id));
            $delete = Request::create(route('delete_pengeluaran_toko', $item->id));
            $tbody .= '<tr>
                        <td>' . $no++ . '</td>
                        <td>' . $item->tanggal_pengeluaran . '</td>
                        <td>' . $item->nama_pengeluaran . '</td>
                        <td>' . $item->catatan_pengeluaran . '</td>
                        <td>Rp.' . number_format($item->jumlah_pengeluaran) . ',-</td>
                        <td>  <form action="' . $delete->url() . '" onsubmit="' . "return confirm('Apakah Anda yakin ?')" . '" method="POST">
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

    public function create_pengeluaran_toko(Request  $request)
    {

        $listLaci = $this->getKategoriLaci();
        $page = "Tambah Pengeluaran Toko";
        return view('admin.forms.pengeluaran_toko', compact(['page', 'listLaci']));
    }
    public function edit_pengeluaran_toko(Request  $request, $id)
    {
        $page = "Edit Pengeluaran";
        $data = PengeluaranToko::findOrFail($id);
        return view('admin.forms.pengeluaran_toko', compact(['page', 'data']));
    }
    public function store_pengeluaran_toko(Request  $request)
    {
        $request->validate(['tanggal_pengeluaran' => 'required']);

        DB::beginTransaction();
        try {
            $pengeluaran = PengeluaranToko::create([
                'tanggal_pengeluaran' => $request->tanggal_pengeluaran,
                'nama_pengeluaran' => $request->nama_pengeluaran,
                'jumlah_pengeluaran' => $request->jumlah_pengeluaran,
                'catatan_pengeluaran' => $request->catatan_pengeluaran,
                'kode_owner' => $this->getThisUser()->id_upline
            ]);
            $this->catatKas(
                $pengeluaran, 0, $pengeluaran->jumlah_pengeluaran,
                'Pengeluaran Toko: ' . $pengeluaran->nama_pengeluaran,
                $pengeluaran->tanggal_pengeluaran
            );
            // laci
            // Misalnya, ambil kategori dari request
            $kategoriId = $request->input('id_kategorilaci');
            $uangKeluar = $request->input('jumlah_pengeluaran');
            $keterangan = $request->input('nama_pengeluaran') . "-" . $request->input('catatan_pengeluaran');

            // Catat histori laci
            $this->recordLaciHistory($kategoriId,  null, $uangKeluar, $keterangan);
            //end laci

             DB::commit();
            return redirect()->route('pengeluaran_toko')->with('success', 'Pengeluaran Toko Berhasil Ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', "Oops, Terjadi Kesalahan: " . $e->getMessage());
        }
    }
    public function update_pengeluaran_toko(Request  $request, $id)
    {
        $val = $this->validate($request, [
            'tanggal_pengeluaran' => ['required']
        ]);
        if ($val) {
            $update = PengeluaranToko::findOrFail($id);
            $update->update([
                'tanggal_pengeluaran' => $request->tanggal_pengeluaran,
                'nama_pengeluaran' => $request->nama_pengeluaran,
                'jumlah_pengeluaran' => $request->jumlah_pengeluaran,
                'catatan_pengeluaran' => $request->catatan_pengeluaran,
            ]);
            if ($update) {
                return redirect()->route('pengeluaran_toko')
                    ->with([
                        'success' => 'Pengeluaran Toko Berhasil DiEdit'
                    ]);
            }
            return redirect()->route('pengeluaran_toko')->with('error', "Oops, Something Went Wrong");
        }
    }
    public function delete_pengeluaran_toko(Request  $request, $id)
    {
        $data = PengeluaranToko::findOrFail($id);
        $data->delete();
        if ($data) {
            return redirect()->route('pengeluaran_toko')
                ->with([
                    'success' => 'Pengeluaran Toko Berhasil Dihapus'
                ]);
        }
        return redirect()->route('pengeluaran_toko')->with('error', "Oops, Something Went Wrong");
    }

    public function view_operasional(Request $request)
    {
        $page = "Pengeluaran Operasional";
        $link_tambah = route('create_pengeluaran_opex');
        $thead = '<th width="5%">No</th>
                    <th>Tanggal</th>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th>Catatan</th>
                    <th>Jumlah</th>
                    <th>Aksi</th>';
        $tbody = '';
        $no = 1;
        $data_opex = PengeluaranOperasional::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        foreach ($data_opex as $item) {
            $edit = Request::create(route('edit_pengeluaran_opex', $item->id));
            $delete = Request::create(route('delete_pengeluaran_opex', $item->id));
            $kategori = $item->kategori;
            if ($item->kategori = 'Penggajian') {
                $data_user = User::where('id', '=', $item->kode_pegawai)->get()->first();
                $kategori = $item->kategori . ' (' . $data_user->name . ')';
            }
            $tbody .= '<tr>
                        <td>' . $no++ . '</td>
                        <td>' . $item->tgl_pengeluaran . '</td>
                        <td>' . $item->nama_pengeluaran . '</td>
                        <td>' . $kategori . '</td>
                        <td>' . $item->desc_pengeluaran . '</td>
                        <td>Rp.' . number_format($item->jml_pengeluaran) . ',-</td>
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
    public function create_pengeluaran_opex(Request $request)
    {
        $page = "Tambah Pengeluaran Operasional";
        $user = User::join('user_details', 'users.id', '=', 'user_details.kode_user')->where([['user_details.jabatan', '!=', '0'], ['user_details.jabatan', '!=', '1'], ['user_details.status_user', '=', '1'], ['user_details.id_upline', '=', $this->getThisUser()->id_upline]])->get(['users.*']);

        $awalBulan = Carbon::now()->startOfMonth();
        $akhirBulan = Carbon::now()->endOfMonth();

        $daftarBeban = BebanOperasional::where('kode_owner', $this->getThisUser()->id_upline)
            ->with(['pengeluaranOperasional' => function ($query) use ($awalBulan, $akhirBulan) {
                $query->whereBetween('tgl_pengeluaran', [$awalBulan, $akhirBulan]);
            }])
            ->orderBy('nama_beban', 'asc')
            ->get();
        // GANTI INI: Ambil ID dan nama beban dari BebanOperasional
        $daftarBeban->map(function ($item) {
            $terpakai = $item->pengeluaranOperasional->sum('jml_pengeluaran');
            $item->sisa_jatah = $item->jumlah_bulanan - $terpakai;
            return $item;
        });

        // Ambil ID beban yang dipilih dari URL (jika ada)
        $selectedBebanId = $request->query('beban_id');

        return view('admin.forms.pengeluaran_opex', compact(['page', 'user', 'daftarBeban', 'selectedBebanId']));
    }
    public function edit_pengeluaran_opex(Request $request, $id)
    {
        $page = "Edit Pengeluaran Operasional";
        $user = User::join('user_details', 'users.id', '=', 'user_details.kode_user')->where([['user_details.jabatan', '!=', '0'], ['user_details.jabatan', '!=', '1'], ['user_details.status_user', '=', '1'], ['user_details.id_upline', '=', $this->getThisUser()->id_upline]])->get(['users.*']);
        $data = PengeluaranOperasional::findOrFail($id);

        $awalBulan = Carbon::now()->startOfMonth();
        $akhirBulan = Carbon::now()->endOfMonth();

        $daftarBeban = BebanOperasional::where('kode_owner', $this->getThisUser()->id_upline)
            ->with(['pengeluaranOperasional' => function ($query) use ($awalBulan, $akhirBulan) {
                $query->whereBetween('tgl_pengeluaran', [$awalBulan, $akhirBulan]);
            }])
            ->orderBy('nama_beban', 'asc')
            ->get();
        // GANTI INI: Ambil ID dan nama beban dari BebanOperasional
        $daftarBeban->map(function ($item) {
            $terpakai = $item->pengeluaranOperasional->sum('jml_pengeluaran');
            $item->sisa_jatah = $item->jumlah_bulanan - $terpakai;
            return $item;
        });

        $selectedBebanId = $data->beban_operasional_id; // Ambil dari data yang diedit

        return view('admin.forms.pengeluaran_opex', compact(['page', 'user', 'data', 'daftarBeban', 'selectedBebanId']));
    }

    public function store_pengeluaran_opex(Request $request)
    {
        $request->validate([
            'tgl_pengeluaran' => ['required'],
            'nama_pengeluaran' => ['required'],
            // 'kategori' tidak wajib lagi jika memilih dari daftar
            'jml_pengeluaran' => 'required|numeric|min:1', // Minimal 1
        ]);

        if ($request->filled('beban_operasional_id')) {
        $beban = BebanOperasional::findOrFail($request->beban_operasional_id);
        $jatahBulanan = $beban->jumlah_bulanan;
        $pengeluaranBaru = $request->jml_pengeluaran;

        // Hitung total yang sudah terpakai bulan ini untuk beban tersebut
        $awalBulan = Carbon::parse($request->tgl_pengeluaran)->startOfMonth();
        $akhirBulan = Carbon::parse($request->tgl_pengeluaran)->endOfMonth();

        $sudahTerpakai = PengeluaranOperasional::where('beban_operasional_id', $beban->id)
            ->whereBetween('tgl_pengeluaran', [$awalBulan, $akhirBulan])
            ->sum('jml_pengeluaran');

        $sisaJatah = $jatahBulanan - $sudahTerpakai;

        // Jika pengeluaran baru lebih besar dari sisa jatah, tolak!
        if ($pengeluaranBaru > $sisaJatah) {
            $pesanError = "Gagal! Jumlah pengeluaran (Rp " . number_format($pengeluaranBaru) . ") melebihi sisa jatah untuk '" . $beban->nama_beban . "' bulan ini. Sisa jatah Anda adalah Rp " . number_format($sisaJatah) . ".";

            // Kembali ke halaman form dengan pesan error dan input sebelumnya
            return back()->with('error', $pesanError)->withInput();
        }
    }

        DB::beginTransaction();
        try {
            $pegawai = $this->getThisUser()->kode_user ? $request->kode_pegawai : '-';

            // Logika baru untuk menentukan kategori
            $kategoriNama = $request->kategori;
            if ($request->filled('beban_operasional_id')) {
                $beban = BebanOperasional::find($request->beban_operasional_id);
                if ($beban) {
                    $kategoriNama = $beban->nama_beban;
                }
            }

            $pengeluaran = PengeluaranOperasional::create([
                'tgl_pengeluaran' => $request->tgl_pengeluaran,
                'nama_pengeluaran' => $request->nama_pengeluaran,
                'kategori' => $kategoriNama, // Simpan nama kategori
                'beban_operasional_id' => $request->beban_operasional_id, // Simpan ID relasi
                'kode_pegawai' => $this->getThisUser()->kode_user,
                'jml_pengeluaran' => $request->jml_pengeluaran,
                'desc_pengeluaran' => $request->desc_pengeluaran ?? '',
                'kode_owner' => $this->getThisUser()->id_upline
            ]);

            // Catatan: Logika penambahan saldo ke pegawai di sini mungkin perlu direvisi.
            // Seharusnya pembayaran gaji dicatat sebagai pengeluaran, bukan penambahan saldo.
            // Namun, untuk integrasi kas, kita catat sebagai pengeluaran.

            // 3. Catat di Buku Besar
            $this->catatKas(
                $pengeluaran, 0, $pengeluaran->jml_pengeluaran,
                'Pengeluaran Opex: ' . $pengeluaran->nama_pengeluaran,
                $pengeluaran->tgl_pengeluaran
            );

            DB::commit();
            return redirect()->route('pengeluaran_operasional')->with('success', 'Pengeluaran Operasional Berhasil Ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', "Oops, Terjadi Kesalahan: " . $e->getMessage());
        }
    }

    public function update_pengeluaran_opex(Request $request, $id)
    {
        $validate = $request->validate([
            'tgl_pengeluaran' => ['required'],
            'nama_pengeluaran' => ['required'],
            'kategori' => ['required'],
        ]);
        if ($validate) {
            $update = PengeluaranOperasional::findOrFail($id);
            $pegawai = $request->kategori == 'Penggajian' ? $request->kode_pegawai : '-';
            $update->update([
                'tgl_pengeluaran' => $request->tgl_pengeluaran,
                'nama_pengeluaran' => $request->nama_pengeluaran,
                'kategori' => $request->kategori,
                'kode_pegawai' => $pegawai,
                'jml_pengeluaran' => $request->jml_pengeluaran,
                'desc_pengeluaran' => $request->desc_pengeluaran != null ? $request->desc_pengeluaran : '',
            ]);
            if ($update) {
                return redirect()->route('pengeluaran_operasional')
                    ->with([
                        'success' => 'Pengeluaran Operasional Berhasil DiEdit'
                    ]);
            }
            return redirect()->back()->with('error', "Oops, Something Went Wrong");
        }
    }
    public function delete_pengeluaran_opex(Request $request, $id)
    {
        $data = PengeluaranOperasional::findOrFail($id);
        $data->delete();
        if ($data) {
            return redirect()->route('pengeluaran_operasional')
                ->with([
                    'success' => 'Pengeluaran Operasional Berhasil Dihapus'
                ]);
        }
        return redirect()->route('pengeluaran_operasional')->with('error', "Oops, Something Went Wrong");
    }
}
