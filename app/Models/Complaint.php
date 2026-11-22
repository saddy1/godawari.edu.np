<?php

namespace App\Models;

use App\Models\Card\Student;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = [
        'student_id', 'category', 'subject', 'message',
        'status', 'admin_response', 'responded_by', 'responded_at',
    ];
    protected $casts = ['responded_at' => 'datetime'];

    public const CATEGORIES = [
        'academic' => 'Academic',
        'facility' => 'Facility / Infrastructure',
        'behaviour' => 'Behaviour / Discipline',
        'administrative' => 'Administrative',
        'library' => 'Library',
        'transport' => 'Transport',
        'other' => 'Other',
    ];

    public const STATUSES = [
        'pending' => 'Pending',
        'in_review' => 'In Review',
        'resolved' => 'Resolved',
        'rejected' => 'Rejected',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function respondedBy()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}
