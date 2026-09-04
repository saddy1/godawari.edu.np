<?php

namespace App\Models\TeachingLearning;

use App\Models\Card\Organization;
use Illuminate\Database\Eloquent\Model;

class RoutineRoom extends Model
{
    protected $fillable = ['organization_id', 'name', 'code', 'type', 'capacity', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function organization() { return $this->belongsTo(Organization::class); }
    public function lessonGroups() { return $this->hasMany(RoutineLessonGroup::class); }
}
