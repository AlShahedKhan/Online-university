<?php

namespace App\Http\Controllers;

use App\Models\MCQ;
use App\Models\Course;
use App\Models\Student;
use App\Models\Material;
use Illuminate\Http\Request;
use App\Models\StudentAnswer;
use App\Models\VideoProgress;
use App\Traits\HandlesApiResponse;
use App\Models\AssignmentSubmission;
use Illuminate\Support\Facades\Auth;

class VideoProgressController extends Controller
{
    use HandlesApiResponse;

    public function getVideos(Request $request, $id)
    {
        $userId = Auth::id();

        // Fetch the student associated with the logged-in user
        $student = Student::where('user_id', $userId)->first();

        // If no student record is found, return a clear error message
        if (!$student) {
            return response()->json([
                'status' => false,
                'message' => 'Student record not found for this user.',
                'status_code' => 404,
            ], 404);
        }

        // Fetch the course using the passed ID
        $course = Course::find($id);

        // If no course is found, return an error message
        if (!$course) {
            return response()->json([
                'status' => false,
                'message' => 'Course not found.',
                'status_code' => 404,
            ], 404);
        }

        // Get all materials (videos) related to the specific course
        $materials = Material::where('course_id', $id)->orderBy('id', 'asc')->get();

        // Get user's video progress
        $progress = VideoProgress::where('user_id', $userId)->pluck('progress', 'video_id');

        // Loop through materials to set progress and lock status
        foreach ($materials as $index => $material) {
            // Set the progress for each video, default to 0 if not found
            $material->progress = $progress[$material->id] ?? 0;

            // Only unlock the next video if the previous video has >= 95% progress
            if ($index > 0) {
                // Check if the previous video has enough progress
                $previousMaterialProgress = $progress[$materials[$index - 1]->id] ?? 0;

                if ($previousMaterialProgress >= 95) {
                    // Unlock only the next video
                    $material->locked = false;
                } else {
                    $material->locked = true; // Lock the video if previous is not complete
                }
            } else {
                // The first video is always unlocked
                $material->locked = false;
            }
        }

        // Return the materials (videos) with progress and lock status
        return response()->json($materials);
    }



    // public function getVideos(Request $request, $id)
    // {
    //     $userId = Auth::id();

    //     // Fetch the student associated with the logged-in user
    //     $student = Student::where('user_id', $userId)->first();

    //     // If no student record is found, return a clear error message
    //     if (!$student) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Student record not found for this user.',
    //             'status_code' => 404,
    //         ], 404);
    //     }

    //     // Fetch the course using the passed ID
    //     $course = Course::find($id); // Assuming `Course` is the model for the course table

    //     // If no course is found, return an error message
    //     if (!$course) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Course not found.',
    //             'status_code' => 404,
    //         ], 404);
    //     }

    //     // Get all materials (videos) related to the specific course
    //     $materials = Material::where('course_id', $id)->orderBy('id', 'asc')->get();

    //     // Get user's video progress
    //     $progress = VideoProgress::where('user_id', $userId)->pluck('progress', 'video_id');

    //     $lockAllNextVideos = false; // Flag to lock all subsequent videos

    //     // Loop through materials to set progress and lock status
    //     foreach ($materials as $index => $material) {
    //         // Set the progress for each video, default to 0 if not found
    //         $material->progress = $progress[$material->id] ?? 0;

    //         // Skip the first video (index = 0) from locking logic
    //         if ($index > 0 && $material->progress < 95) {
    //             // If progress is less than 95 and it's not the first video, lock all subsequent videos
    //             $lockAllNextVideos = true;
    //         }

    //         // If the flag is set, lock the video
    //         if ($lockAllNextVideos) {
    //             $material->locked = true;
    //         } else {
    //             // Otherwise, use the existing logic to determine if the video should be locked
    //             $material->locked = $this->isVideoLocked($userId, $material->id, $index);
    //         }
    //     }

    //     // Return the materials (videos) with progress and lock status
    //     return response()->json($materials);
    // }


    public function getVideoShow(Request $request, $course_id, $material_id)
    {
        $userId = Auth::id();

        // Fetch the student associated with the logged-in user
        $student = Student::where('user_id', $userId)->first();

        // If no student record is found, return an error message
        if (!$student) {
            return response()->json([
                'status' => false,
                'message' => 'Student record not found for this user.',
                'status_code' => 404,
            ], 404);
        }

        // Fetch the course using the passed course_id
        $course = Course::find($course_id);

        // If no course is found, return an error message
        if (!$course) {
            return response()->json([
                'status' => false,
                'message' => 'Course not found.',
                'status_code' => 404,
            ], 404);
        }

        // Fetch all materials (videos) for this course
        $materials = Material::where('course_id', $course_id)->orderBy('id', 'asc')->get();

        // Get the user's video progress for each video
        $progress = VideoProgress::where('user_id', $userId)->pluck('progress', 'video_id');

        $lockAllNextVideos = false; // Flag to lock subsequent videos after the first one

        // Loop through materials to set progress and lock status
        foreach ($materials as $index => $material) {
            // Set the progress for each video, default to 0 if not found
            $material->progress = isset($progress[$material->id]) ? $progress[$material->id] : 0;

            // Ensure the first video is always unlocked
            if ($index == 0) {
                $material->locked = false;
            } else {
                // If the previous video is completed (progress >= 95), unlock the current one
                $previousMaterialProgress = isset($progress[$materials[$index - 1]->id]) ? $progress[$materials[$index - 1]->id] : 0;

                if ($previousMaterialProgress >= 95) {
                    $material->locked = false;
                } else {
                    $material->locked = true;
                }
            }
        }

        // Find the specific video based on material_id
        $material = $materials->where('id', $material_id)->first();

        // If the requested video is not found, return an error message
        if (!$material) {
            return response()->json([
                'status' => false,
                'message' => 'Video not found.',
                'status_code' => 404,
            ], 404);
        }

        // Return the material (video) with progress and lock status
        return response()->json($material);
    }





    /**
     * Check if a video should be locked.
     */
    private function isVideoLocked($userId, $videoId, $index)
    {
        // First 3 videos are always unlocked
        if ($index < 3) {
            return false;
        }

        // Check if previous video was watched at least 90%
        $prevVideoId = $videoId - 1;
        $progress = VideoProgress::where('user_id', $userId)
            ->where('video_id', $prevVideoId)
            ->first();

        return !$progress || $progress->progress < 90;
    }

    /**
     * Update progress when a user watches a video.
     */
    public function updateProgress(Request $request)
    {
        $userId = Auth::id();
        $validated = $request->validate([
            'video_id' => 'required|exists:materials,id',
            'progress' => 'required|integer|min:0|max:100'
        ]);

        VideoProgress::updateOrCreate(
            ['user_id' => $userId, 'video_id' => $validated['video_id']],
            ['progress' => $validated['progress']]
        );

        return response()->json(['message' => 'Progress updated successfully']);
    }


    public function trackProgress(Request $request)
    {
        return $this->safeCall(function () {
            // Get the logged-in user's ID
            $userId = Auth::id();

            // Fetch the student associated with the logged-in user
            $student = Student::where('user_id', $userId)->first();

            // If no student record is found, return a clear error message
            if (!$student) {
                return response()->json([
                    'status' => false,
                    'message' => 'Student record not found for this user.',
                    'status_code' => 404,
                ], 404);
            }

            // Proceed with the rest of the logic
            $totalVideos = Material::count();

            // If no videos exist, prevent division by zero
            if ($totalVideos === 0) {
                return response()->json([
                    'progress' => 0,
                    'expected_progress' => 0,
                    'video_completed' => 0
                ]);
            }

            // Get the sum of all progress values for this student
            $sumProgress = VideoProgress::where('user_id', $userId)->sum('progress');

            // The expected progress is the number of videos * 100 (assuming each video can have a max of 100%)
            $expectedProgress = $totalVideos * 100;

            // Calculate the percentage of completed videos
            $videoCompleted = ($sumProgress / $expectedProgress) * 100;

            // Format to 2 decimal places
            $videoCompleted = number_format($videoCompleted, 2);

            // Return the result in the JSON response
            return response()->json([
                'progress' => $sumProgress,               // Total sum of progress from VideoProgress
                'expected_progress' => $expectedProgress, // Total expected progress (each video is 100%)
                'video_completed' => $videoCompleted      // Percentage of video completion
            ]);
        });
    }

    public function trackProgressForStudent($student_id)
    {
        return $this->safeCall(function () use ($student_id) {
            // Fetch the student by ID
            $student = Student::find($student_id);

            // If no student record is found, return a clear error message
            if (!$student) {
                return response()->json([
                    'status' => false,
                    'message' => 'Student record not found.',
                    'status_code' => 404,
                ], 404);
            }

            // Proceed with the rest of the logic
            $totalVideos = Material::count();

            // If no videos exist, prevent division by zero
            if ($totalVideos === 0) {
                return response()->json([
                    'progress' => 0,
                    'expected_progress' => 0,
                    'video_completed' => 0
                ]);
            }

            // Get the sum of all progress values for this student
            $sumProgress = VideoProgress::where('user_id', $student->user_id)->sum('progress');

            // The expected progress is the number of videos * 100 (assuming each video can have a max of 100%)
            $expectedProgress = $totalVideos * 100;

            // Calculate the percentage of completed videos
            $videoCompleted = ($sumProgress / $expectedProgress) * 100;

            // Format to 2 decimal places
            $videoCompleted = number_format($videoCompleted, 2);

            // Return the result in the JSON response
            return response()->json([
                'progress' => $sumProgress,               // Total sum of progress from VideoProgress
                'expected_progress' => $expectedProgress, // Total expected progress (each video is 100%)
                'video_completed' => $videoCompleted      // Percentage of video completion
            ]);
        });
    }

}
