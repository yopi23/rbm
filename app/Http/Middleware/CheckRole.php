<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $this_user = User::join('user_details', 'user_details.kode_user', '=', 'users.id')
            ->where('users.id', auth()->user()->id)
            ->first(['users.*', 'user_details.*', 'users.id as id_user']);

        if (in_array($this_user->jabatan, $roles)) {
            return $next($request);
        }

        return redirect()->back();
    }
}
