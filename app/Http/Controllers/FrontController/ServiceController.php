<?php

namespace App\Http\Controllers\FrontController;

use App\Http\Controllers\Controller;
use App\Models\DetailPartLuarService;
use App\Models\DetailPartServices;
use App\Models\Garansi;
use App\Models\Sevices;
use App\Models\User;
use Illuminate\Http\Request;
use Milon\Barcode\Facades\DNS1DFacade;

class ServiceController extends Controller
{
    //
    public function cek_service(Request $request){
        $result = null;
        $data = Sevices::where([['kode_service','=',$request->q]])->get()->first();
        if($data){
            $teknisi = $data->id_teknisi != null || $data->id_teknisi != '' ? User::where([['id','=',$data->id_teknisi]])->get(['name'])->first()->name : '-';
            $qr = DNS1DFacade::getBarcodeHTML($data->kode_service, "C39" ,1,100);
            $garansi = Garansi::where([['kode_garansi','=',$request->q],['type_garansi','=','service']])->get();
            $detail = DetailPartServices::join('spareparts','detail_part_services.kode_sparepart','=','spareparts.id')->where([['detail_part_services.kode_services','=',$data->id]])->get(['detail_part_services.id as id_detail_part','detail_part_services.*','spareparts.*']);
            $detail_luar = DetailPartLuarService::where([['kode_services','=',$data->id]])->get();
            $result = [
                'qr'=>$qr,
                'teknisi' => $teknisi,
                'data' => $data,
                'garansi' => $garansi,
                'detail' => $detail,
                'detail_luar' => $detail_luar,
            ];
        }
       
        return $result;
    }
    public function cek_garansi(Request $request){
        $result = null;
        $data = Garansi::where([['kode_garansi','=',$request->q]])->get();
        if($data){
            $qr = DNS1DFacade::getBarcodeHTML($request->q, "C39" ,1,100);
            $result = [
                'qr' => $qr,
                'data' => $data
            ];
        }
        
        return $result;
    }
    public function index(Request $request){
        $data = null;
        if(isset($request->q)){
           switch($request->type_search){
            case 'service':
                $data = $this->cek_service($request);
                break;
            case 'garansi':
                $data = $this->cek_garansi($request);
                break;
           }
        }
        return view('front.service',compact(['data','request']));
    }
}
