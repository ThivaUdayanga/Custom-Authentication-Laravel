<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        Branch::create([
            'name' => 'Head Office',
            'location' => 'Colombo',
            'working_hours' => '9:00 AM - 5:00 PM',
            'time_zone' => 'IST (India Standard Time)',
            'is_active' => true,
        ]);
    }
}