<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next,$role)
    {
        if(!Auth::check()){
            return route('login');
        }
        $this_user = User::join('user_details','user_details.kode_user','=','users.id')->where([['users.id','=',auth()->user()->id]])->get(['users.*','user_details.*','users.id as id_user'])->first();
        if($this_user->jabatan == $role){
            return $next($request);
        }
        return redirect()->back();
        
    }
}
