<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryBook extends Model
{
    protected $fillable = [
        'library_category_id',
        'preceded_by_book_id',
        'title',
        'author',
        'isbn',
        'publisher',
        'publication_year',
        'edition',
        'volume',
        'language',
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

    // The earlier edition this catalog entry succeeds, if one was linked.
    public function precededBy()
    {
        return $this->belongsTo(self::class, 'preceded_by_book_id');
    }

    // Any later editions that were linked back to this one.
    public function laterEditions()
    {
        return $this->hasMany(self::class, 'preceded_by_book_id');
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
