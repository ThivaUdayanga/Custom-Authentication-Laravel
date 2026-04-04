<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('leaves', 'leave_category_id')) {
            Schema::table('leaves', function (Blueprint $table) {
                $table->foreignId('leave_category_id')->nullable()->after('user_id')->constrained('leave_categories')->cascadeOnDelete();
            });
        }

        if (!Schema::hasColumn('leaves', 'duration_type')) {
            Schema::table('leaves', function (Blueprint $table) {
                $table->enum('duration_type', ['Full Day', 'Half Day'])->default('Full Day')->after('leave_category_id');
            });
        }

        if (!Schema::hasColumn('leaves', 'days_count')) {
            Schema::table('leaves', function (Blueprint $table) {
                $table->decimal('days_count', 5, 1)->default(1.0)->after('end_date');
            });
        }

        if (Schema::hasColumn('leaves', 'leave_type')) {
            Schema::table('leaves', function (Blueprint $table) {
                $table->dropColumn('leave_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('leaves', 'days_count')) {
            Schema::table('leaves', function (Blueprint $table) {
                $table->dropColumn('days_count');
            });
        }

        if (Schema::hasColumn('leaves', 'duration_type')) {
            Schema::table('leaves', function (Blueprint $table) {
                $table->dropColumn('duration_type');
            });
        }

        if (Schema::hasColumn('leaves', 'leave_category_id')) {
            Schema::table('leaves', function (Blueprint $table) {
                $table->dropConstrainedForeignId('leave_category_id');
            });
        }

        // if (!Schema::hasColumn('leaves', 'leave_type')) {
        //     Schema::table('leaves', function (Blueprint $table) {
        //         $table->enum('leave_type', [
        //             'Sick Leave',
        //             'Casual Leave',
        //             'Annual Leave',
        //             'Maternity Leave',
        //             'Paternity Leave',
        //             'Unpaid Leave',
        //         ])->after('user_id');
        //     });
        // }
    }
};
