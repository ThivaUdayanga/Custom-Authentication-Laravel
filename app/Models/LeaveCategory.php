<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
        'branch_id',
        'leave_duration_type',
        'days_per_year',
        'applicable_roles',
        'is_paid',
        'is_active',
    ];

    protected $casts = [
        'applicable_roles' => 'array',
        'is_paid' => 'boolean',
        'is_active' => 'boolean',
    ];

    public const DURATION_FULL_DAY = 'Full Day';
    public const DURATION_HALF_DAY = 'Half Day';
    public const DURATION_BOTH = 'Both';

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    public function isApplicableForRole(string $role): bool
    {
        if (empty($this->applicable_roles)) {
            return true; // If no roles specified, applicable to all
        }
        
        return in_array($role, $this->applicable_roles);
    }

    public function isApplicableForBranch(?string $branchId): bool
    {
        if ($this->branch_id === null) {
            return true; // Global category, applicable to all branches
        }
        
        return $this->branch_id === $branchId;
    }

    public function toArray()
    {
        $array = parent::toArray();
        
        $array['branchId'] = $this->branch_id;
        $array['leaveDurationType'] = $this->leave_duration_type;
        $array['daysPerYear'] = $this->days_per_year;
        $array['applicableRoles'] = $this->applicable_roles;
        $array['isPaid'] = (bool) $this->is_paid;
        $array['isActive'] = (bool) $this->is_active;
        $array['createdAt'] = $this->created_at?->toISOString();
        $array['updatedAt'] = $this->updated_at?->toISOString();
        
        unset($array['branch_id'], $array['leave_duration_type'], $array['days_per_year'], 
              $array['applicable_roles'], $array['is_paid'], $array['is_active'], 
              $array['created_at'], $array['updated_at']);
        
        return $array;
    }
}
