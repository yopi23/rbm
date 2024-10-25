<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengeluaranOperasional;
use App\Models\PengeluaranToko;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use App\Traits\KategoriLaciTrait;

class PengeluaranController extends Controller
{
    use KategoriLaciTrait;
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
        $val = $this->validate($request, [
            'tanggal_pengeluaran' => ['required']
        ]);
        if ($val) {
            $create = PengeluaranToko::create([
                'tanggal_pengeluaran' => $request->tanggal_pengeluaran,
                'nama_pengeluaran' => $request->nama_pengeluaran,
                'jumlah_pengeluaran' => $request->jumlah_pengeluaran,
                'catatan_pengeluaran' => $request->catatan_pengeluaran,
                'kode_owner' => $this->getThisUser()->id_upline
            ]);
            // laci
            // Misalnya, ambil kategori dari request
            $kategoriId = $request->input('id_kategorilaci');
            $uangKeluar = $request->input('jumlah_pengeluaran');
            $keterangan = $request->input('nama_pengeluaran') . "-" . $request->input('catatan_pengeluaran');

            // Catat histori laci
            $this->recordLaciHistory($kategoriId,  null, $uangKeluar, $keterangan);
            //end laci

            if ($create) {
                return redirect()->back()->with([
                    'success' => 'Pengeluaran Toko Berhasil Ditambahkan'
                ]);
                // return redirect()->route('pengeluaran_toko')
                //     ->with([
                //         'success' => 'Pengeluaran Toko Berhasil Ditambahkan'
                //     ]);
            }
            // return redirect()->route('pengeluaran_toko')->with('error', "Oops, Something Went Wrong");
            return redirect()->route('/');
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
        return view('admin.forms.pengeluaran_opex', compact(['page', 'user']));
    }
    public function edit_pengeluaran_opex(Request $request, $id)
    {
        $page = "Edit Pengeluaran Operasional";
        $user = User::join('user_details', 'users.id', '=', 'user_details.kode_user')->where([['user_details.jabatan', '!=', '0'], ['user_details.jabatan', '!=', '1'], ['user_details.status_user', '=', '1'], ['user_details.id_upline', '=', $this->getThisUser()->id_upline]])->get(['users.*']);
        $data = PengeluaranOperasional::findOrFail($id);
        return view('admin.forms.pengeluaran_opex', compact(['page', 'user', 'data']));
    }
    public function store_pengeluaran_opex(Request $request)
    {
        $validate = $request->validate([
            'tgl_pengeluaran' => ['required'],
            'nama_pengeluaran' => ['required'],
            'kategori' => ['required'],
        ]);
        if ($validate) {
            $pegawai = $request->kategori == 'Penggajian' ? $request->kode_pegawai : '-';
            $create = PengeluaranOperasional::create([
                'tgl_pengeluaran' => $request->tgl_pengeluaran,
                'nama_pengeluaran' => $request->nama_pengeluaran,
                'kategori' => $request->kategori,
                'kode_pegawai' => $pegawai,
                'jml_pengeluaran' => $request->jml_pengeluaran,
                'desc_pengeluaran' => $request->desc_pengeluaran != null ? $request->desc_pengeluaran : '',
                'kode_owner' => $this->getThisUser()->id_upline
            ]);
            if ($create) {
                if ($pegawai != '-') {
                    $pegawais = UserDetail::where([['kode_user', '=', $pegawai]])->get()->first();
                    $new_saldo = $pegawais->saldo + $request->jml_pengeluaran;
                    $pegawais->update([
                        'saldo' => $new_saldo
                    ]);
                }
                return redirect()->route('pengeluaran_operasional')
                    ->with([
                        'success' => 'Pengeluaran Operasional Berhasil Ditambahkan'
                    ]);
            }
            return redirect()->back()->with('error', "Oops, Something Went Wrong");
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
