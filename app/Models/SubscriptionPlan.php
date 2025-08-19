<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;
    protected $table = 'subscription_plans';

    protected $fillable = [
        'name',
        'price',
        'duration_in_months',
    ];
     public function subscriptions()
    {
        return $this->hasMany(Subscription::class, 'subscription_plan_id');
    }
}
