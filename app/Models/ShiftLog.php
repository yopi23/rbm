<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper to record a shift action.
     */
    public static function record($actionType, $description, $relatedId = null, $relatedType = null, $amount = 0)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return null;
            }

            $activeShift = Shift::getActiveShift($user->id);
            if (!$activeShift) {
                return null;
            }

            return self::create([
                'shift_id'     => $activeShift->id,
                'user_id'      => $user->id,
                'action_type'  => $actionType,
                'description'  => $description,
                'related_id'   => $relatedId,
                'related_type' => $relatedType,
                'amount'       => $amount,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to create shift log: ' . $e->getMessage());
            return null;
        }
    }
}
