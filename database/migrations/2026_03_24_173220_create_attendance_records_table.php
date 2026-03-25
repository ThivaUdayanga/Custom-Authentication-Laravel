<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->enum('status', ['On Time', 'Late', 'Early Departure', 'Fraudulent', 'Verified']);
            $table->text('status_reason')->nullable();
            $table->string('scan_type');  // Check-in or Check-out
            $table->timestamp('scan_time');
            $table->decimal('latitude', 10, 8); // Store latitude
            $table->decimal('longitude', 11, 8); // Store longitude
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};