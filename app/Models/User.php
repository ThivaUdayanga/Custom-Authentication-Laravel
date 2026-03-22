<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\HasApiTokens;
//use App\Models\Shift;
use App\Models\EmployeeRosterAssignment;

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
        'designation',
        'employment_status',
        'date_of_joining',
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
}