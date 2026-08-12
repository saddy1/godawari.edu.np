<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryBookCopy extends Model
{
    protected $fillable = [
        'library_book_id',
        'accession_no',
        'barcode',
        'status',
        'condition',
        'remarks',
    ];

    public function book()
    {
        return $this->belongsTo(LibraryBook::class, 'library_book_id');
    }

    public function loans()
    {
        return $this->hasMany(LibraryLoan::class);
    }

    public function activeLoan()
    {
        return $this->hasOne(LibraryLoan::class)->where('status', 'issued');
    }
}
