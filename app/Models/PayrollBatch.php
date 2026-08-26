<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollBatch extends Model
{
    use HasFactory;

    public const BS_MONTHS = [
        1 => 'Baisakh', 2 => 'Jestha', 3 => 'Ashadh', 4 => 'Shrawan',
        5 => 'Bhadra', 6 => 'Ashwin', 7 => 'Kartik', 8 => 'Mangsir',
        9 => 'Poush', 10 => 'Magh', 11 => 'Falgun', 12 => 'Chaitra',
    ];

    protected $fillable = [
        'bs_year', 'bs_month', 'revision', 'original_filename', 'row_count',
        'total_net_amount', 'uploaded_by', 'uploaded_at',
    ];

    protected function casts(): array
    {
        return ['uploaded_at' => 'datetime', 'total_net_amount' => 'decimal:2'];
    }

    public function payslips()
    {
        return $this->hasMany(PayrollPayslip::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getPeriodLabelAttribute(): string
    {
        return (self::BS_MONTHS[$this->bs_month] ?? 'Month').' '.$this->bs_year.' BS';
    }
}
