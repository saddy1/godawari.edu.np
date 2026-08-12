<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibraryActivityLog extends Model
{
    public $timestamps = true;
    const UPDATED_AT = null;

    protected $fillable = [
        'action', 'loan_id', 'book_copy_id', 'book_id', 'user_id',
        'borrower_name', 'borrower_identifier', 'borrower_type',
        'book_title', 'accession_no', 'fine_amount', 'details', 'performed_by',
    ];

    protected $casts = [
        'fine_amount' => 'decimal:2',
    ];

    public function loan()
    {
        return $this->belongsTo(LibraryLoan::class);
    }

    public function book()
    {
        return $this->belongsTo(LibraryBook::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'book_issued'             => 'Book Issued',
            'book_returned'           => 'Book Returned',
            'fine_collected'          => 'Fine Collected',
            'book_added'              => 'Book Added',
            'copies_added'            => 'Copies Added',
            'rule_updated'            => 'Rule Updated',
            'patron_category_updated' => 'Patron Category Updated',
            default                   => ucwords(str_replace('_', ' ', $this->action)),
        };
    }

    public function getActionColorAttribute(): string
    {
        return match ($this->action) {
            'book_issued'    => 'blue',
            'book_returned'  => 'green',
            'fine_collected' => 'red',
            'book_added'     => 'purple',
            'copies_added'   => 'purple',
            default          => 'gray',
        };
    }
}
