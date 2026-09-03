<?php

namespace App\Models\TeachingLearning;

use Illuminate\Database\Eloquent\Model;

class RoutinePeriod extends Model
{
    protected $fillable = ['routine_shift_id', 'position', 'name', 'starts_at', 'ends_at', 'is_break'];
    protected $casts = ['is_break' => 'boolean'];

    public function shift()
    {
        return $this->belongsTo(RoutineShift::class, 'routine_shift_id');
    }
}
