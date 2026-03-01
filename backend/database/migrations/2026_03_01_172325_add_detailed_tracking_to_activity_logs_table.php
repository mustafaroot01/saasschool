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
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('model_type')->nullable()->after('description');
            $table->string('model_id')->nullable()->after('model_type');
            $table->json('old_values')->nullable()->after('model_id');
            $table->json('new_values')->nullable()->after('old_values');
            
            $table->index(['model_type', 'model_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(['model_type', 'model_id']);
            $table->dropColumn(['model_type', 'model_id', 'old_values', 'new_values']);
        });
    }
};
