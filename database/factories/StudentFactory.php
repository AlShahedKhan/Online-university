<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition()
    {
        return [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'phone_number' => $this->faker->phoneNumber,
            'batch_id' => \App\Models\Batch::inRandomOrder()->first()->id ?? 1, // Assign a random batch
            'gender' => $this->faker->randomElement(['Male', 'Female']),
            'address' => $this->faker->address,
        ];
    }
}
