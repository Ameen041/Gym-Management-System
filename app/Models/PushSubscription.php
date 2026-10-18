<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A phone or browser that receives notifications for a user (Web Push). */
class PushSubscription extends Model
{
    protected $fillable = ['user_id', 'endpoint', 'endpoint_hash', 'public_key', 'auth_token'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
