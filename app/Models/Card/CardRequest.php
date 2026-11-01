<?php

namespace App\Models\Card;

use Illuminate\Database\Eloquent\Model;

class CardRequest extends Model
{
    protected $fillable = ['student_id', 'status', 'admin_note'];

    public function student()
    {
        // Card requests are historical records. Inactive students must remain
        // available here; only a genuinely deleted student makes this relation
        // invalid. (Student normally hides inactive members with a global scope.)
        return $this->belongsTo(Student::class)->withoutGlobalScope('active');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'   => 'Pending Payment Verification',
            'approved'  => 'Approved – Ready to Collect',
            'collected' => 'Collected',
            'rejected'  => 'Rejected',
            default     => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending'   => 'yellow',
            'approved'  => 'green',
            'collected' => 'blue',
            'rejected'  => 'red',
            default     => 'gray',
        };
    }
}
