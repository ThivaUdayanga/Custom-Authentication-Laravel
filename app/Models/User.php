<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;
//use App\Models\Shift;
use App\Models\EmployeeRosterAssignment;
use App\Models\Leave;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'branch_id',
        'department',
        //'designation',
        'employment_status',
        'date_of_joining',
        'leave_balance',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'date_of_joining' => 'date',
        'password' => 'hashed',
    ];

    public const ROLE_ADMIN = 'Admin';
    public const ROLE_HR_MANAGER = 'HR Manager';
    public const ROLE_BRANCH_MANAGER = 'Branch Manager';
    public const ROLE_EMPLOYEE = 'Employee';

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function rosterAssignments()
    {
        return $this->hasMany(EmployeeRosterAssignment::class, 'user_id');
    }

    public function assignedRosterRecords()
    {
        return $this->hasMany(EmployeeRosterAssignment::class, 'assigned_by');
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function approvedLeaves()
    {
        return $this->hasMany(Leave::class, 'approved_by');
    }

    public function toArray()
    {
        $array = parent::toArray();
        
        $array['branchId'] = $this->branch_id;
        $array['employmentStatus'] = $this->employment_status;
        $array['dateOfJoining'] = $this->date_of_joining?->format('Y-m-d');
        $array['leaveBalance'] = $this->leave_balance;
        
        unset($array['branch_id'], $array['employment_status'], 
              $array['date_of_joining'], $array['leave_balance']);
        
        return $array;
    }
}