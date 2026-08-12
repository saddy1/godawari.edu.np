<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryNotification extends Model
{
    protected $fillable = [
        'user_id', 'title', 'message', 'type', 'loan_id', 'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function loan()
    {
        return $this->belongsTo(LibraryLoan::class);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }
}
