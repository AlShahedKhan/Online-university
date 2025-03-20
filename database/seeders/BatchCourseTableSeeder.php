<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Professor;
use Illuminate\Support\Facades\DB;

class BatchCourseTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Ensure that at least one batch, course, and professor exist
        $batches = Batch::all();
        $courses = Course::all();
        $professors = Professor::all();

        if ($batches->isEmpty() || $courses->isEmpty() || $professors->isEmpty()) {
            $this->command->info('Skipping BatchCourseTableSeeder: No batches, courses, or professors found.');
            return;
        }

        foreach ($batches as $batch) {
            // Attach random courses to each batch
            $assignedCourses = $courses->random(rand(1, 3)); // Assign 1-3 random courses per batch

            foreach ($assignedCourses as $course) {
                // Select a random professor for the batch-course assignment
                $professor = $professors->random();

                // Insert into batch_course pivot table
                DB::table('batch_course')->insert([
                    'batch_id' => $batch->id,
                    'course_id' => $course->id,
                    'professor_id' => $professor->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('BatchCourseTableSeeder: Successfully seeded batch_course pivot table.');
    }
}
