<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacultyGroup extends Model
{
    protected $fillable = [
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function members()
    {
        return $this->hasMany(Faculty::class)->orderBy('order')->orderBy('name');
    }

    public function activeMembers()
    {
        return $this->members()->where('is_active', true);
    }
}
