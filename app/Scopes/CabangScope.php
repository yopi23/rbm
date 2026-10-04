<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CabangScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $detail = \App\Models\UserDetail::where('kode_user', $user->id)->first();
            
            // Jika user bukan owner (yaitu jabatan '2'/Kasir atau '3'/Teknisi), batasi query ke cabang mereka
            // TAMBAHAN: Pastikan user memiliki cabang_id. Jika null, tidak difilter by cabang agar datanya tetap muncul (sudah dilindungi filter kode_owner di controller)
            if ($detail && $detail->jabatan != '1' && !empty($user->cabang_id)) {
                $builder->where($model->getTable() . '.cabang_id', $user->cabang_id);
            }
        }
    }
}
