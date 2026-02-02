<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Penarikan;
use App\Models\PresentaseUser;
use App\Models\ProfitPresentase;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\Shift;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use App\Traits\ManajemenKasTrait;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use ManajemenKasTrait;
    //
    public function view_profile(Request $request)
    {
        $page = "Profile";

        $penarikan = Penarikan::join('users', 'penarikans.kode_user', '=', 'users.id')->where([['penarikans.kode_owner', '=', $this->getThisUser()->id_upline]])->get(['penarikans.id as id_penarikan', 'penarikans.*', 'users.*']);
        if ($this->getThisUser()->jabatan != '1') {
            $penarikan = Penarikan::join('users', 'penarikans.kode_user', '=', 'users.id')->where([['penarikans.kode_owner', '=', $this->getThisUser()->id_upline], ['penarikans.kode_user', '=', auth()->user()->id]])->get(['penarikans.id as id_penarikan', 'penarikans.*', 'users.*']);
        }
        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-t');

        $data = UserDetail::where([['kode_user', '=', auth()->user()->id]])->get()->first();
        $persentase = PresentaseUser::where([['kode_user', '=', auth()->user()->id]])->get()->first();
        $data_komisi = ProfitPresentase::where([
            ['kode_user', '=', auth()->user()->id],
            ['created_at', '>=', $startOfMonth . ' 00:00:00'],
            ['created_at', '<=', $endOfMonth . ' 23:59:59']
        ])->get();
        $komisi = 0;
        foreach ($data_komisi as $k) {
            $komisi += $k->profit;
        }

        // Jika pengguna adalah owner/upline atau admin, ambil daftar karyawan
        $employees = [];
        if ($this->getThisUser()->jabatan == '1' || $this->getThisUser()->id == 1) {
            $employees = UserDetail::where('id_upline', $this->getThisUser()->id)->get();
            foreach ($employees as $employee) {
                $employee->saldo = $employee->saldo;
                $employee->total_penarikan = Penarikan::where([
                    ['kode_user', $employee->id],
                    ['created_at', '>=', now()->startOfMonth()],
                    ['created_at', '<=', now()->endOfMonth()]
                ])->sum('jumlah_penarikan');

                // Ambil riwayat komisi karyawan
                $employee->riwayat_komisi = ProfitPresentase::where([
                    ['kode_user', '=', $employee->id],
                    ['created_at', '>=', now()->startOfMonth()],
                    ['created_at', '<=', now()->endOfMonth()]
                ])->get();

                // Hitung total komisi untuk karyawan tertentu
                $employee->total_komisi = $employee->riwayat_komisi->sum('profit');
            }
        }

        $content = view('admin.page.profile', compact(['penarikan', 'persentase', 'komisi', 'employees']));
        return view('admin.layout.blank_page', compact(['page', 'content', 'data']));
    }
    public function update_profile(Request $request, $id)
    {
        $validate = $request->validate([
            'name' => ['required'],
            'email' => ['required'],
        ]);
        if ($validate) {
            $update = User::findOrFail($id);
            $update->update([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password != null ? Hash::make($request->password) : $update->password,
            ]);
            if ($update) {
                $update1 = UserDetail::where([['kode_user', '=', $id]])->get()->first();

                $file = $request->file('foto_user');
                $foto = $file != null ? date('Ymdhis') . $file->getClientOriginalName() : $update1->foto_user;
                if ($file != null) {
                    if ($update1->foto_user != '-') {
                        File::delete(public_path('uploads/' . $update1->foto_user));
                    }
                    $file->move('public/uploads/', $foto);
                }
                $update1->update([
                    'foto_user' => $foto,
                    'fullname' => $request->name,
                    'alamat_user' => $request->alamat_user != null ? $request->alamat_user : '-',
                    'no_telp' => $request->no_telp != null ? $request->no_telp : '0',
                    'link_twitter' => $request->link_twitter,
                    'link_facebook' => $request->link_facebook,
                    'link_instagram' => $request->link_instagram,
                    'link_linkedin' => $request->link_instagram,
                ]);
                if ($update1) {
                    return redirect()->back()
                        ->with([
                            'success' => 'Pengguna Berhasil DiUpdate'
                        ]);
                }
                return redirect()->back()->with('error', "Oops, Something Went Wrong");
            }
        }
    }
    public function store_penarikan(Request $request)
    {
        $user = $this->getThisUser();
        // Menghapus karakter non-angka (kecuali tanda desimal jika dibutuhkan)
        $jumlahPenarikan = preg_replace('/[^0-9.]/', '', $request->jumlah_penarikan);

        // Jika nilai hasil preg_replace masih kosong atau bukan angka, kembalikan error
        if (!is_numeric($jumlahPenarikan) || $jumlahPenarikan <= 0) {
            return redirect()->back()->with([
                'error' => 'Jumlah penarikan tidak valid'
            ]);
        }
        if ($user->saldo < $jumlahPenarikan) {
            return redirect()->back()->with([
                'error' => 'Oops, saldo anda tidak mencukupi'
            ]);
        }

        $count = Penarikan::latest()->get()->count();
        $kode = 'PEN' . date('Ymd') . $this->getThisUser()->id_upline . $this->getThisUser()->kode_user;
        $create = Penarikan::create([
            'tgl_penarikan' => date('Y-m-d h:i:s'),
            'kode_penarikan' => $kode,
            'kode_user' => $this->getThisUser()->kode_user,
            'kode_owner' => $this->getThisUser()->id_upline,
            'jumlah_penarikan' => $jumlahPenarikan,
            'catatan_penarikan' => $request->catatan_penarikan != null ? $request->catatan_penarikan : '-',
            'status_penarikan' => '1',
            'dari_saldo' => $user->saldo,
            'shift_id' => Shift::getActiveShift(auth()->user()->id)->id ?? null,
        ]);
        if ($create) {
            $data = Penarikan::where([['kode_penarikan', '=', $kode]])->get()->first();
            $pegawais = UserDetail::where([['kode_user', '=', $data->kode_user]])->get()->first();

            if ($jumlahPenarikan <= 0 || $pegawais->saldo < $jumlahPenarikan) {
                return redirect()->back()->with([
                    'error' => 'Oops, saldo anda tidak cukup'
                ]);
            }
            $new_saldo = $pegawais->saldo - $jumlahPenarikan;
            $pegawais->update([
                'saldo' => $new_saldo
            ]);

            $this->catatKas(
                $create,                                     // Model sumber
                0,                                           // Debit
                $jumlahPenarikan,                            // Kredit (uang keluar dari kas perusahaan)
                'Penarikan Saldo Teknisi: ' . $pegawais->fullname, // Deskripsi
                now()                                        // Tanggal
            );

            DB::commit();

            // Status WhatsApp notification
$whatsappStatus = 'Pesan WhatsApp tidak dikirim: Nomor telepon tidak tersedia';

// Dapatkan admin dari upline
$admin = UserDetail::where([['kode_user', '=', $pegawais->id_upline]])->get()->first();

// Array untuk menyimpan nomor telepon valid
$validPhoneNumbers = [];

// Inject WhatsAppService
$whatsAppService = app(WhatsAppService::class);

// Cek nomor admin
if (!empty($admin->no_telp) && $whatsAppService->isValidPhoneNumber($admin->no_telp)) {
    $validPhoneNumbers[] = $admin->no_telp;
}

// Cek nomor owner/recipient kedua
if (!empty($pegawais->no_telp) && $whatsAppService->isValidPhoneNumber($pegawais->no_telp)) {
    $validPhoneNumbers[] = $pegawais->no_telp;
}

// Kirim notifikasi WhatsApp jika ada nomor telepon valid
if (count($validPhoneNumbers) > 0) {
    try {
        // Kirim notifikasi ke semua nomor valid sekaligus
        $waResult = $whatsAppService->penarikanNotification([
            'teknisi' => $pegawais->fullname,
            'jumlah' => 'Rp ' . number_format($jumlahPenarikan, 0, ',', '.'),
            'catatan' => $request->catatan_penarikan != null ? $request->catatan_penarikan : '-',
            'no_hp' => $validPhoneNumbers,
        ]);

        if ($waResult['status']) {
            $whatsappStatus = 'Pesan WhatsApp berhasil dikirim ke semua penerima';
        } else {
            // Cek apakah sebagian berhasil
            $successCount = count(array_filter($waResult['details'], function($detail) {
                return $detail['status'] === true;
            }));

            if ($successCount > 0) {
                $whatsappStatus = "Pesan WhatsApp berhasil dikirim ke {$successCount} dari " . count($validPhoneNumbers) . " penerima";
            } else {
                $whatsappStatus = 'Pesan WhatsApp gagal dikirim: ' . $waResult['message'];
            }
        }
    } catch (\Exception $waException) {
        // Log error tapi jangan batalkan transaksi utama
        \Log::error("Failed to send WhatsApp notification: " . $waException->getMessage(), [
            'penarikan' => $pegawais->fullname,
            'recipients' => $validPhoneNumbers,
            'exception' => $waException
        ]);

        $whatsappStatus = 'Pesan WhatsApp gagal dikirim: Terjadi kesalahan sistem';
    }
} else {
    $whatsappStatus = 'Pesan WhatsApp tidak dikirim: Tidak ada nomor telepon valid';
}

            return redirect()->route('profile')->with([
                'success' => 'Penarikan Berhasil Di Buat'
            ]);

        }
        return redirect()->back()->with([
            'error' => 'Oops, Something Went Wrong'
        ]);
    }

    public function delete_penarikan(Request $request, $id)
    {
        $data = Penarikan::findOrFail($id);
        $data->delete();
        if ($data) {
            $pegawais = UserDetail::where([['kode_user', '=', $data->kode_user]])->get()->first();
            $new_saldo = $pegawais->saldo + $data->jumlah_penarikan;
            $pegawais->update([
                'saldo' => $new_saldo
            ]);
            return redirect()->route('profile')->with([
                'success' => 'Penarikan Berhasil Di Hapus'
            ]);
        }
        return redirect()->back()->with([
            'error' => 'Oops, Something Went Wrong'
        ]);
    }
    public function update_penarikan(Request $request, $id)
    {
        $data = Penarikan::findOrFail($id);
        $data->update([
            'jumlah_penarikan' => $request->jumlah_penarikan,
            'catatan_penarikan' => $request->catatan_penarikan != null ? $request->catatan_penarikan : '-',
            'status_penarikan' => $request->status_penarikan,
        ]);
        if ($data) {
            if ($request->status_penarikan == '2') {
                $pegawais = UserDetail::where([['kode_user', '=', $data->kode_user]])->get()->first();
                $new_saldo = $pegawais->saldo + $request->jumlah_penarikan;
                $pegawais->update([
                    'saldo' => $new_saldo
                ]);
            }
            return redirect()->route('profile')->with([
                'success' => 'Penarikan Berhasil Di Update'
            ]);
        }
        return redirect()->back()->with([
            'error' => 'Oops, Something Went Wrong'
        ]);
    }
    public function updateAllStatuses()
    {
        $penarikans = Penarikan::where('status_penarikan', 0)->get();

        foreach ($penarikans as $penarikan) {
            $penarikan->update(['status_penarikan' => 1]);
        }

        return redirect()->route('profile')->with(['success' => 'Semua status penarikan berhasil diperbarui']);
    }
}
