<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            // Remove old single scan fields
            $table->dropColumn(['scan_type', 'scan_time', 'latitude', 'longitude']);
            
            // Add separate check-in and check-out fields
            $table->timestamp('check_in_time')->nullable()->after('status_reason');
            $table->decimal('check_in_latitude', 10, 8)->nullable()->after('check_in_time');
            $table->decimal('check_in_longitude', 11, 8)->nullable()->after('check_in_latitude');
            
            $table->timestamp('check_out_time')->nullable()->after('check_in_longitude');
            $table->decimal('check_out_latitude', 10, 8)->nullable()->after('check_out_time');
            $table->decimal('check_out_longitude', 11, 8)->nullable()->after('check_out_latitude');
            
            // Add date field for easier querying
            $table->date('attendance_date')->after('branch_id');
            
            // Add work duration in minutes
            $table->integer('work_duration_minutes')->nullable()->after('check_out_longitude');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_time',
                'check_in_latitude',
                'check_in_longitude',
                'check_out_time',
                'check_out_latitude',
                'check_out_longitude',
                'attendance_date',
                'work_duration_minutes'
            ]);
            
            // Restore old fields
            $table->string('scan_type');
            $table->timestamp('scan_time');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
        });
    }
};
