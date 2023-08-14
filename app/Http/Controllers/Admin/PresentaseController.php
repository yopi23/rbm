<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PresentaseUser;
use App\Models\User;
use Illuminate\Http\Request;

class PresentaseController extends Controller
{
    //
    public function index(){
        $page = 'Persentase';
        $data = User::join('user_details','users.id','=','user_details.kode_user')->where([['user_details.id_upline','=',$this->getThisUser()->id_upline],['user_details.jabatan','!=','0'],['user_details.jabatan','!=','1']])->get(['users.id as id_user','users.*','user_details.*']);
        $persentase = PresentaseUser::latest()->get();
        $content = view('admin.page.persentase',compact(['data','persentase']));
        return view('admin.layout.blank_page',compact(['page','content']));
    }
    public function store_or_update(Request $request){
        $data = PresentaseUser::where([['kode_user','=',$request->kode_user]])->get()->first();
        if($data){
            $data->update([
                'presentase' => $request->presentase,
            ]);
            if($data){
                return redirect()->back();
            }
        }else{
            $create = PresentaseUser::create([
                'presentase' => $request->presentase,
                'kode_user' => $request->kode_user,
            ]);
            if($create){
                return redirect()->back();
            }
        }
    }
}
