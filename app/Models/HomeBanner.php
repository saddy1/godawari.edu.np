<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeBanner extends Model
{
    protected $fillable = [
        'eyebrow',    'eyebrow_ne',
        'title',      'title_ne',
        'subtitle',   'subtitle_ne',
        'primary_label',   'primary_label_ne',
        'primary_url',
        'secondary_label', 'secondary_label_ne',
        'secondary_url',
        'image_path',
        'text_position',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function getImageUrlAttribute(): string
    {
        return asset($this->image_path);
    }

    /** Return the eyebrow in the current locale, falling back to English. */
    public function localizedEyebrow(): ?string
    {
        return app()->getLocale() === 'ne' && $this->eyebrow_ne
            ? $this->eyebrow_ne : $this->eyebrow;
    }

    public function localizedTitle(): string
    {
        return (app()->getLocale() === 'ne' && $this->title_ne)
            ? $this->title_ne : $this->title;
    }

    public function localizedSubtitle(): ?string
    {
        return app()->getLocale() === 'ne' && $this->subtitle_ne
            ? $this->subtitle_ne : $this->subtitle;
    }

    public function localizedPrimaryLabel(): ?string
    {
        return app()->getLocale() === 'ne' && $this->primary_label_ne
            ? $this->primary_label_ne : $this->primary_label;
    }

    public function localizedSecondaryLabel(): ?string
    {
        return app()->getLocale() === 'ne' && $this->secondary_label_ne
            ? $this->secondary_label_ne : $this->secondary_label;
    }
}
