<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    //
    public function login()
    {
        if (!Auth::check()) {
            return view('front.auth.signin');
        }
        return redirect()->back();
    }
    public function register()
    {
        if (!Auth::check()) {
            return view('front.auth.signup');
        }
        return redirect()->back();
    }
    public function authenticate(Request $request)
    {
        $credential = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required']
        ]);
        if (Auth::attempt($credential)) {

            $request->session()->regenerate();
            $cek = UserDetail::where([['kode_user', '=', auth()->user()->id]])->get(['status_user'])->first();
            if ($cek->status_user != '1') {
                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect('login')->with('error', "Akun kamu Belum aktif, Silahkan Hubungi Admin untuk Mengaktifkan Akunmu Kembali")->onlyInput('email');
            }
            return redirect()->intended('/dashboard');
        }
        return redirect('login')->with('error', "Email atau Password Salah")->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('login');
    }
    public function sign_up(Request $request)
    {
        $cek = UserDetail::where([['kode_invite', '=', $request->kode_invite]])->get()->first() ?? false;
        $id_upline = null;
        $jabatan = '1';
        if ($cek) {
            $id_upline = $cek->kode_user;
            $jabatan = $request->jabatan;
        }
        $create = User::create([
            'name' => $request->nama,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        if ($create) {
            $data_user = User::where([['name', '=', $request->nama], ['email', '=', $request->email]])->get()->first();
            if ($data_user->id != null || !empty($data_user->id)) {
                $kode_invite = 'INV' . $data_user->id . $data_user->jabatan . rand(500, 1000);
                $upline = $data_user->id;
                if ($cek) {
                    $upline = $id_upline;
                    $kode_invite = '-';
                }
                UserDetail::create([
                    'kode_user' => $data_user->id,
                    'foto_user' => '-',
                    'fullname' => $data_user->name,
                    'alamat_user' => '',
                    'no_telp' => '-',
                    'jabatan' => $jabatan,
                    'id_upline' =>  $upline,
                    'status_user' => '0',
                    'kode_invite' => $kode_invite,
                    'link_twitter' => '-',
                    'link_facebook' => '-',
                    'link_instagram' => '-',
                    'link_linkedin' => '-',
                ]);
            }
            return redirect()->back()
                ->with([
                    'success' => 'Daftar Berhasil, Silahkan Konfirmasi Admin Untuk Mengaktifkan Akun'
                ]);
            return redirect()->back()->with('error', "Oops, Something Went Wrong");
        }
    }
}
