<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengeluaranOperasional;
use App\Models\PengeluaranToko;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\BebanOperasional;
use App\Models\Shift;
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
            // Get Active Shift
            $activeShift = Shift::getActiveShift(Auth::id());
            if (!$activeShift) {
                return back()->with('error', 'Shift belum dibuka. Silakan buka shift terlebih dahulu.');
            }
            $shiftId = $activeShift->id;

            $pengeluaran = PengeluaranToko::create([
                'tanggal_pengeluaran' => $request->tanggal_pengeluaran,
                'nama_pengeluaran' => $request->nama_pengeluaran,
                'jumlah_pengeluaran' => $request->jumlah_pengeluaran,
                'catatan_pengeluaran' => $request->catatan_pengeluaran,
                'kode_owner' => $this->getThisUser()->id_upline,
                'shift_id' => $shiftId,
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
        // Check Active Shift
        $activeShift = Shift::getActiveShift(Auth::id());
        if (!$activeShift) {
            return back()->with('error', 'Shift belum dibuka. Silakan buka shift terlebih dahulu.');
        }

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
        // Check Active Shift
        $activeShift = Shift::getActiveShift(Auth::id());
        if (!$activeShift) {
            return back()->with('error', 'Shift belum dibuka. Silakan buka shift terlebih dahulu.');
        }

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
        // Eager load relasi pegawai untuk efisiensi
        $data_opex = PengeluaranOperasional::with('pegawai')->where('kode_owner', $this->getThisUser()->id_upline)->latest()->get();
        foreach ($data_opex as $item) {
            $edit = Request::create(route('edit_pengeluaran_opex', $item->id));
            $delete = Request::create(route('delete_pengeluaran_opex', $item->id));

            // Perbaikan Bug: Gunakan '==' untuk perbandingan
            $kategori = (strtolower($item->kategori) == 'penggajian' && $item->pegawai) ? $item->kategori . ' (' . $item->pegawai->name . ')' : $item->kategori;

            $tbody .= '<tr>
                        <td>' . $no++ . '</td>
                        <td>' . $item->tgl_pengeluaran . '</td>
                        <td>' . $item->nama_pengeluaran . '</td>
                        <td>' . $kategori . '</td>
                        <td>' . $item->desc_pengeluaran . '</td>
                        <td>Rp.' . number_format($item->jml_pengeluaran) . ',-</td>
                        <td>
                        <form action="' . $delete->url() . '" onsubmit="return confirm(\'Apakah Anda yakin ?\')" method="POST">
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

        // Logika perhitungan sisa jatah disamakan dengan BebanOperasionalController
        $awalTahunIni = Carbon::now()->startOfYear();
        $daftarBeban = BebanOperasional::where('kode_owner', $this->getThisUser()->id_upline)
            ->with(['pengeluaranOperasional' => function ($query) use ($awalTahunIni) {
                $query->where('tgl_pengeluaran', '>=', $awalTahunIni);
            }])
            ->orderBy('nama_beban', 'asc')
            ->get();

        $awalBulanIni = Carbon::now()->startOfMonth();
        $akhirBulanIni = Carbon::now()->endOfMonth();

        $daftarBeban->map(function ($item) use ($awalBulanIni, $akhirBulanIni) {
            if ($item->periode == 'tahunan') {
                $pengeluaranPeriodeIni = $item->pengeluaranOperasional;
            } else {
                $pengeluaranPeriodeIni = $item->pengeluaranOperasional->whereBetween('tgl_pengeluaran', [$awalBulanIni, $akhirBulanIni]);
            }
            $item->terpakai_periode_ini = $pengeluaranPeriodeIni->sum('jml_pengeluaran');
            // Ganti 'jumlah_bulanan' menjadi 'nominal'
            $item->sisa_jatah = $item->nominal - $item->terpakai_periode_ini;
            return $item;
        });

        $selectedBebanId = $request->query('beban_id');
        return view('admin.forms.pengeluaran_opex', compact(['page', 'user', 'daftarBeban', 'selectedBebanId']));
    }

    // PERBAIKAN PADA FUNGSI EDIT
    public function edit_pengeluaran_opex(Request $request, $id)
    {
        $page = "Edit Pengeluaran Operasional";
        $user = User::join('user_details', 'users.id', '=', 'user_details.kode_user')->where([['user_details.jabatan', '!=', '0'], ['user_details.jabatan', '!=', '1'], ['user_details.status_user', '=', '1'], ['user_details.id_upline', '=', $this->getThisUser()->id_upline]])->get(['users.*']);
        $data = PengeluaranOperasional::findOrFail($id);

        // Logika perhitungan sisa jatah disamakan (seperti di create)
        $awalTahunIni = Carbon::now()->startOfYear();
        $daftarBeban = BebanOperasional::where('kode_owner', $this->getThisUser()->id_upline)
            ->with(['pengeluaranOperasional' => function ($query) use ($awalTahunIni, $id) {
                // Saat edit, jangan hitung pengeluaran yang sedang diedit
                $query->where('tgl_pengeluaran', '>=', $awalTahunIni)->where('id', '!=', $id);
            }])
            ->orderBy('nama_beban', 'asc')
            ->get();

        $awalBulanIni = Carbon::now()->startOfMonth();
        $akhirBulanIni = Carbon::now()->endOfMonth();

        $daftarBeban->map(function ($item) use ($awalBulanIni, $akhirBulanIni) {
            if ($item->periode == 'tahunan') {
                $pengeluaranPeriodeIni = $item->pengeluaranOperasional;
            } else {
                $pengeluaranPeriodeIni = $item->pengeluaranOperasional->whereBetween('tgl_pengeluaran', [$awalBulanIni, $akhirBulanIni]);
            }
            $item->terpakai_periode_ini = $pengeluaranPeriodeIni->sum('jml_pengeluaran');
            $item->sisa_jatah = $item->nominal - $item->terpakai_periode_ini;
            return $item;
        });

        $selectedBebanId = $data->beban_operasional_id;
        return view('admin.forms.pengeluaran_opex', compact(['page', 'user', 'data', 'daftarBeban', 'selectedBebanId']));
    }

    // PERBAIKAN PADA FUNGSI STORE
    public function store_pengeluaran_opex(Request $request)
    {
        $validatedData = $request->validate([
            'tgl_pengeluaran' => ['required', 'date'],
            'nama_pengeluaran' => ['required', 'string', 'max:255'],
            'beban_operasional_id' => ['nullable', 'exists:beban_operasional,id'],
            'kode_pegawai' => ['nullable', 'exists:users,id'],
            'jml_pengeluaran' => ['required', 'numeric', 'min:1'],
            'desc_pengeluaran' => ['nullable', 'string'],
        ]);

        if ($request->filled('beban_operasional_id')) {
            $beban = BebanOperasional::findOrFail($request->beban_operasional_id);
            $pengeluaranBaru = $request->jml_pengeluaran;

            if ($beban->periode == 'tahunan') {
                $awalPeriode = Carbon::parse($request->tgl_pengeluaran)->startOfYear();
                $akhirPeriode = Carbon::parse($request->tgl_pengeluaran)->endOfYear();
            } else {
                $awalPeriode = Carbon::parse($request->tgl_pengeluaran)->startOfMonth();
                $akhirPeriode = Carbon::parse($request->tgl_pengeluaran)->endOfMonth();
            }

            $sudahTerpakai = PengeluaranOperasional::where('beban_operasional_id', $beban->id)
                ->whereBetween('tgl_pengeluaran', [$awalPeriode, $akhirPeriode])
                ->sum('jml_pengeluaran');

            // Ganti 'jumlah_bulanan' menjadi 'nominal'
            $sisaJatah = $beban->nominal - $sudahTerpakai;

            if ($pengeluaranBaru > $sisaJatah) {
                $pesanError = "Gagal! Jumlah pengeluaran (Rp " . number_format($pengeluaranBaru) . ") melebihi sisa jatah untuk '" . $beban->nama_beban . "'. Sisa jatah: Rp " . number_format($sisaJatah) . ".";
                return back()->with('error', $pesanError)->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // Get Active Shift
            $activeShift = Shift::getActiveShift(Auth::id());
            if (!$activeShift) {
                return back()->with('error', 'Shift belum dibuka. Silakan buka shift terlebih dahulu.');
            }
            $shiftId = $activeShift->id;

            $validatedData['kategori'] = 'Lainnya'; // Default kategori
            if ($request->filled('beban_operasional_id')) {
                // Ambil nama kategori dari master beban
                $validatedData['kategori'] = BebanOperasional::find($request->beban_operasional_id)->nama_beban;
            }

            $validatedData['kode_owner'] = $this->getThisUser()->id_upline;
            $validatedData['kode_pegawai'] = $this->getThisUser()->kode_user;
            $validatedData['shift_id'] = $shiftId;

            $pengeluaran = PengeluaranOperasional::create($validatedData);

            // $this->catatKas(...) REMOVED: Ditangani oleh Observer untuk mencegah duplikasi dan handle update/delete

            DB::commit();
            return redirect()->route('pengeluaran_operasional')->with('success', 'Pengeluaran Operasional Berhasil Ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', "Oops, Terjadi Kesalahan: " . $e->getMessage());
        }
    }

    // PERBAIKAN PADA FUNGSI UPDATE
    public function update_pengeluaran_opex(Request $request, $id)
    {
        // Check Active Shift
        $activeShift = Shift::getActiveShift(Auth::id());
        if (!$activeShift) {
            return back()->with('error', 'Shift belum dibuka. Silakan buka shift terlebih dahulu.');
        }

        $validatedData = $request->validate([
            'tgl_pengeluaran' => ['required', 'date'],
            'nama_pengeluaran' => ['required', 'string', 'max:255'],
            'beban_operasional_id' => ['nullable', 'exists:beban_operasional,id'],
            'kode_pegawai' => ['nullable', 'exists:users,id'],
            'jml_pengeluaran' => ['required', 'numeric', 'min:1'],
            'desc_pengeluaran' => ['nullable', 'string'],
        ]);

        $pengeluaran = PengeluaranOperasional::where('kode_owner', $this->getThisUser()->id_upline)->findOrFail($id);

        // Validasi Sisa Jatah saat Update
        if ($request->filled('beban_operasional_id')) {
            $beban = BebanOperasional::findOrFail($request->beban_operasional_id);
            $pengeluaranBaru = $request->jml_pengeluaran;

            if ($beban->periode == 'tahunan') {
                $awalPeriode = Carbon::parse($request->tgl_pengeluaran)->startOfYear();
                $akhirPeriode = Carbon::parse($request->tgl_pengeluaran)->endOfYear();
            } else {
                $awalPeriode = Carbon::parse($request->tgl_pengeluaran)->startOfMonth();
                $akhirPeriode = Carbon::parse($request->tgl_pengeluaran)->endOfMonth();
            }
            // Hitung pemakaian lain tanpa menyertakan data yang sedang diedit
            $sudahTerpakai = PengeluaranOperasional::where('beban_operasional_id', $beban->id)
                ->where('id', '!=', $id)
                ->whereBetween('tgl_pengeluaran', [$awalPeriode, $akhirPeriode])
                ->sum('jml_pengeluaran');

            $sisaJatah = $beban->nominal - $sudahTerpakai;

            if ($pengeluaranBaru > $sisaJatah) {
                $pesanError = "Gagal! Jumlah pengeluaran (Rp " . number_format($pengeluaranBaru) . ") melebihi sisa jatah untuk '" . $beban->nama_beban . "'. Sisa jatah: Rp " . number_format($sisaJatah) . ".";
                return back()->with('error', $pesanError)->withInput();
            }
        }

        $validatedData['kategori'] = 'Lainnya';
        if ($request->filled('beban_operasional_id')) {
            $validatedData['kategori'] = BebanOperasional::find($request->beban_operasional_id)->nama_beban;
        }

        // Jika kategori bukan penggajian, pastikan kode_pegawai null
        if (strtolower($validatedData['kategori']) != 'penggajian') {
            $validatedData['kode_pegawai'] = null;
        }

        $pengeluaran->update($validatedData);

        return redirect()->route('pengeluaran_operasional')->with('success', 'Pengeluaran Operasional Berhasil Diperbarui');
    }

    public function delete_pengeluaran_opex(Request $request, $id)
    {
        // Check Active Shift
        $activeShift = Shift::getActiveShift(Auth::id());
        if (!$activeShift) {
            return back()->with('error', 'Shift belum dibuka. Silakan buka shift terlebih dahulu.');
        }

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
