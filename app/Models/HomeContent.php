<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeContent extends Model
{
    protected $fillable = [
        'type',
        'category',
        'title',
        'title_ne',
        'subtitle',
        'subtitle_ne',
        'description',
        'url',
        'image_path',
        'icon_key',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public const ICONS = [
        'notice' => 'M8 7h8M8 11h8M8 15h5M6 3h9l3 3v15H6V3z',
        'result' => 'M9 12l2 2 4-4M7 4h10v16H7V4z',
        'calendar' => 'M8 7V3m8 4V3M5 11h14M6 5h12a1 1 0 011 1v13H5V6a1 1 0 011-1z',
        'grid' => 'M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z',
        'download' => 'M12 3v10m0 0l4-4m-4 4L8 9M5 19h14',
        'contact' => 'M21 10.5a8.38 8.38 0 01-.9 3.8 8.5 8.5 0 01-7.6 4.7 8.38 8.38 0 01-3.8-.9L3 20l1.9-5.7a8.38 8.38 0 01-.9-3.8 8.5 8.5 0 1117 0z',
        'book' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5S19.832 5.477 21 6.253v13',
        'idea' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0',
        'people' => 'M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4 0a4 4 0 100-8 4 4 0 000 8z',
        'screen' => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5H3v10a2 2 0 002 2z',
    ];

    public function localizedTitle(): string
    {
        if (app()->getLocale() === 'ne' && ! empty($this->title_ne)) {
            return $this->title_ne;
        }

        return $this->title ?? '';
    }

    public function localizedSubtitle(): ?string
    {
        if (app()->getLocale() === 'ne' && ! empty($this->subtitle_ne)) {
            return $this->subtitle_ne;
        }

        return $this->subtitle;
    }

    public function getIconPathAttribute(): string
    {
        return self::ICONS[$this->icon_key] ?? self::ICONS['book'];
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset($this->image_path) : null;
    }
}
