<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\Sparepart;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AjaxRequestController extends Controller
{
    //
    public function getHiddenItemForm($method = 'POST')
    {
        $result = '' . csrf_field() . '';
        $result .= '' . method_field($method) . '';
        return $result;
    }
    public function search_sparepart(Request $request)
    {
        if ($request->ajax()) {
            $output = "";
            if ($request->search) {
                $data = DB::table('spareparts')->where([['nama_sparepart', 'LIKE', '%' . $request->search . '%'], ['kode_owner', '=', $this->getThisUser()->id_upline]])->get();
                if ($data) {
                    $no = 1;
                    foreach ($data as $item) {
                        $act = $item->stok_sparepart > 0 ? '<form action="' . route('store_sparepart_toko') . '" method="POST">
                        ' . $this->getHiddenItemForm('POST') . '
                        <div class="row">
                            <div class="col-md-6">
                                <input type="number" name="qty_part" id="qty_part" class="form-control" min="1" max="' . $item->stok_sparepart . '" value="1">
                            </div>
                            <div class="col-md-6">
                            <input type="hidden" name="kode_services" id="kode_services" value="' . $request->kode_service . '">
                            <input type="hidden" name="kode_sparepart" id="kode_sparepart" value="' . $item->id . '">
                            <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                        </form>' : '<span class="badge badge-danger">Stok Kosong</span';
                        $output .= '
                                        <tr>
                                            <td>' . $no++ . '</td>
                                            <td>' . $item->nama_sparepart . '</td>
                                            <td>Rp.' . number_format($item->harga_jual) . '</td>
                                            <td>Rp.' . number_format($item->harga_jual + $item->harga_pasang) . '</td>
                                            <td>' . $item->stok_sparepart . '</td>
                                            <td>
                                            ' . $act . '
                                            </td>
                                        </tr>
                                    ';
                    }
                }
            } else {
                $output .= '<tr>
                            <td class="text-center text-muted" colspan="100%">Data Tidak Ditemukan</td>
                        </tr>';
            }
            return Response($output);
        }
    }
    public function search_kode_invite(Request $request)
    {
        if ($request->ajax()) {
            if ($request->search) {
                $data = UserDetail::where([['kode_invite', '=', $request->search]])->get()->first();
                if ($data) {
                    return Response('<p class="text-success text-muted">Kode Invite Ditemukkan <br> <b>Owner<b><br> ' . $data->fullname . '</p>
                                    <div class="form-group">
                                            <select name="jabatan" id="jabatan" class="form-control">
                                            <option value="2"> Kasir </option>
                                            <option value="3"> Teknisi </option>
                                            </select>
                                        </div><br>');
                } else {
                    return Response('<p class="text-danger text-muted">Kode Invite Tidak Ditemukkan</p>');
                }
            }
        }
    }
}
