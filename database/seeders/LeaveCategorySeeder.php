<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LeaveCategory;

class LeaveCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Annual Leave',
                'description' => 'Regular annual leave entitlement',
                'branch_id' => null, // Global category
                'leave_duration_type' => 'Both',
                'days_per_year' => 21,
                'applicable_roles' => ['Employee', 'HR Manager', 'Branch Manager'],
                'is_paid' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Sick Leave',
                'description' => 'Medical leave for illness',
                'branch_id' => null, // Global category
                'leave_duration_type' => 'Both',
                'days_per_year' => 10,
                'applicable_roles' => ['Employee', 'HR Manager', 'Branch Manager'],
                'is_paid' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Unpaid Leave',
                'description' => 'Unpaid leave for personal reasons',
                'branch_id' => null, // Global category
                'leave_duration_type' => 'Both',
                'days_per_year' => 30,
                'applicable_roles' => null, // All roles
                'is_paid' => false,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            LeaveCategory::create($category);
        }
    }
}
