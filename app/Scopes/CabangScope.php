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

            // Untuk Sparepart (master katalog barang toko), semua HP di bawah owner yang sama
            // harus bisa mencari barang yang didata dari HP manapun (baik yang cabang_id = null maupun cabang toko).
            if ($model instanceof \App\Models\Sparepart) {
                return;
            }

            // Jika user bukan owner (yaitu jabatan '2'/Kasir atau '3'/Teknisi), batasi query ke cabang mereka
            // Pastikan user memiliki cabang_id, dan tetap sertakan data yang cabang_id-nya NULL (data global toko/owner)
            if ($detail && $detail->jabatan != '1' && !empty($user->cabang_id)) {
                $table = $model->getTable();
                $builder->where(function ($q) use ($table, $user) {
                    $q->where($table . '.cabang_id', $user->cabang_id)
                      ->orWhereNull($table . '.cabang_id');
                });
            }
        }
    }
}
