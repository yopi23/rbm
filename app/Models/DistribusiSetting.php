<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistribusiSetting extends Model
{
    use HasFactory;
    protected $table = 'distribusi_setting';
    protected $fillable = ['role', 'persentase', 'keterangan', 'kode_owner'];
}
