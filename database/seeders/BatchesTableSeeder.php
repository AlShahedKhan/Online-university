<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Batch;
use App\Models\Professor;
use Faker\Factory as Faker;

class BatchesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $faker = Faker::create();

        // Create 5 batches
        for ($i = 0; $i < 5; $i++) {
            $batch = Batch::create([
                'title' => 'Batch ' . ($i + 1),
                'subtitle' => $faker->sentence(5),
                'batch_image' => 'batch_images/batch' . ($i + 1) . '.jpg',
            ]);

            // Attach random professors (since it's a many-to-many relationship)
            $professors = Professor::inRandomOrder()->limit(rand(1, 3))->pluck('id');
            $batch->instructors()->attach($professors);
        }
    }
}
