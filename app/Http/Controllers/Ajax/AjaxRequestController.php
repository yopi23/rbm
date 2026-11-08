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
    public function search_sparepart(Request $request)
    {
        if ($request->ajax()) {
            $data = [];
            if ($request->search) {
                $data = DB::table('spareparts')->where([['nama_sparepart', 'LIKE', '%' . $request->search . '%'], ['kode_owner', '=', $this->getThisUser()->id_upline]])->get();
            }
            $output = view('admin.tables.partials.ajax_search_sparepart', [
                'data' => $data,
                'kode_service' => $request->kode_service
            ])->render();
            return response($output);
        }
    }
    public function search_kode_invite(Request $request)
    {
        if ($request->ajax()) {
            if ($request->search) {
                $data = UserDetail::where([['kode_invite', '=', $request->search]])->first();
                if ($data) {
                    $fullname = htmlspecialchars($data->fullname, ENT_QUOTES, 'UTF-8');
                    return response('<p class="text-success text-muted">Kode Invite Ditemukan <br> <b>Owner</b><br> ' . $fullname . '</p>
                                    <div class="form-group">
                                            <select name="jabatan" id="jabatan" class="form-control">
                                            <option value="2"> Kasir </option>
                                            <option value="3"> Teknisi </option>
                                            </select>
                                        </div><br>');
                } else {
                    return response('<p class="text-danger text-muted">Kode Invite Tidak Ditemukan</p>');
                }
            }
        }
    }
}
