<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $规划) {
            $规划->decimal('price', 15, 2)->default(0)->after('notifications_limit');
            $规划->integer('duration_months')->default(12)->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $规划) {
            $规划->dropColumn(['price', 'duration_months']);
        });
    }
};
