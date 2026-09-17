<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vacancy extends Model
{
    protected $fillable = [
        'title', 'description', 'requirements', 'department',
        'type', 'deadline', 'document_path', 'featured_image', 'is_active',
    ];

    protected $casts = [
        'deadline' => 'date',
        'is_active' => 'boolean',
    ];

    public function applications()
    {
        return $this->hasMany(VacancyApplication::class);
    }

    public function isExpired(): bool
    {
        return $this->deadline && $this->deadline->isPast();
    }

    public function scopeOpen($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('deadline')->orWhere('deadline', '>=', now()));
    }
}
