<?php

namespace App\Traits;

use App\Models\UserDetail;
use Illuminate\Support\Facades\Auth;

/**
 * Trait HasOwnerAccess
 * 
 * Trait terstandardisasi untuk mendapatkan Owner ID dari user yang sedang login.
 * Menggantikan duplikasi method getOwnerId() di banyak controller.
 * 
 * LOGIC:
 *  - Jabatan '1' (Owner) → return user ID sendiri
 *  - Jabatan lainnya (Karyawan) → return id_upline (atasan/owner)
 */
trait HasOwnerAccess
{
    /**
     * Get standardized Owner ID.
     * Owner (jabatan=1) → returns own user ID
     * Employee (jabatan!=1) → returns their upline's user ID
     */
    protected function getOwnerId(): int
    {
        $user = Auth::user();
        $detail = $user->userDetail;

        if ($detail && $detail->jabatan == '1') {
            return $user->id;
        }

        return $detail ? $detail->id_upline : $user->id;
    }

    /**
     * Get kode_owner for API controllers that resolve via auth:sanctum.
     * Same logic as getOwnerId() but with null safety for API context.
     */
    protected function getKodeOwner(): ?int
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        $detail = UserDetail::where('kode_user', $user->id)->first();
        if (!$detail) {
            return $user->id;
        }

        return ($detail->jabatan == '1') ? $user->id : $detail->id_upline;
    }
}
