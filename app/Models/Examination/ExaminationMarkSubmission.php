<?php

namespace App\Models\Examination;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ExaminationMarkSubmission extends Model
{
    protected $fillable = [
        'examination_subject_id', 'teacher_id', 'section_ids', 'locked_at',
        'unlock_requested_at', 'unlock_reason', 'unlocked_at', 'unlocked_by',
    ];

    protected $casts = [
        'section_ids' => 'array', 'locked_at' => 'datetime',
        'unlock_requested_at' => 'datetime', 'unlocked_at' => 'datetime',
    ];

    public function examinationSubject() { return $this->belongsTo(ExaminationSubject::class); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id'); }
    public function unlockedBy() { return $this->belongsTo(User::class, 'unlocked_by'); }
    public function getIsLockedAttribute(): bool { return $this->locked_at !== null; }
    public function getUnlockIsPendingAttribute(): bool { return $this->is_locked && $this->unlock_requested_at !== null; }
}
