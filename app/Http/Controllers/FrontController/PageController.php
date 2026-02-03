<?php

namespace App\Http\Controllers\FrontController;

use App\Http\Controllers\Controller;
use App\Models\Handphone;
use App\Models\Sevices;
use App\Models\Sparepart;
use App\Models\User;
use Illuminate\Http\Request;

class PageController extends Controller
{
    //
    public function index(Request $request)
    {
        $datateam = User::join('user_details', 'user_details.kode_user', '=', 'users.id')->where([
            ['user_details.status_user', '=', '1'], ['user_details.jabatan', '!=', '0']
        ])->get(['users.*', 'user_details.*', 'users.id as id_user']);
        $produk = Handphone::count();
        $sparepart = Sparepart::count();
        $service = Sevices::count();
        return view('front.index', compact(['datateam', 'service', 'sparepart', 'produk']));
    }
    public function view_service(Request $request)
    {
        return view('front.service');
    }
    public function view_sparepart(Request $request)
    {
        return view('front.sparepart');
    }
    public function view_produk(Request $request)
    {
        return view('front.produk');
    }
}
