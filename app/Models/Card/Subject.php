<?php

namespace App\Models\Card;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['name', 'code', 'credit_hours', 'has_practical', 'is_active'];
    protected $casts = [
        'has_practical' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function offerings()
    {
        return $this->hasMany(SubjectOffering::class);
    }
}
