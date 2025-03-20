<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Helpers\AuthHelper;
use App\Models\BatchCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BatchCourseController extends Controller
{
    // Store or update the batch_course record
    public function storeOrUpdate(Request $request)
    {
        $validated = $request->validate([
            'batch_id' => 'required|exists:batches,id',
            'course_id' => 'required|exists:courses,id',
            'professor_id' => 'required|exists:professors,id',
        ]);

        AuthHelper::checkUser();
        AuthHelper::checkAdmin();

        // Log the incoming request
        Log::info('Received request to store or update batch_course', [
            'batch_id' => $validated['batch_id'],
            'course_id' => $validated['course_id'],
            'professor_id' => $validated['professor_id'],
        ]);

        $batchCourse = BatchCourse::updateOrCreate(
            [
                'batch_id' => $validated['batch_id'],
                'course_id' => $validated['course_id'],
                'professor_id' => $validated['professor_id'],
            ],
            [
                'updated_at' => now(),
            ]
        );

        Log::info('BatchCourse stored or updated successfully', [
            'batch_course_id' => $batchCourse->id,
            'batch_id' => $validated['batch_id'],
            'course_id' => $validated['course_id'],
            'professor_id' => $validated['professor_id'],
        ]);

        return response()->json([
            'message' => 'BatchCourse stored or updated successfully.',
            'data' => $batchCourse
        ]);
    }

    public function getCoursesForBatch($batchId)
    {
        $batch = Batch::findOrFail($batchId);
        $courses = $batch->courses;

        return response()->json([
            'data' => $courses
        ]);
    }
}
