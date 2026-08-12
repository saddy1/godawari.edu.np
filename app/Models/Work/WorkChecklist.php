<?php

namespace App\Models\Work;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'description',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(WorkChecklistItem::class)->orderBy('sort_order')->orderBy('id');
    }
}
