<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Roster extends Model
{
    protected $fillable = [
        'branch_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['branchId', 'isActive'];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RosterItem::class)->orderBy('day_order');
    }

    public function getBranchIdAttribute()
    {
        return $this->attributes['branch_id'];
    }

    public function getIsActiveAttribute()
    {
        return (bool) $this->attributes['is_active'];
    }

    public function toArray()
    {
        $array = parent::toArray();
        
        $array['branchId'] = $this->branch_id;
        $array['isActive'] = (bool) $this->is_active;
        $array['createdAt'] = $this->created_at?->toISOString();
        $array['updatedAt'] = $this->updated_at?->toISOString();
        
        unset($array['branch_id'], $array['is_active'], $array['created_at'], $array['updated_at']);
        
        return $array;
    }
}