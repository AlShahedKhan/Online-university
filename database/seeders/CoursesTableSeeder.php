<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Professor;
use Faker\Factory as Faker;

class CoursesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $faker = Faker::create();

        // Ensure at least one professor exists before creating courses
        $professor = Professor::inRandomOrder()->first();

        if (!$professor) {
            // Create a default professor if none exist
            $professor = Professor::create([
                'user_id' => null, // Adjust based on your User model relationship
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email_address' => 'john.doe@example.com',
                'phone_number' => '123-456-7890',
                'designation' => 'Professor',
                'address' => '123 University St, City',
                'postal_code' => '12345',
                'employee_id' => 'EMP001',
                'blood_group' => 'O+',
                'gender' => 'Male',
                'user_status' => 'active',
                'description' => 'Experienced professor in software engineering.',
                'profile_picture' => 'uploads/professors/default.jpg',
            ]);
        }

        // Create 10 sample courses
        for ($i = 0; $i < 10; $i++) {
            Course::create([
                'name' => $faker->sentence(3),
                'description' => $faker->paragraph,
                'course_image' => 'uploads/courses/' . $faker->uuid . '.jpg',
                'professor_id' => $professor->id, // Associate with an existing professor
                'status' => $faker->randomElement(['draft', 'published']),
            ]);
        }
    }
}
