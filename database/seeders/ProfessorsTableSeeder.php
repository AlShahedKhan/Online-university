<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Professor;
use App\Models\User;
use Faker\Factory as Faker;

class ProfessorsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $faker = Faker::create();

        for ($i = 0; $i < 10; $i++) { // Generate 10 fake professors
            $user = User::inRandomOrder()->first();

            if (!$user) {
                // Create a user if no users exist
                $user = User::create([
                    'name' => $faker->name,
                    'email' => $faker->unique()->safeEmail,
                    'password' => bcrypt('password'), // Default password
                ]);
            }

            Professor::create([
                'user_id' => $user->id,
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'email_address' => $faker->unique()->safeEmail,
                'phone_number' => $faker->phoneNumber,
                'designation' => $faker->randomElement(['Assistant Professor', 'Associate Professor', 'Professor']),
                'address' => $faker->address,
                'postal_code' => $faker->postcode,
                'employee_id' => strtoupper($faker->unique()->bothify('EMP###')),
                'blood_group' => $faker->randomElement(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']),
                'gender' => $faker->randomElement(['Male', 'Female', 'Other']),
                'user_status' => $faker->randomElement(['active', 'inactive']),
                'description' => $faker->sentence,
                'profile_picture' => 'uploads/professors/' . $faker->uuid . '.jpg',
            ]);
        }
    }
}
