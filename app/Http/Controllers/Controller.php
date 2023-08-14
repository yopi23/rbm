<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    
    public function getThisUser(){
        if(Auth::check()){
            return User::join('user_details','user_details.kode_user','=','users.id')->where([['users.id','=',auth()->user()->id]])->get(['users.*','user_details.*','users.id as id_user'])->first();
        }
    }

}
