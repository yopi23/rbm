<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'user_id',
        'action',
        'description',
        'performed_by_user_id',
    ];

    /**
     * Log ini milik langganan yang mana.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
    public function user(): BelongsTo
    {
        // Relasi ini akan otomatis terhubung ke kolom 'user_id'
        return $this->belongsTo(User::class);
    }

    /**
     * Aksi ini dilakukan oleh siapa (bisa sistem/NULL).
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }
}
