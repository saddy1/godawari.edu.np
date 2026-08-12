<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    protected $fillable = ['name', 'file_path', 'mime_type', 'size', 'category', 'show_in_gallery', 'caption'];

    protected $casts = [
        'show_in_gallery' => 'boolean',
    ];

    // Append this custom attribute to JSON responses automatically
    protected $appends = ['url'];

    public function getUrlAttribute()
    {
        // Simply return the file path relative to the public directory
        return asset($this->file_path);
    }
}
