<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faculty extends Model
{
    protected $fillable = ['faculty_group_id', 'name', 'role', 'category', 'education', 'image', 'order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(FacultyGroup::class, 'faculty_group_id');
    }

    // Helper to get image URL
    public function getImageUrlAttribute()
    {
        if (!$this->image) return asset('assets/image/default-placeholder.jpg');
        return str_contains($this->image, 'http') ? $this->image : asset($this->image);
    }
}
