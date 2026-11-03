<?php

namespace App\Models\Card;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['name', 'code', 'credit_hours', 'theory_credit_hours', 'practical_credit_hours', 'has_practical', 'practical_code', 'is_active'];
    protected $casts = [
        'has_practical' => 'boolean',
        'is_active' => 'boolean',
        'theory_credit_hours' => 'decimal:2',
        'practical_credit_hours' => 'decimal:2',
    ];

    public function offerings()
    {
        return $this->hasMany(SubjectOffering::class);
    }
}
