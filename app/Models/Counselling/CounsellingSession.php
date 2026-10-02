<?php

namespace App\Models\Counselling;

use App\Models\Card\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CounsellingSession extends Model
{
    protected $fillable = [
        'student_id', 'counsellor_id', 'requested_by', 'created_by',
        'topic', 'status', 'scheduled_at', 'report', 'completed_at',
    ];
    protected $casts = ['scheduled_at' => 'datetime', 'completed_at' => 'datetime'];

    // The private report column is deliberately NOT hidden here — hiding it
    // would make it invisible everywhere including to an authorized viewer.
    // Every controller/view that touches this model must itself check
    // canViewReport() before reading or rendering `report`.
    public function student() { return $this->belongsTo(Student::class); }
    public function counsellor() { return $this->belongsTo(User::class, 'counsellor_id'); }
    public function requestedBy() { return $this->belongsTo(User::class, 'requested_by'); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }

    public function canViewReport(User $user): bool
    {
        return $user->isSuperAdmin() || (int) $this->counsellor_id === (int) $user->id;
    }
}
