<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsMenu extends Model
{
    protected $fillable = ['name', 'location', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function items()
    {
        return $this->hasMany(CmsMenuItem::class)->orderBy('sort_order')->orderBy('label');
    }

    public function rootItems()
    {
        return $this->items()->whereNull('parent_id')->where('is_active', true)->with('children.page');
    }
}
