<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceRememberToken extends Model
{
    protected $fillable = ['user_id', 'token', 'user_agent', 'expires_at'];
    protected $casts = ['expires_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
