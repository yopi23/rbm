<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HpSuggestions extends Model
{
    use HasFactory;
    protected $table = 'hp_suggestions';
    protected $fillable = ['brand', 'type', 'screen_size', 'camera_position', 'note', 'submitted_by', 'is_approved'];
}
