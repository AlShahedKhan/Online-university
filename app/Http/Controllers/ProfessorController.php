<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\Professor;
use App\Helpers\AuthHelper;
use App\Models\BatchCourse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Traits\HandlesApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\ProfessorAccountCreated;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProfessorController extends Controller
{
    use HandlesApiResponse;

    public function index()
    {
        return $this->safeCall(function () {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();
            if (!Professor::exists()) {
                return $this->errorResponse('No professors found', 404);
            }
            $professors = Professor::orderBy('created_at', 'desc')->paginate(10);
            return $this->successResponse('Professors retrieved successfully', ['professors' => $professors]);
        });
    }

    public function getBatch()
    {
        return $this->safeCall(function () {
            AuthHelper::checkUser();
            AuthHelper::checkProfessor();
            $user = Auth::user();
            $professor = Professor::where('user_id', $user->id)->first();

            if (!$professor) {
                return $this->errorResponse('Professor not found', 404);
            }

            // Fetch all batches assigned to the professor
            $batches = BatchCourse::where('professor_id', $professor->id) // Corrected to professor_id
                ->paginate(10);

            return $this->successResponse('Professor batches retrieved successfully', ['batch' => $batches]);
        });
    }

    // public function getCourse($id)
    // {
    //     return $this->safeCall(function () use ($id) {
    //         AuthHelper::checkUser();
    //         AuthHelper::checkProfessor();
    //         $user = Auth::user();
    //         $professor = Professor::where('user_id', $user->id)->first();

    //         if (!$professor) {
    //             return $this->errorResponse('Professor not found', 200);
    //         }

    //         // Fetch all courses for the given batch_id and professor_id
    //         $courses = BatchCourse::where('professor_id', $professor->id)
    //             ->where('batch_id', $id) // Filter by batch_id
    //             ->with('course.materials') // Include related course and materials data
    //             ->get(); // Retrieve all matching courses

    //         if ($courses->isEmpty()) {
    //             return $this->errorResponse('No courses found for this batch', 200);
    //         }

    //         // Transform the courses data to only return the course details and total materials
    //         $transformedCourses = $courses->map(function ($batchCourse) {
    //             return [
    //                 'course' => [
    //                     'id' => $batchCourse->course->id,
    //                     'name' => $batchCourse->course->name,
    //                     'description' => $batchCourse->course->description,
    //                     'course_image' => $batchCourse->course->course_image,
    //                     'status' => $batchCourse->course->status,
    //                     'total_materials' => $batchCourse->course->materials->count(), // Adding total materials count
    //                 ],
    //             ];
    //         });

    //         return $this->successResponse('Courses retrieved successfully', ['courses' => $transformedCourses]);
    //     });
    // }

    public function getCourse($id)
    {
        return $this->safeCall(function () use ($id) {
            AuthHelper::checkUser();
            AuthHelper::checkProfessor();
            $user = Auth::user();
            $professor = Professor::where('user_id', $user->id)->first();

            if (!$professor) {
                return $this->errorResponse('Professor not found', 200);
            }

            // Fetch all courses for the given batch_id and professor_id
            $courses = BatchCourse::where('professor_id', $professor->id)
                ->where('batch_id', $id) // Filter by batch_id
                ->with('course.materials') // Include related course and materials data
                ->get(); // Retrieve all matching courses

            if ($courses->isEmpty()) {
                return $this->errorResponse('No courses found for this batch', 200);
            }

            // Transform the courses data to only return the course details and total materials
            $transformedCourses = $courses->map(function ($batchCourse) {
                return [
                    'id' => $batchCourse->course->id,
                    'name' => $batchCourse->course->name,
                    'description' => $batchCourse->course->description,
                    'course_image' => $batchCourse->course->course_image,
                    'status' => $batchCourse->course->status,
                    'total_materials' => $batchCourse->course->materials->count(), // Adding total materials count
                ];
            });

            // Return only the array of courses, no "courses" key
            return $this->successResponse('Courses retrieved successfully', ['course' => $transformedCourses]);
        });
    }

    public function getMaterials($batch_id, $course_id)
    {
        return $this->safeCall(function () use ($batch_id, $course_id) {
            AuthHelper::checkUser();
            AuthHelper::checkProfessor();
            $user = Auth::user();
            $professor = Professor::where('user_id', $user->id)->first();

            if (!$professor) {
                return $this->errorResponse('Professor not found', 404);
            }

            $course = BatchCourse::where('professor_id', $professor->id)
                ->where('batch_id', $batch_id)
                ->where('course_id', $course_id)
                ->with('course.materials')
                ->first();

            if (!$course || !$course->course) {
                return $this->errorResponse('Course not found or not assigned to this professor', 200);
            }

            $materials = $course->course->materials;

            if ($materials->isEmpty()) {
                return $this->errorResponse('No materials found for this course', 200);
            }

            return $this->successResponse('Materials retrieved successfully', ['materials' => $materials]);
        });
    }


    public function getProfessorCourseLists()
    {
        // Use safeCall to handle any errors
        return $this->safeCall(function () {
            // Get the authenticated professor
            $professor = Professor::with([
                'user', // Eager load the related user to get the login_id
                'courses.materials',  // Eager load courses and their related materials
            ])->where('user_id', Auth::id())->firstOrFail(); // Assuming the user_id is tied to the professor

            return $this->successResponse('Professor course lists fetched successfully.', ['professor' => $professor]);
        });
    }



    public function storeOrUpdate(Request $request, int $id = null): JsonResponse
    {
        return $this->safeCall(function () use ($request, $id) {
            // Validate the request
            $validated = $request->validate([
                'data' => 'required|string',
                'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg',
            ]);

            AuthHelper::checkUser();
            AuthHelper::checkAdmin();

            // Decode JSON 'data' into an array
            $data = json_decode($validated['data'], true);
            if (!is_array($data)) {
                return $this->errorResponse('The data field must be a valid JSON array.', 400);
            }

            // Merge data for further validation
            $request->merge($data);

            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email_address' => 'required|email|unique:professors,email_address,' . $id,
                'phone_number' => 'nullable|string',
                'designation' => 'nullable|string',
                'address' => 'nullable|string',
                'postal_code' => 'nullable|string',
                'employee_id' => 'nullable|string',
                'blood_group' => 'nullable|string',
                'gender' => 'nullable|in:Male,Female,Other',
                'user_status' => 'nullable|in:Active,Inactive',
                'description' => 'nullable|string',
            ]);

            // Handle image upload
            if ($request->hasFile('profile_picture')) {
                $imagePath = $request->file('profile_picture')->store('profile_pictures', 'public');
                $data['profile_picture'] = $imagePath;
            }

            // Check if a user already exists for this professor
            $user = User::where('email', $data['email_address'])->first();

            if (!$user) {
                // Generate a random password
                $randomPassword = \Str::random(12);

                // Create a new user account
                $user = User::create([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email_address'],
                    'password' => Hash::make($randomPassword),
                    'is_professor' => 1, // Mark as professor
                    'is_admin' => 0, // Default to non-admin
                    'is_approved' => 1, // Mark as approved
                ]);

                // Send email with login credentials
                Mail::to($user->email)->send(new ProfessorAccountCreated($user, $randomPassword));
            }

            // Assign user_id to the professor data
            $data['user_id'] = $user->id;

            // Create or update professor record
            $professor = Professor::updateOrCreate(
                ['id' => $id],
                $data
            );

            return $this->successResponse(
                'Professor data stored or updated successfully.',
                $professor->toArray()
            );
        });
    }

    public function getProfessorDetails($id)
    {
        return $this->safeCall(function () use ($id) {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();
            $professor = Professor::with(['students', 'materials'])->find($id);

            if (!$professor) {
                return response()->json(['message' => 'Professor not found'], 404);
            }

            $total_students = $professor->students->count();
            $total_materials = $professor->materials->count();
            $materials_list = $professor->materials;

            return response()->json([
                'professor' => $professor,
                'total_students' => $total_students,
                'total_materials' => $total_materials,
                'materials' => $materials_list
            ]);
        });
    }

    public function getBatchStudentLists($id)
    {
        return $this->safeCall(function () use ($id) {
            AuthHelper::checkUser();
            AuthHelper::checkProfessor();
            $students = Student::where('batch_id', $id)->paginate(10);
            return $this->successResponse('Students retrieved successfully', ['students' => $students]);
        });
    }

    public function getBatchList()
    {
        return $this->safeCall(function () {
            AuthHelper::checkUser();
            AuthHelper::checkProfessor();
            $user = Auth::user();
            $professor = Professor::where('user_id', $user->id)->first();
            if (!$professor) {
                return $this->errorResponse('Professor not found', 404);
            }
            $batches = $professor->batches()->with([
                'materials.course',
                'students' => function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'email', 'batch_id');
                }
            ])->get();

            $totalBatches = $batches->count();
            $totalStudents = $batches->sum(function ($batch) {
                return $batch->students->count(); // Count students for each batch
            });
            $totalMaterials = $batches->sum(function ($batch) {
                return $batch->materials->count(); // Count materials for each batch
            });
            $totalCourses = $batches->sum(function ($batch) {
                return $batch->materials->pluck('course')->unique()->count(); // Count unique courses per batch
            });

            return $this->successResponse('Batches retrieved successfully', [
                'batches' => $batches,
                'total_batches' => $totalBatches,
                'total_students' => $totalStudents,
                'total_materials' => $totalMaterials,
                'total_courses' => $totalCourses
            ]);
        });
    }

    public function getBatchStudentsWithResults()
    {
        return $this->safeCall(function () {
            Log::info("Starting getBatchStudentsWithResults process...");

            // Check if user is authenticated
            AuthHelper::checkUser();
            Log::info("User authenticated: User ID " . Auth::id());

            // Ensure the user is a professor
            AuthHelper::checkProfessor();
            Log::info("User is a professor.");

            // Get the authenticated user
            $user = Auth::user();
            Log::info("Fetching professor details for User ID: " . $user->id);

            // Retrieve professor data
            $professor = Professor::where('user_id', $user->id)->first();
            if (!$professor) {
                Log::error("Professor record not found for User ID: " . $user->id);
                return $this->errorResponse('Professor not found', 404);
            }
            Log::info("Professor found: Professor ID " . $professor->id);

            // Retrieve batches assigned to the professor
            $batches = $professor->batches()->with([
                'students.results' => function ($query) {
                    $query->select('id', 'student_table_id', 'result', 'remarks', 'course_id'); // Get results related to each student
                }
            ])->get();

            // Check if batches are found
            if ($batches->isEmpty()) {
                Log::warning("No batches found for Professor ID: " . $professor->id);
                return $this->errorResponse('No batches found for this professor', 404);
            }

            // Prepare the response data
            $batchData = $batches->map(function ($batch) {
                // For each batch, retrieve the students and their results
                $students = $batch->students->map(function ($student) {
                    // For each student, include their results
                    $results = $student->results; // This assumes that the student model has a 'results' relationship defined
                    return [
                        'student_id' => $student->id,
                        'first_name' => $student->first_name,
                        'last_name' => $student->last_name,
                        'email' => $student->email,
                        'results' => $results // Include the results
                    ];
                });

                return [
                    'id' => $batch->id,
                    'title' => $batch->title,
                    'batch_image' => $batch->batch_image,

                    'students' => $students // Include student list with their results
                ];
            });

            Log::info("Returning batch data with students and their results for Professor ID: " . $professor->id);

            // Return the final response
            return $this->successResponse('Batches with students and results retrieved successfully', ['batch_data' => $batchData]);
        });
    }

    public function destroy($id)
    {

        return $this->safeCall(function () use ($id) {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();
            try {
                $professor = Professor::findOrFail($id);
                $professor->delete();
                return $this->successResponse('Professor deleted successfully', ['professor' => $professor]);
            } catch (ModelNotFoundException $e) {
                return $this->errorResponse('Professor not found', 404);
            }
        });
    }
}
