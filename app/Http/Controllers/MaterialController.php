<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Course;
use App\Models\Student;
use App\Models\Material;
use App\Helpers\AuthHelper;
use Illuminate\Http\Request;
use App\Services\VimeoService;
use App\Jobs\UploadVideoToVimeo;
use App\Traits\HandlesApiResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\AssignmentSubmission;

use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Cache\Store;

class MaterialController extends Controller
{
    use HandlesApiResponse;

    public function index()
    {
        AuthHelper::checkUser();
        AuthHelper::checkProfessor();

        $material = Material::orderBy('created_at', 'desc')->paginate(10);

        return $this->successResponse('Materials retrieved successfully', ['materials' => $material]);
    }

    public function fetchCoursesByCourseIdForAdmin($courseId)
    {
        return $this->safeCall(function () use ($courseId) {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();
            $data = Material::where('course_id', $courseId)->get();
            return $this->successResponse('Materials retrieved successfully', ['materials' => $data]);
        });
    }

    // This is for professor
    public function fetchCoursesByCourseId($courseId)
    {
        return $this->safeCall(function () use ($courseId) {
            AuthHelper::checkUser();
            AuthHelper::checkProfessor();
            $data = Material::with('professor')->where('course_id', $courseId)->get();
            return $this->successResponse('Materials retrieved successfully', ['materials' => $data]);
        });
    }


    public function storeOrUpdate(Request $request, $id = null)
    {
        return $this->safeCall(function () use ($request, $id) {
            // Validation for data and files
            $validateData = $request->validate([
                'data' => 'required|string',
                'video' => 'nullable|mimes:mp4,mov,avi', // 20MB max
                'assignment' => 'nullable|mimes:pdf,doc,docx', // 10MB max
            ]);

            AuthHelper::checkUser();
            AuthHelper::checkProfessor();

            if (!Batch::exists()) {
                return $this->errorResponse('No batch found', 400);
            }
            if (!Course::exists()) {
                return $this->errorResponse('No course found', 400);
            }

            // Decode and validate the 'data' field to ensure it's a valid JSON array
            $data = json_decode($validateData['data'], true);

            if (!is_array($data)) {
                return $this->errorResponse('The data field must be a valid JSON array.', 400);
            }

            $request->merge($data); // Merge the decoded data with the request

            // Additional validation for the merged fields
            $request->validate([
                'batch_id' => 'required|exists:batches,id',
                'course_id' => 'required|exists:courses,id',
                'title' => 'required|string|max:255',
                'subtitle' => 'nullable|string|max:255',
                'date' => 'required|date',
                'total_time' => 'required|string|max:50',
                'description' => 'required|string|max:5000',
            ]);

            // Fetch professor and check authorization
            $professor = auth()->user()->professor;
            if (!$professor) {
                return $this->errorResponse('Unauthorized access. Professor not found.', 403);
            }
            $professorId = $professor->id;

            // Find existing material or create a new one
            $material = $id ? Material::find($id) : new Material();

            if (!$material && $id) {
                return $this->errorResponse('Material not found', 404);
            }

            // Handle video upload if present
            $videoPath = null;
            if ($request->hasFile('video')) {
                $videoPath = $request->file('video')->path();
            } else {
                Log::warning('No video uploaded.');
            }

            // Handle assignment file upload if present
            if ($request->hasFile('assignment')) {
                $assignmentPath = $request->file('assignment')->store('materials/assignments', 'public');
                $data['assignment_path'] = 'storage/' . $assignmentPath; // Store with the correct column name
            } else {
                Log::warning('No assignment uploaded.');
            }

            $data['professor_id'] = $professorId; // Auto-assign professor ID
            $material->fill($data); // Fill the material with the validated data
            $material->save();

            if ($videoPath) {
                $vimeoResponse = (new UploadVideoToVimeo($videoPath, $material))->handle();
                $material->update(['video_path' => $vimeoResponse]);
            }
            return $this->successResponse('Material saved successfully', ['material' => $material]);
        });
    }




    public function show(Material $material)
    {
        Log::info('hi');
        return $this->successResponse('Material retrieved successfully', ['material' => $material]);
    }



    public function submitAssignment(Request $request, $courseId, $materialId)
    {
        // Log the incoming request
        Log::info("Starting assignment submission process for Course ID: $courseId, Material ID: $materialId");

        // Validate the input for the assignment file
        $request->validate([
            'assignment' => 'required|file|mimes:pdf,docx,doc|max:10240',  // Validate file type and size
        ]);

        Log::info("Validation successful, proceeding with file upload...");

        // Get the authenticated user
        $user = Auth::user();
        Log::info("Authenticated user: User ID: " . $user->id);

        // Get the student from the 'students' table using the authenticated user's ID
        $student = Student::where('user_id', $user->id)->firstOrFail();
        Log::info("Found student: " . $student->full_name);

        // Find the material by materialId
        $material = Material::find($materialId);

        if (!$material) {
            Log::error("Material with ID $materialId not found.");
            return response()->json(['message' => 'Material not found'], 404);
        }

        Log::info("Material found: Course ID = {$material->course_id}, Batch ID = {$material->batch_id}");

        // Ensure the student is enrolled in the course and batch
        $batch = Batch::find($student->batch_id);
        if (!$batch) {
            Log::error("Batch with ID {$student->batch_id} not found.");
            return response()->json(['message' => 'Batch not found'], 404);
        }

        // Check if the student's batch has this course
        $isEnrolledInCourse = $batch->materials->contains('course_id', $courseId);
        if (!$isEnrolledInCourse) {
            Log::error("Student is not enrolled in this course. Course ID: $courseId, Student's Batch ID: {$student->batch_id}");
            return response()->json(['message' => 'You are not enrolled in this course'], 403);
        }

        // Ensure the student is in the correct batch for the material
        if ((int)$material->batch_id !== (int)$student->batch_id) {
            Log::error("Material batch mismatch. Expected Batch ID: {$material->batch_id}, Found Batch ID: {$student->batch_id}");
            return response()->json(['message' => 'Material does not belong to your batch'], 403);
        }

        // Handle file upload
        if ($request->hasFile('assignment') && $request->file('assignment')->isValid()) {
            Log::info("File upload successful. Storing assignment file...");

            // Store the file in the 'materials/submitted_assignments' directory in the public disk
            $assignmentPath = $request->file('assignment')->store('materials/submitted_assignments', 'public');
            Log::info("File stored successfully at: $assignmentPath");

            // Check if the student already submitted an assignment for this material
            $submission = AssignmentSubmission::where('student_id', $student->id)
                ->where('material_id', $materialId)
                ->where('course_id', $courseId)
                ->where('batch_id', $student->batch_id)
                ->first();

            if ($submission) {
                // Update the existing submission
                $submission->submited_assignment_path = 'storage/' . $assignmentPath;
                $submission->updated_at = now();
                $submission->save();

                Log::info("Assignment submission updated successfully in the database.");
                return response()->json(['message' => 'Assignment updated successfully']);
            } else {
                // Create a new submission if one doesn't exist
                AssignmentSubmission::create([
                    'student_id' => $student->id,
                    'material_id' => $materialId,
                    'course_id' => $courseId,
                    'batch_id' => $student->batch_id,
                    'submited_assignment_path' => 'storage/' . $assignmentPath,
                ]);

                Log::info("New assignment submission saved successfully in the database.");
                return response()->json(['message' => 'Assignment submitted successfully']);
            }
        }

        // If no file or invalid file
        Log::error("No valid assignment file provided or file is invalid.");
        return response()->json(['message' => 'No valid assignment file provided'], 400);
    }

    public function getAssignment($courseId, $materialId)
    {
        // Log the incoming request
        Log::info("Fetching assignment submissions for Course ID: $courseId, Material ID: $materialId");

        AuthHelper::checkUser();
        AuthHelper::checkProfessor();
        // Find the material by materialId
        $material = Material::find($materialId);

        if (!$material) {
            Log::error("Material with ID $materialId not found.");
            return response()->json(['message' => 'Material not found'], 404);
        }

        // Find all submissions for the given course and material
        $submissions = AssignmentSubmission::where('course_id', $courseId)
            ->where('material_id', $materialId)
            ->with('student')->get();

        if ($submissions->isEmpty()) {
            Log::info("No assignments found for Course ID: $courseId, Material ID: $materialId.");
            return response()->json(['message' => 'No assignments found'], 404);
        }

        // Return all submissions data
        Log::info("Assignments retrieved successfully.");
        return response()->json([
            'message' => 'Assignments fetched successfully',
            'data' => $submissions
        ]);
    }

    public function getTotalMarksByStudent($studentId)
    {
        // Log the request
        Log::info("Fetching total marks with course details for Student ID: $studentId.");

        // Get all assignment submissions for the given student with non-null marks
        $submissions = AssignmentSubmission::where('student_id', $studentId)
            ->whereNotNull('marks')  // Exclude assignments with NULL marks
            ->with('course') // Load course relationship
            ->get();

        if ($submissions->isEmpty()) {
            Log::info("No assignments found for Student ID: $studentId.");
            return response()->json(['message' => 'No assignments found for this student'], 404);
        }

        // Calculate total marks for each course and include course details
        $courseMarks = [];
        $totalMarksOfAllCourses = 0;

        foreach ($submissions as $submission) {
            if ($submission->course) { // Ensure course exists
                $courseId = $submission->course->id;
                if (isset($courseMarks[$courseId])) {
                    $courseMarks[$courseId]['total_marks'] += $submission->marks;
                } else {
                    $courseMarks[$courseId] = [
                        'course_id' => $courseId,
                        'course_name' => $submission->course->name ?? 'Unknown',
                        'course_description' => $submission->course->description ?? 'N/A',
                        'course_image' => $submission->course->course_image ?? 'N/A',
                        'total_marks' => $submission->marks,
                    ];
                }

                // Sum total marks across all courses
                $totalMarksOfAllCourses += $submission->marks;
            }
        }

        // Return total marks with course details
        Log::info("Total marks with course details retrieved successfully for Student ID: $studentId.");
        return response()->json([
            'message' => 'Total marks fetched successfully',
            'data' => [
                'marks_per_course' => array_values($courseMarks),
                'total_marks_of_all_courses' => $totalMarksOfAllCourses
            ],
        ]);
    }



    public function getMarksOfAssignment($courseId)
    {
        // Log the incoming request
        Log::info("Fetching total marks for assignments in Course ID: $courseId");

        AuthHelper::checkUser();
        AuthHelper::checkProfessor();

        // Get all assignment submissions for the given courseId
        $submissions = AssignmentSubmission::where('course_id', $courseId)
            ->whereNotNull('marks')  // Exclude submissions without marks
            ->get();

        if ($submissions->isEmpty()) {
            Log::info("No assignments found for Course ID: $courseId.");
            return response()->json(['message' => 'No assignments found for this course'], 404);
        }

        // Calculate total marks for each student
        $studentsMarks = [];

        foreach ($submissions as $submission) {
            // If the student already exists in the array, add their marks
            if (isset($studentsMarks[$submission->student_id])) {
                $studentsMarks[$submission->student_id]['total_marks'] += $submission->marks;
            } else {
                // Otherwise, initialize the student record
                $studentsMarks[$submission->student_id] = [
                    'student_id' => $submission->student_id,
                    'total_marks' => $submission->marks,
                ];
            }
        }

        // Return total marks for each student
        Log::info("Marks retrieved successfully for Course ID: $courseId.");
        return response()->json([
            'message' => 'Marks fetched successfully',
            'data' => $studentsMarks,
        ]);
    }





    public function assignAssignmentMarks(Request $request, $courseId, $materialId, $studentId)
    {
        // Validate the request
        $request->validate([
            'marks' => 'required|numeric|min:0|max:100',
        ]);

        // Get the authenticated user
        $user = Auth::user();

        // Check if user is a professor
        $professor = $user->professor;
        if (!$professor) {
            Log::error("Unauthorized access. User ID {$user->id} is not a professor.");
            return response()->json(['message' => 'Unauthorized. Only assigned professors can grade assignments.'], 403);
        }

        // Find the course
        $course = Course::find($courseId);
        if (!$course) {
            Log::error("Course not found with ID: {$courseId}");
            return response()->json(['message' => 'Course not found.'], 404);
        }

        // Check if the professor is assigned to the course
        if ($course->professor_id !== $professor->id) {
            Log::error("Professor ID {$professor->id} is not assigned to Course ID {$courseId}.");
            return response()->json(['message' => 'Forbidden. You are not assigned to this course.'], 403);
        }

        // Find the student's assignment submission
        $assignment = AssignmentSubmission::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->where('material_id', $materialId)
            ->first();

        if (!$assignment) {
            Log::error("Assignment not found for Student ID: {$studentId}, Course ID: {$courseId}, Material ID: {$materialId}");
            return response()->json(['message' => 'Assignment not found.'], 404);
        }

        // Assign marks
        $assignment->marks = $request->marks;
        $assignment->save();

        Log::info("Marks assigned successfully. Student ID: {$studentId}, Course ID: {$courseId}, Material ID: {$materialId}, Marks: {$request->marks}");

        return response()->json(['message' => 'Marks assigned successfully']);
    }

    public function getAssignmentMarks($courseId, $studentId)
    {
        // Log the incoming request
        Log::info("Fetching assignment marks for Student ID: $studentId in Course ID: $courseId");

        // Get all assignment submissions for the given courseId and studentId
        $submissions = AssignmentSubmission::where('course_id', $courseId)
            ->where('student_id', $studentId)
            ->whereNotNull('marks')  // Exclude submissions without marks
            ->get();

        if ($submissions->isEmpty()) {
            Log::info("No assignments found for Student ID: $studentId in Course ID: $courseId.");
            return response()->json(['message' => 'No assignments found for this student in this course'], 404);
        }

        // Calculate total marks for the student
        $totalMarks = $submissions->sum('marks');  // Sum up the marks from all submissions

        // Return the total marks for the student along with their submissions
        Log::info("Assignment marks retrieved successfully for Student ID: $studentId in Course ID: $courseId.");
        return response()->json([
            'message' => 'Marks fetched successfully',
            'data' => [
                'student_id' => $studentId,
                'course_id' => $courseId,
                'total_marks' => $totalMarks,
                'submissions' => $submissions,
            ]
        ]);
    }

    public function getExpectedMarks()
    {
        // Log the request
        Log::info("Fetching expected total marks per course.");

        // Fetch all materials with their related courses
        $materials = Material::with('course')->get();

        if ($materials->isEmpty()) {
            Log::info("No materials found.");
            return response()->json(['message' => 'No materials found'], 404);
        }

        // Initialize variables
        $courseMarks = [];
        $totalExpectedMarks = 0;

        foreach ($materials as $material) {
            if ($material->course) { // Ensure course exists
                $courseId = $material->course->id;

                if (isset($courseMarks[$courseId])) {
                    $courseMarks[$courseId]['total_marks'] += $material->marks;
                } else {
                    $courseMarks[$courseId] = [
                        'course_id' => $courseId,
                        'course_name' => $material->course->name ?? 'Unknown',
                        'course_description' => $material->course->description ?? 'N/A',
                        'course_image' => $material->course->course_image ?? 'N/A',
                        'total_marks' => $material->marks,
                    ];
                }

                // Add to the total expected marks across all courses
                $totalExpectedMarks += $material->marks;
            }
        }

        // Return expected marks grouped by course with grand total
        Log::info("Expected marks retrieved successfully.");
        return response()->json([
            'message' => 'Total expected marks fetched successfully',
            'data' => [
                'courses' => array_values($courseMarks),
                'total' => $totalExpectedMarks
            ]
        ]);
    }




    public function getTotalMarksForCourse($courseId)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Check if the user is a student
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            Log::error("Unauthorized access. User ID {$user->id} is not a student.");
            return response()->json(['message' => 'Unauthorized. Only students can access their marks.'], 403);
        }

        // Retrieve all assignments submitted by the student for the course
        $totalMarks = AssignmentSubmission::where('student_id', $student->id)
            ->where('course_id', $courseId)
            ->sum('marks'); // Sum up all marks

        Log::info("Total marks calculated for Student ID: {$student->id}, Course ID: {$courseId}, Total Marks: {$totalMarks}");

        return response()->json([
            'message' => 'Total marks retrieved successfully',
            'student_id' => $student->id,
            'course_id' => $courseId,
            'total_marks' => $totalMarks,
        ]);
    }


    public function destroy($id)
    {
        AuthHelper::checkUser();
        AuthHelper::checkProfessor();

        $material = Material::find($id);
        if (!$material) {
            return $this->errorResponse('Material not found', 404);
        }

        $material->delete();

        return $this->successResponse('Material deleted successfully', ['material' => $material]);
    }
}
