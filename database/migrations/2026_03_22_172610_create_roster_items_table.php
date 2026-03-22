<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roster_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roster_id')->constrained('rosters')->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->restrictOnDelete();
            $table->unsignedInteger('day_order');
            $table->timestamps();

            $table->unique(['roster_id', 'day_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_items');
    }
};