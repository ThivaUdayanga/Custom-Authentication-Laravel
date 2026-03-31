<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::first();

        User::create([
            'name' => 'System Admin',
            'email' => 'admin@gmail.com',
            'password' => '12345678',
            'role' => User::ROLE_ADMIN,
            'branch_id' => $branch?->id,
            'department' => 'Administration',
            'employment_status' => 'Active',
            'date_of_joining' => now()->toDateString(),
        ]);
    }
}