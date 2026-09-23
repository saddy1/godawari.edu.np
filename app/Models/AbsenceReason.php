<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsenceReason extends Model
{
    protected $fillable = ['label', 'usage_count'];

    // Records that a remark used this reason's exact text — creating it first
    // if nobody has typed it before. This is how the suggestion list grows.
    public static function recordUsage(string $label): void
    {
        $label = trim($label);
        if ($label === '') {
            return;
        }

        $reason = static::whereRaw('LOWER(label) = ?', [mb_strtolower($label)])->first();

        if ($reason) {
            $reason->increment('usage_count');
        } else {
            static::create(['label' => $label, 'usage_count' => 1]);
        }
    }
}
