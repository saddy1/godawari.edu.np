<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeyPerson extends Model
{
    protected $table = 'key_persons';

    protected $fillable = [
        'name',
        'designation',
        'phone',
        'email',
        'photo',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getPhotoUrlAttribute(): string
    {
        if ($this->photo) {
            return asset($this->photo);
        }

        // Default avatar SVG as data URI
        return 'data:image/svg+xml;utf8,' . rawurlencode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">'
            . '<rect width="100" height="100" fill="#1a5632"/>'
            . '<circle cx="50" cy="38" r="18" fill="rgba(255,255,255,0.8)"/>'
            . '<ellipse cx="50" cy="85" rx="28" ry="20" fill="rgba(255,255,255,0.8)"/>'
            . '</svg>'
        );
    }
}
