<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'الباقة التجريبية',
                'max_students' => 50,
                'max_teachers' => 10,
                'max_admins' => 2,
                'storage_limit_mb' => 500,
                'notifications_limit' => 100,
                'price' => 0,
                'duration_months' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'الباقة الأساسية',
                'max_students' => 200,
                'max_teachers' => 20,
                'max_admins' => 5,
                'storage_limit_mb' => 2000,
                'notifications_limit' => 1000,
                'price' => 100,
                'duration_months' => 12,
                'is_active' => true,
            ],
            [
                'name' => 'الباقة المتقدمة',
                'max_students' => 1000,
                'max_teachers' => 100,
                'max_admins' => 20,
                'storage_limit_mb' => 10000,
                'notifications_limit' => 5000,
                'price' => 250,
                'duration_months' => 12,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['name' => $plan['name']], $plan);
        }
    }
}
