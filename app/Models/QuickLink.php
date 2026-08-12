<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickLink extends Model
{
    protected $fillable = [
        'title',
        'title_ne',
        'url',
        'open_in_new_tab',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'open_in_new_tab' => 'boolean',
        'is_active'       => 'boolean',
        'sort_order'      => 'integer',
    ];

    public function localizedTitle(): string
    {
        if (app()->getLocale() === 'ne' && filled($this->title_ne)) {
            return $this->title_ne;
        }

        return $this->title;
    }
}
