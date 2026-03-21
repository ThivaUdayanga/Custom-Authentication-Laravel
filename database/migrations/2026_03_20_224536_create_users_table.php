<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');

            $table->enum('role', [
                User::ROLE_ADMIN,
                User::ROLE_HR_MANAGER,
                User::ROLE_BRANCH_MANAGER,
                User::ROLE_EMPLOYEE,
            ])->default(User::ROLE_EMPLOYEE);

            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();

            $table->string('department')->nullable();
            $table->string('designation')->nullable();

            $table->enum('employment_status', [
                'Active',
                'On Leave',
                'Resigned',
                'Terminated',
            ])->default('Active');

            $table->date('date_of_joining')->nullable();
            $table->unsignedBigInteger('shift_id')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};