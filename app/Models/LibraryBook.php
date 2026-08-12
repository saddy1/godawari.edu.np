<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryBook extends Model
{
    protected $fillable = [
        'library_category_id',
        'title',
        'author',
        'isbn',
        'publisher',
        'publication_year',
        'edition',
        'price',
        'pages',
        'description',
        'source',
        'shelf_location',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(LibraryCategory::class, 'library_category_id');
    }

    public function copies()
    {
        return $this->hasMany(LibraryBookCopy::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
