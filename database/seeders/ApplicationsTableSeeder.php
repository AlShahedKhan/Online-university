<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Application;
use Faker\Factory as Faker;

class ApplicationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $faker = Faker::create();

        for ($i = 0; $i < 10; $i++) { // Generate 10 fake applications
            Application::create([
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'date_of_birth' => $faker->date('Y-m-d', '-20 years'),
                'gender' => $faker->randomElement(['Male', 'Female', 'Other']),
                'nationality' => $faker->country,
                'contact_number' => $faker->phoneNumber,
                'email' => $faker->unique()->safeEmail,
                'address' => $faker->address,
                'nid_number' => $faker->randomNumber(8, true),
                'program' => $faker->randomElement(['Computer Science', 'Business Administration', 'Data Science']),
                'bachelor_institution' => $faker->company,
                'degree_earned' => $faker->randomElement(['BSc', 'BA', 'BBA', 'BEng']),
                'graduation_year' => $faker->numberBetween(2010, 2024),
                'gpa' => $faker->randomFloat(2, 2.0, 4.0),
                'job_title' => $faker->jobTitle,
                'years_experience' => $faker->numberBetween(0, 10),
                'responsibilities' => $faker->sentence,
                'passport_path' => 'uploads/passports/' . $faker->uuid . '.jpg',
                'nid_path' => 'uploads/nids/' . $faker->uuid . '.jpg',
                'application_status' => $faker->randomElement(['pending', 'approved', 'rejected']),
            ]);
        }
    }
}
