<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
        ]);

        User::updateOrCreate(
            ['email' => 'admin@diyala.net'],
            [
                'name' => 'مدير النظام',
                'password' => bcrypt('password123'),
            ]
        );
    }
}
