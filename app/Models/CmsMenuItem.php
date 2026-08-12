<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsMenuItem extends Model
{
    protected $fillable = [
        'cms_menu_id',
        'parent_id',
        'cms_page_id',
        'label',
        'label_ne',
        'subtitle',
        'subtitle_ne',
        'type',
        'url',
        'target',
        'sort_order',
        'is_active',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function menu()
    {
        return $this->belongsTo(CmsMenu::class, 'cms_menu_id');
    }

    public function page()
    {
        return $this->belongsTo(CmsPage::class, 'cms_page_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->where('is_active', true)->orderBy('sort_order')->orderBy('label')->with('children.page', 'page');
    }

    public function getResolvedUrlAttribute(): string
    {
        return $this->type === 'page' && $this->page ? $this->page->url : ($this->url ?: '#');
    }

    public function localizedLabel(): string
    {
        return app()->getLocale() === 'ne' && filled($this->label_ne)
            ? $this->label_ne
            : $this->label;
    }

    public function localizedSubtitle(): ?string
    {
        return app()->getLocale() === 'ne' && filled($this->subtitle_ne)
            ? $this->subtitle_ne
            : $this->subtitle;
    }
}
