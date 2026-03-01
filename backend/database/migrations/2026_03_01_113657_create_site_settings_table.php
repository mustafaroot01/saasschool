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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('label')->nullable();
            $table->timestamps();
        });

        // Seed initial mobile settings
        DB::table('site_settings')->insert([
            ['key' => 'mobile_app_version', 'value' => '1.0.0', 'label' => 'إصدار التطبيق', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'mobile_app_force_update', 'value' => '0', 'label' => 'تحديث إجباري', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
