<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Professor;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(
            [
                // ApplicationsTableSeeder::class,
                UserSeeder::class,
                // ProfessorsTableSeeder::class,
                // BatchesTableSeeder::class,
                // CoursesTableSeeder::class,
                // BatchCourseTableSeeder::class,
                // MaterialsTableSeeder::class,

            ]
        );
    }
}
