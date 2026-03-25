<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_record_id')->constrained('attendance_records')->cascadeOnDelete();
            $table->string('qr_code'); // Store the QR code value scanned
            $table->string('scan_type'); // 'Check-in' or 'Check-out'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_scans');
    }
};