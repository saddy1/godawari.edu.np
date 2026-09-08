<?php

namespace App\Models\Card;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = ['name', 'slug', 'type', 'logo_asset_id', 'signature_asset_id', 'stamp_asset_id', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function departments()
    {
        return $this->hasMany(Department::class)->orderBy('name');
    }

    public function memberTypes()
    {
        return $this->hasMany(MemberType::class)->orderBy('name');
    }

    public function activeDepartments()
    {
        return $this->departments()->where('is_active', true);
    }

    public function logoAsset()      { return $this->belongsTo(OrgAsset::class, 'logo_asset_id'); }
    public function signatureAsset() { return $this->belongsTo(OrgAsset::class, 'signature_asset_id'); }
    public function stampAsset()     { return $this->belongsTo(OrgAsset::class, 'stamp_asset_id'); }

    // Students link by organization slug, not a foreign key — not a true Eloquent relation.
    public function studentsQuery()
    {
        return Student::where('organization', $this->slug);
    }
}
