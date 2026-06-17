<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CabangScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $detail = \App\Models\UserDetail::where('kode_user', $user->id)->first();
            
            // Jika user bukan owner (yaitu jabatan '2'/Kasir atau '3'/Teknisi), batasi query ke cabang mereka
            if ($detail && $detail->jabatan != '1') {
                $builder->where($model->getTable() . '.cabang_id', $user->cabang_id);
            }
        }
    }
}
