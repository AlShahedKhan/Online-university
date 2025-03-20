<?php

namespace Database\Seeders;

use App\Models\Batch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Batch::create([
            'name' => 'Batch 1',
            'subtitle' => 'Subtitle 1',
            'batch_image' => 'Description 1',
            'batch_instructor' => 'active',
        ]);
    }
}
