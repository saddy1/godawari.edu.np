<?php

namespace App\Models\Card;

use Illuminate\Database\Eloquent\Model;

class UpdateRequest extends Model
{
    protected $fillable = ['student_id', 'requested_changes', 'status', 'admin_note'];

    protected $casts = [
        'requested_changes' => 'array',
    ];

    public function student()
    {
        // Requests are historical records, so an inactive student must still
        // be available to the administrator reviewing the request.
        return $this->belongsTo(Student::class)->withoutGlobalScope('active');
    }
}
