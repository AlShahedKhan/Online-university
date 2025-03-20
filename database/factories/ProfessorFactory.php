<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Professor>
 */
class ProfessorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email_address' => $this->faker->unique()->safeEmail,
            'phone_number' => $this->faker->unique()->phoneNumber,
            'designation' => $this->faker->jobTitle,
            'address' => $this->faker->address,
            'postal_code' => $this->faker->postcode,
            'employee_id' => $this->faker->unique()->bothify('EMP-#####'),
            'blood_group' => $this->faker->randomElement(['A+', 'B+', 'AB+', 'O+', 'A-', 'B-', 'AB-', 'O-']),
            'gender' => $this->faker->randomElement(['Male', 'Female', 'Other']),
            'user_status' => $this->faker->randomElement(['Active', 'Inactive']),
            'description' => $this->faker->optional()->sentence,
            'profile_picture' => $this->faker->optional()->imageUrl(200, 200, 'people', true, 'Professor')
        ];
    }
}
