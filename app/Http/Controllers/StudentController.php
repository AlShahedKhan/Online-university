<?php

namespace App\Http\Controllers;

use App\Models\MCQ;
use App\Models\User;
use App\Models\Batch;
use App\Models\Course;
use App\Models\Student;
use App\Models\Material;
use App\Helpers\AuthHelper;
use App\Models\BatchCourse;
use App\Models\Certificate;
use Illuminate\Http\Request;
use App\Models\StudentAnswer;
use Illuminate\Http\JsonResponse;
use App\Traits\HandlesApiResponse;
use Illuminate\Support\Facades\Log;
use App\Models\AssignmentSubmission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    use HandlesApiResponse;

    public function index()
    {
        return $this->safeCall(function () {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();
            $students = Student::orderBy('created_at', 'desc')->paginate(10);
            return $this->successResponse('Students retrieved successfully', ['students' => $students]);
        });
    }

    public function show(Student $student)
    {
        return $this->safeCall(function () use ($student) {
            if (Auth::user()->is_admin) {
                return $this->successResponse('Student retrieved successfully', ['student' => $student]);
            }
            if (Auth::user()->is_professor) {
                $professor = Auth::user()->professor;
                if ($professor->id == $student->professor_id) {
                    return $this->successResponse('Student retrieved successfully', ['student' => $student]);
                }
            }

            return $this->successResponse('Student retrieved successfully', ['student' => $student]);
        });
    }

    public function update(Request $request): JsonResponse
    {
        return $this->safeCall(function () use ($request) {
            // Ensure the user is authenticated
            AuthHelper::checkUser();

            $user = Auth::user();

            // Decode 'data' field
            $data = json_decode($request->input('data'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->errorResponse('The data field must be a valid JSON object.', 400);
            }

            Log::info('Request data:', $data);

            // Ensure email cannot be changed
            unset($data['email']);

            if (!$user->is_admin) {
                unset($data['student_id']);
            }

            // Validate data including optional password
            $validated = validator($data, [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'phone_number' => 'nullable|string',
                'address' => 'nullable|string',
                'postal_code' => 'nullable|string',
                'student_id' => 'nullable|string',
                'blood_group' => 'nullable|string',
                'gender' => 'nullable|in:Male,Female,Other',
                'user_status' => 'nullable|in:Active,Inactive',
                'description' => 'nullable|string',
                'password' => 'nullable|string|min:8|confirmed', // Password is optional but must be confirmed
            ])->validate();

            // Handle profile picture update
            if ($request->hasFile('profile_picture')) {
                if ($user->profile_picture) {
                    Storage::disk('public')->delete($user->profile_picture);
                }
                $imagePath = $request->file('profile_picture')->store('student_profiles', 'public');
                $validated['profile_picture'] = $imagePath;
                Log::info('Profile picture updated successfully.');
            }

            // Ensure the validated data is not empty before updating
            if (empty($validated)) {
                return $this->errorResponse('No valid data to update.', 400);
            }

            // Update student record (excluding email)
            $student = $user->student; // Assuming the user has a related student model
            $student->update($validated);
            Log::info('Student information updated successfully.');

            // Update password if a new password is provided
            if (!empty($data['password'])) {
                $user->update(['password' => Hash::make($data['password'])]);
                Log::info('Password updated successfully.');
            }

            // Return full student data, including fields from the students table
            return $this->successResponse(
                'Student information updated successfully.',
                ['student' => $student] // Return full student data
            );
        });
    }



    public function getStudentCoursesWithMaterialsAndProfessor()
    {
        // Get the authenticated user
        $user = Auth::user();

        // Check if the user is a student
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            Log::error("Unauthorized access. User ID {$user->id} is not a student.");
            return response()->json(['message' => 'Unauthorized. Only students can access their courses.'], 403);
        }

        // Retrieve all courses associated with the student's batch
        $courses = BatchCourse::where('batch_id', $student->batch_id)->with('course.materials')->paginate(10);
        // $courses = Course::whereHas('materials', function ($query) use ($student) {
        //     $query->where('batch_id', $student->batch_id);
        // })
        //     ->with([
        //         'materials', // Load all course materials
        //         'professor:id,first_name,last_name,email_address,phone_number,designation' // Load professor details
        //     ])
        //     ->get();

        if ($courses->isEmpty()) {
            Log::info("No courses found for Student ID: {$student->id}, Batch ID: {$student->batch_id}");
            return response()->json(['message' => 'No courses found for your batch.'], 404);
        }

        Log::info("Retrieved courses, materials, and professors for Student ID: {$student->id}");

        return response()->json([
            'message' => 'Courses, materials, and professors retrieved successfully',
            'student_id' => $student->id,
            'batch_id' => $student->batch_id,
            'courses' => $courses,
        ]);
    }
    public function getCourses()
    {
        return $this->safeCall(function () {
            AuthHelper::checkUser();
            $user = Auth::user();

            // Retrieve the student record for the authenticated user
            $student = Student::where('user_id', $user->id)->first();

            // Check if student record exists
            if (!$student) {
                return $this->errorResponse('Student record not found', 404);
            }

            // Retrieve courses for the student's batch
            $courses = BatchCourse::where('batch_id', $student->batch_id)
                ->with('course.materials') // Ensure materials are loaded for each course
                ->paginate(10);

            // Manually transform the courses data to remove unwanted fields and add total materials
            $transformedCourses = $courses->map(function ($batchCourse) {
                return [
                    'id' => $batchCourse->course->id,
                    'name' => $batchCourse->course->name,
                    'description' => $batchCourse->course->description,
                    'course_image' => $batchCourse->course->course_image,
                    // Add total materials count here
                    'total_materials' => $batchCourse->course->materials->count(),
                ];
            });

            // Return only the array of courses, no "courses" key
            return $this->successResponse('Student courses retrieved successfully', [
                'course' => $transformedCourses,
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'total' => $courses->total(),
            ]);
        });
    }

    public function getMaterials($id)
    {
        return $this->safeCall(function () use ($id) {
            AuthHelper::checkUser();
            $user = Auth::user();

            // Retrieve the student record for the authenticated user
            $student = Student::where('user_id', $user->id)->first();

            // Check if student record exists
            if (!$student) {
                return $this->errorResponse('Student record not found', 404);
            }

            // Retrieve the materials for the student's batch and course
            $materials = BatchCourse::where('batch_id', $student->batch_id)
                ->where('course_id', $id)
                ->with('course.materials') // Eager load materials
                ->first();

            // Check if materials are found
            if (!$materials) {
                return $this->errorResponse('Materials not found for this course', 404);
            }

            // Extract materials from the related course
            $materialList = $materials->course->materials;

            // Return only the materials, not the batchCourse or course data
            return $this->successResponse('Student materials retrieved successfully', ['materials' => $materialList]);
        });
    }

    public function getStudentCourses(Request $request)
    {
        return $this->safeCall(function () use ($request) {
            AuthHelper::checkUser();
            $user = Auth::user();

            $student = Student::with('batch') // Eager load the batch relationship
                ->where('user_id', Auth::user()->id)
                ->first();
            Log::info("Student ID: {$student->id}, Batch ID: {$student->batch_id}, Course ID: {$request->course_id}");




            return $this->successResponse('Student courses retrieved successfully', ['student' => $student]);
        });
    }

    public function getCoursesAndMaterials()
    {
        return $this->safeCall(function () {

            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'Unauthorized. Only students can access their courses.'], 403);
            }

            $student = Student::with(['batch.courses.materials'])->where('user_id', $user->id)->first();

            if (!$student) {
                return response()->json(['message' => 'Student not found.'], 404);
            }


            return $this->successResponse('Student details and courses retrieved successfully', [
                'student' => $student,
            ]);
        });
    }

    public function getResults()
    {
        return $this->safeCall(function () {
            $user = Auth::user();
            $student = Student::with(['results.course'])->where('user_id', $user->id)->paginate(10);
            return $this->successResponse('Student results retrieved successfully', ['student' => $student]);
        });
    }

    public function getCertificate($studentId)
    {
        return $this->safeCall(function () use ($studentId) {
            AuthHelper::checkAdmin();
            AuthHelper::checkUser();
            $user = Auth::user();

            $student = Student::with(['results'])->where('student_id', $studentId)->first();

            if (!$student) {
                Log::error("Student with ID {$studentId} not found.");
                return $this->errorResponse("Student not found", 404);
            }

            Log::info("Student Retrieved: ", ['student' => $student]);

            return $this->successResponse('Student certificate retrieved successfully', ['student' => $student]);
        });
    }


    public function calculateCGPA($student_id)
    {
        // Check if the user is logged in and is an admin
        AuthHelper::checkUser();
        AuthHelper::checkAdmin();

        $userID = Auth::id();

        // Only an admin can view other students' CGPA
        if (!Auth::user()->is_admin) {
            return response()->json([
                'message' => 'You do not have permission to view this information.'
            ], 403);
        }

        // Fetch the student record by student_id, including related batch and courses
        $student = Student::where('id', $student_id)->with('batch.courses')->first();

        // Check if student exists
        if (!$student) {
            return response()->json([
                'message' => 'Student not found.'
            ], 404);
        }

        $studentId = $student->id;
        $isEnrolledInAnyCourse = $student->batch->courses()->exists();

        if (!$isEnrolledInAnyCourse) {
            return response()->json([
                'message' => 'Student is not enrolled in any course.'
            ], 403);
        }

        $totalCredits = $student->batch->courses->sum('credit');

        $totalMarks = StudentAnswer::where('student_id', $studentId)
            ->where('is_correct', 1)
            ->count();

        $totalQuestionsInAllCourses = Mcq::count();

        $mcqPercentage = ($totalQuestionsInAllCourses > 0)
            ? ($totalMarks / $totalQuestionsInAllCourses) * 100
            : 0;

        $totalMarksInAllCourses = AssignmentSubmission::where('student_id', $studentId)
            ->sum('marks');

        $totalExpectedAssignmentMarksInAllCourses = Material::sum('marks');

        $totalAssignmentMarksInAllCourses = AssignmentSubmission::where('student_id', $studentId)
            ->sum('marks');
        $assignmentPercentage = ($totalExpectedAssignmentMarksInAllCourses > 0)
            ? ($totalAssignmentMarksInAllCourses / $totalExpectedAssignmentMarksInAllCourses) * 100
            : 0;

        // Convert MCQ percentage to CGPA using provided scale
        $mcqCGPA = $this->convertPercentageToCGPA($mcqPercentage);

        // Convert Assignment percentage to CGPA using provided scale
        $assignmentCGPA = $this->convertPercentageToCGPA($assignmentPercentage);

        // Calculate overall CGPA by averaging the two CGPAs (MCQ and Assignment)
        $totalCGPA = ($mcqCGPA + $assignmentCGPA) / 2;

        return response()->json([
            'student' => [
                'id' => $student->id,
                'profile_picture' => $student->profile_picture,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'email' => $student->email,
                'phone_number' => $student->phone_number,
                'program' => $student->program,
                'address' => $student->address,
                'postal_code' => $student->postal_code,
                'blood_group' => $student->blood_group,
                'gender' => $student->gender,
                'user_status' => $student->user_status,
                'description' => $student->description,
                'batch_title' => $student->batch->title,
                'created_at' => $student->created_at,
                'updated_at' => $student->updated_at
            ],
            'total_assignment_marks_expected' => $totalExpectedAssignmentMarksInAllCourses, // Total marks expected from assignments
            'total_marks_gained_by_student' => $totalMarksInAllCourses, // Total marks gained by student
            'total_marks_expected_in_mcq' => $totalQuestionsInAllCourses, // Total marks expected in MCQs
            'total_marks_gained_in_mcq' => $totalMarks, // Total marks gained by student in MCQs
            'total_credits_registered_for' => $totalCredits, // Total credits student is registered for
            'mcq_percentage' => round($mcqPercentage, 2), // MCQ percentage
            'assignment_percentage' => round($assignmentPercentage, 2), // Assignment percentage
            'cgpa' => round($totalCGPA, 2) // Total CGPA Calculation result
        ]);
    }



    private function convertPercentageToCGPA($percentage)
    {
        if ($percentage < 60) {
            return 0; // Less than 60% gets a CGPA of 0
        } elseif ($percentage >= 60 && $percentage < 70) {
            return 2.0; // 60% → 2.0 CGPA
        } elseif ($percentage >= 70 && $percentage < 75) {
            return 2.5; // 70% → 2.5 CGPA
        } elseif ($percentage >= 75 && $percentage < 80) {
            return 2.8; // 75% → 2.8 CGPA
        } elseif ($percentage >= 80 && $percentage < 85) {
            return 3.0; // 80% → 3.0 CGPA
        } elseif ($percentage >= 85 && $percentage < 90) {
            return 3.3; // 85% → 3.3 CGPA
        } elseif ($percentage >= 90 && $percentage < 95) {
            return 3.7; // 90% → 3.7 CGPA
        } elseif ($percentage >= 95) {
            return 4.0; // 95% → 4.0 CGPA
        }
    }

    public function createCertificate(Request $request, $student_id)
    {
        // Check if the user is logged in and is an admin
        AuthHelper::checkUser();
        AuthHelper::checkAdmin();

        // Validate the 'issued_by' field is provided in the request
        $validated = $request->validate([
            'issued_by' => 'required|string|max:255',  // Ensure 'issued_by' is provided
        ]);

        // Get the 'issued_by' from the validated input
        $issuedBy = $validated['issued_by'];

        // Fetch the student record by student_id, including related batch and courses
        $student = Student::where('id', $student_id)->with('batch.courses')->first();

        // Check if student exists
        if (!$student) {
            return response()->json([
                'message' => 'Student not found.'
            ], 404);
        }

        // Calculate CGPA using the same logic from the calculateCGPA method
        $totalCredits = $student->batch->courses->sum('credit');
        $totalMarks = StudentAnswer::where('student_id', $student_id)
            ->where('is_correct', 1)
            ->count();

        $totalQuestionsInAllCourses = Mcq::count();
        $mcqPercentage = ($totalQuestionsInAllCourses > 0)
            ? ($totalMarks / $totalQuestionsInAllCourses) * 100
            : 0;

        $totalMarksInAllCourses = AssignmentSubmission::where('student_id', $student_id)
            ->sum('marks');

        $totalExpectedAssignmentMarksInAllCourses = Material::sum('marks');
        $totalAssignmentMarksInAllCourses = AssignmentSubmission::where('student_id', $student_id)
            ->sum('marks');

        $assignmentPercentage = ($totalExpectedAssignmentMarksInAllCourses > 0)
            ? ($totalAssignmentMarksInAllCourses / $totalExpectedAssignmentMarksInAllCourses) * 100
            : 0;

        // Convert MCQ percentage to CGPA using provided scale
        $mcqCGPA = $this->convertPercentageToCGPA($mcqPercentage);

        // Convert Assignment percentage to CGPA using provided scale
        $assignmentCGPA = $this->convertPercentageToCGPA($assignmentPercentage);

        // Calculate overall CGPA by averaging the two CGPAs (MCQ and Assignment)
        $totalCGPA = ($mcqCGPA + $assignmentCGPA) / 2;

        // Store the certificate details in the database
        $certificate = Certificate::create([
            'student_id' => $student->id,
            'stu_id' => $student->student_id,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'program' => $student->program,
            'batch' => $student->batch->title,
            'cgpa' => round($totalCGPA, 2),
            'certificate_date' => now()->toDateString(),
            'issued_by' => $issuedBy,  // Use the 'issued_by' from the input
            'approved' => false, // By default, the certificate is not approved
        ]);

        return response()->json([
            'message' => 'Certificate created successfully.',
            'certificate' => $certificate
        ]);
    }


    public function getCertificateForAdmin()
    {
       return $this->safeCall(function () {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();
            $certificates = Certificate::orderBy('created_at', 'desc')->paginate(10);
            return $this->successResponse('Certificates retrieved successfully', ['certificates' => $certificates]);
       });
    }

    public function approveCertificate($certificate_id)
    {
        AuthHelper::checkUser();
        AuthHelper::checkAdmin();
        $certificate = Certificate::find($certificate_id);
        if (!$certificate) {
            return response()->json([
                'message' => 'Certificate not found.'
            ], 404);
        }
        $certificate->approved = true;
        $certificate->save();
        return response()->json([
            'message' => 'Certificate approved successfully.',
            'certificate' => $certificate
        ]);
    }

    public function getCertificateForStudent()
    {
        return $this->safeCall(function () {
            $userId = Auth::id(); // Get the authenticated user's ID
            Log::info("User ID: ", ['userId' => $userId]);

            // Get the student record for the user
            $student = Student::where('user_id', $userId)->first();
            Log::info("Student Record: ", ['student' => $student]);

            // If student doesn't exist, return an error
            if (!$student) {
                Log::warning("Student not found for user", ['userId' => $userId]);
                return $this->errorResponse('Student not found', 404);
            }

            // Check if the student's certificate exists and is approved
            $certificate = Certificate::where('student_id', $student->id)  // Use student_id here
                ->where('approved', 1) // Ensure the certificate is approved
                ->first(); // Use 'first' to get a single certificate (not a collection)

            // Log the certificate result for debugging
            Log::info("Certificate Retrieved: ", ['certificate' => $certificate]);

            if (!$certificate) {
                // Log when certificate is not found
                Log::warning("Certificate not found or not approved for user", ['userId' => $userId]);
                return $this->errorResponse('Certificate not found or not approved', 404);
            }

            // If the certificate is found and approved
            Log::info("Certificate successfully retrieved for user", ['certificate' => $certificate]);
            return $this->successResponse('Certificate retrieved successfully', ['certificate' => $certificate]);
        });
    }

    public function destroy(Certificate $certificate)
    {
        return $this->safeCall(function () use ($certificate) {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();
            $certificate->delete();
            return $this->successResponse('Certificate deleted successfully');
        });
    }
}
