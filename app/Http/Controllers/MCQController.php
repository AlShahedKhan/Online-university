<?php

namespace App\Http\Controllers;

use App\Models\MCQ;
use App\Models\Course;
use App\Models\Student;
use App\Models\Material;
use App\Models\BatchCourse;
use Illuminate\Http\Request;
use App\Models\StudentAnswer;
use App\Traits\HandlesApiResponse;
use Illuminate\Support\Facades\Log;
use App\Models\AssignmentSubmission;
use Illuminate\Support\Facades\Auth;

class MCQController extends Controller
{
    use HandlesApiResponse;

    public function index($course_id, $material_id)
    {
        return $this->safeCall(function () use ($course_id, $material_id) {
            // Validate course and material existence
            $course = Course::find($course_id);
            if (!$course) {
                return $this->errorResponse('Course not found', 404);
            }

            $material = Material::where('id', $material_id)
                ->where('course_id', $course_id)
                ->first();

            if (!$material) {
                return $this->errorResponse('Material not found for this course', 404);
            }

            // Fetch all MCQs for this material
            $mcqs = MCQ::where('course_id', $course_id)
                ->where('material_id', $material_id)
                ->get();

            if ($mcqs->isEmpty()) {
                return $this->errorResponse('No MCQs found for this material', 404);
            }

            return $this->successResponse('MCQs retrieved successfully', ['mcqs' => $mcqs]);
        });
    }

    public function storeOrUpdate(Request $request, $id = null)
    {
        return $this->safeCall(function () use ($request, $id) {
            // Validation for MCQs
            $request->validate([
                'course_id' => 'required|exists:courses,id',
                'material_id' => 'required|exists:materials,id',
                'mcqs' => 'required|array',
                'mcqs.*.question' => 'required|string|max:255',
                'mcqs.*.answers' => 'required|array|min:2',
                'mcqs.*.correct_answer' => 'required|string|max:255',
            ]);

            // Fetch course and material
            $course = Course::findOrFail($request->course_id);
            $material = Material::findOrFail($request->material_id);

            // If MCQ is being updated, find the existing MCQ
            if ($id) {
                $existingMCQ = MCQ::find($id);
                if (!$existingMCQ) {
                    return $this->errorResponse('MCQ not found', 404);
                }

                // Ensure there is at least one MCQ item in the request
                if (!isset($request->mcqs[0])) {
                    return $this->errorResponse('Invalid MCQ update data', 400);
                }

                // Extract the first MCQ item (since update only affects one MCQ at a time)
                $mcqData = $request->mcqs[0];

                // Update the existing MCQ
                $existingMCQ->update([
                    'question' => $mcqData['question'],
                    'answers' => json_encode($mcqData['answers']),
                    'correct_answer' => $mcqData['correct_answer'],
                ]);

                return $this->successResponse('MCQ updated successfully', ['mcq' => $existingMCQ]);
            }

            // Create new MCQs
            $mcqsData = [];
            foreach ($request->mcqs as $mcqData) {
                $mcqsData[] = new MCQ([
                    'course_id' => $course->id,
                    'material_id' => $material->id,
                    'question' => $mcqData['question'],
                    'answers' => json_encode($mcqData['answers']),
                    'correct_answer' => $mcqData['correct_answer'],
                ]);
            }

            // Save the MCQs for the material
            $material->mcqs()->saveMany($mcqsData);

            return $this->successResponse('MCQs created successfully', ['mcqs' => $mcqsData]);
        });
    }

    public function destroy($course_id, $material_id, $mcq_id)
    {
        return $this->safeCall(function () use ($course_id, $material_id, $mcq_id) {
            // Validate if the course exists
            $course = Course::find($course_id);
            if (!$course) {
                return $this->errorResponse('Course not found', 404);
            }

            // Validate if the material exists within the course
            $material = Material::where('id', $material_id)
                ->where('course_id', $course_id)
                ->first();

            if (!$material) {
                return $this->errorResponse('Material not found for this course', 404);
            }

            // Validate if the MCQ exists within the material
            $mcq = MCQ::where('id', $mcq_id)
                ->where('course_id', $course_id)
                ->where('material_id', $material_id)
                ->first();

            if (!$mcq) {
                return $this->errorResponse('MCQ not found for this material', 404);
            }

            // Delete the MCQ
            $mcq->delete();

            return $this->successResponse('MCQ deleted successfully', []);
        });
    }



    public function submitAnswers(Request $request, $course_id, $material_id)
    {
        return $this->safeCall(function () use ($request, $course_id, $material_id) {
            $course = Course::find($course_id);
            if (!$course) {
                return $this->errorResponse('Course not found', 404);
            }

            $material = Material::where('id', $material_id)
                ->where('course_id', $course_id)
                ->first();

            if (!$material) {
                return $this->errorResponse('Material not found for this course', 404);
            }

            $totalQuestions = MCQ::where('course_id', $course_id)
                ->where('material_id', $material_id)
                ->count();

            if ($totalQuestions === 0) {
                return $this->errorResponse('No MCQs found for this material', 404);
            }

            $request->validate([
                'mcq_answers' => 'required|array',
                'mcq_answers.*.mcq_id' => 'required|exists:m_c_q_s,id',
                'mcq_answers.*.selected_answer' => 'required|string',
            ]);

            $userId = Auth::id();
            $student = Student::where('user_id', $userId)->first();

            if (!$student) {
                return $this->errorResponse('Student record not found. Please make sure your profile is set up.', 404);
            }

            $correctAnswers = 0;

            foreach ($request->mcq_answers as $answer) {
                $mcq = MCQ::where('id', $answer['mcq_id'])
                    ->where('course_id', $course_id)
                    ->where('material_id', $material_id)
                    ->first();

                if (!$mcq) {
                    return $this->errorResponse("MCQ with ID {$answer['mcq_id']} not found in this material", 404);
                }

                $isCorrect = $mcq->correct_answer === $answer['selected_answer'];

                if ($isCorrect) {
                    $correctAnswers++;
                }

                StudentAnswer::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'mcq_id' => $mcq->id,
                    ],
                    [
                        'selected_answer' => $answer['selected_answer'],
                        'is_correct' => $isCorrect,
                    ]
                );
            }

            $totalScore = $correctAnswers;

            return $this->successResponse("MCQ answers submitted successfully", [
                'total_questions' => $totalQuestions,
                'answered_questions' => count($request->mcq_answers),
                'correct_answers' => $correctAnswers,
                'total_score' => $totalScore,
            ]);
        });
    }



    public function getStudentMarksByMaterial($courseId, $materialId)
    {
        // Get authenticated user ID
        $userId = Auth::id();

        // Fetch student record from the students table
        $student = Student::where('user_id', $userId)->with('batch')->firstOrFail(); // Ensure student exists

        // Check if the student is assigned to this course
        $isEnrolledInCourse = $student->batch->courses()->where('course_id', $courseId)->exists();

        if (!$isEnrolledInCourse) {
            return response()->json([
                'message' => 'Student is not enrolled in this course.'
            ], 403);
        }

        // Check if the material is assigned to this course
        $isMaterialInCourse = Material::where('id', $materialId)->where('course_id', $courseId)->exists();

        if (!$isMaterialInCourse) {
            return response()->json([
                'message' => 'This material is not part of the selected course.'
            ], 403);
        }

        // Get student ID
        $studentId = $student->id;

        // Get total correct answers for the student in the specified material
        $marksInMaterial = StudentAnswer::where('student_id', $studentId)
            ->whereHas('mcq', function ($query) use ($courseId, $materialId) {
                $query->where('course_id', $courseId)->where('material_id', $materialId);
            })
            ->where('is_correct', 1)
            ->count();

        // Get total correct answers for the student in the entire course
        $totalMarksInCourse = StudentAnswer::where('student_id', $studentId)
            ->whereHas('mcq', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->where('is_correct', 1)
            ->count();

        // Get total questions in this material
        $totalQuestionsInMaterial = Mcq::where('course_id', $courseId)
            ->where('material_id', $materialId)
            ->count();

        // Get total questions in the entire course
        $totalQuestionsInCourse = Mcq::where('course_id', $courseId)
            ->count();

        // Calculate percentage of marks in material
        $percentageInMaterial = ($totalQuestionsInMaterial > 0)
            ? ($marksInMaterial / $totalQuestionsInMaterial) * 100
            : 0;

        // Calculate percentage of marks in course
        $percentageInCourse = ($totalQuestionsInCourse > 0)
            ? ($totalMarksInCourse / $totalQuestionsInCourse) * 100
            : 0;

        // Return response with student details, marks, total questions, and percentages
        return response()->json([
            'student' => [
                'id' => $student->id,
                'profile picture' => $student->profile_picture,
                'first name' => $student->first_name,
                'last name' => $student->last_name,
                'email' => $student->email,
                'phone number' => $student->phone_number,
                'program' => $student->program,
                'address' => $student->address,
                'postal_code' => $student->postal_code,
                'blood_group' => $student->blood_group,
                'gender' => $student->gender,
                'user_status' => $student->user_status,
                'description' => $student->description,
                'batch title' => $student->batch->title,
                'created_at' => $student->created_at,
                'updated_at' => $student->updated_at
            ],
            'course_id' => $courseId,
            'material_id' => $materialId,
            'marks_in_material' => $marksInMaterial,
            'total_marks_in_course' => $totalMarksInCourse,
            'total_questions_in_material' => $totalQuestionsInMaterial,
            'total_questions_in_course' => $totalQuestionsInCourse,
            'percentage_in_material' => round($percentageInMaterial, 2),
            'percentage_in_course' => round($percentageInCourse, 2)
        ]);
    }



    public function getStudentOverallMarks()
    {
        // Get authenticated user ID
        $userId = Auth::id();

        // Fetch student record from the students table
        $student = Student::where('user_id', $userId)->with('batch')->firstOrFail(); // Ensure student exists

        // Get student ID
        $studentId = $student->id;

        // Check if the student is enrolled in any course
        $isEnrolledInAnyCourse = $student->batch->courses()->exists();

        if (!$isEnrolledInAnyCourse) {
            return response()->json([
                'message' => 'Student is not enrolled in any course.'
            ], 403);
        }

        // Get total correct answers for the student across all courses
        $totalMarks = StudentAnswer::where('student_id', $studentId)
            ->where('is_correct', 1)
            ->count();

        // Get total questions across all courses
        $totalQuestionsInAllCourses = Mcq::count();

        // Calculate percentage of marks across all courses
        $overallPercentage = ($totalQuestionsInAllCourses > 0)
            ? ($totalMarks / $totalQuestionsInAllCourses) * 100
            : 0;

        // Return response with total questions, total marks, and overall percentage
        return response()->json([
            'student' => [
                'id' => $student->id,
                'profile picture' => $student->profile_picture,
                'first name' => $student->first_name,
                'last name' => $student->last_name,
                'email' => $student->email,
                'phone number' => $student->phone_number,
                'program' => $student->program,
                'address' => $student->address,
                'postal_code' => $student->postal_code,
                'blood_group' => $student->blood_group,
                'gender' => $student->gender,
                'user_status' => $student->user_status,
                'description' => $student->description,
                'batch title' => $student->batch->title,
                'created_at' => $student->created_at,
                'updated_at' => $student->updated_at
            ],
            'total_questions_in_all_courses' => $totalQuestionsInAllCourses,
            'total_marks_obtained' => $totalMarks,
            'overall_percentage' => round($overallPercentage, 2)
        ]);
    }


    public function getStudentMarksByMaterialForAdmin($student_id, $courseId, $materialId)
    {
        // Fetch student record from the students table
        $student = Student::where('id', $student_id)->with('batch')->firstOrFail(); // Ensure student exists

        // Check if the student is assigned to this course
        $isEnrolledInCourse = $student->batch->courses()->where('course_id', $courseId)->exists();

        if (!$isEnrolledInCourse) {
            return response()->json([
                'message' => 'Student is not enrolled in this course.'
            ], 403);
        }

        // Check if the material is assigned to this course
        $isMaterialInCourse = Material::where('id', $materialId)->where('course_id', $courseId)->exists();

        if (!$isMaterialInCourse) {
            return response()->json([
                'message' => 'This material is not part of the selected course.'
            ], 403);
        }

        // Get total correct answers for the student in the specified material
        $marksInMaterial = StudentAnswer::where('student_id', $student_id)
            ->whereHas('mcq', function ($query) use ($courseId, $materialId) {
                $query->where('course_id', $courseId)->where('material_id', $materialId);
            })
            ->where('is_correct', 1)
            ->count();

        // Get total correct answers for the student in the entire course
        $totalMarksInCourse = StudentAnswer::where('student_id', $student_id)
            ->whereHas('mcq', function ($query) use ($courseId) {
                $query->where('course_id', $courseId);
            })
            ->where('is_correct', 1)
            ->count();

        // Get total questions in this material
        $totalQuestionsInMaterial = Mcq::where('course_id', $courseId)
            ->where('material_id', $materialId)
            ->count();

        // Get total questions in the entire course
        $totalQuestionsInCourse = Mcq::where('course_id', $courseId)
            ->count();

        // Calculate percentage of marks in material
        $percentageInMaterial = ($totalQuestionsInMaterial > 0)
            ? ($marksInMaterial / $totalQuestionsInMaterial) * 100
            : 0;

        // Calculate percentage of marks in course
        $percentageInCourse = ($totalQuestionsInCourse > 0)
            ? ($totalMarksInCourse / $totalQuestionsInCourse) * 100
            : 0;

        // Return response with student details, marks, total questions, and percentages
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
                'batch_title' => $student->batch->title ?? 'N/A',
                'created_at' => $student->created_at,
                'updated_at' => $student->updated_at
            ],
            'course_id' => $courseId,
            'material_id' => $materialId,
            'marks_in_material' => $marksInMaterial,
            'total_marks_in_course' => $totalMarksInCourse,
            'total_questions_in_material' => $totalQuestionsInMaterial,
            'total_questions_in_course' => $totalQuestionsInCourse,
            'percentage_in_material' => round($percentageInMaterial, 2) ,
            'percentage_in_course' => round($percentageInCourse, 2) 
        ]);
    }


    public function getStudentOverallMarksForAdmin($student_id)
    {
        // Fetch student record from the students table
        $student = Student::where('id', $student_id)->with('batch')->firstOrFail(); // Ensure student exists

        // Check if the student is enrolled in any course
        $isEnrolledInAnyCourse = $student->batch->courses()->exists();

        if (!$isEnrolledInAnyCourse) {
            return response()->json([
                'message' => 'Student is not enrolled in any course.'
            ], 403);
        }

        // Get total correct answers for the student across all courses
        $totalMarks = StudentAnswer::where('student_id', $student_id)
            ->where('is_correct', 1)
            ->count();

        // Get total questions across all courses
        $totalQuestionsInAllCourses = Mcq::count();

        // Calculate percentage of marks across all courses
        $overallPercentage = ($totalQuestionsInAllCourses > 0)
            ? ($totalMarks / $totalQuestionsInAllCourses) * 100
            : 0;

        // Return response with total questions, total marks, and overall percentage
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
                'batch_title' => $student->batch->title ?? 'N/A',
                'created_at' => $student->created_at,
                'updated_at' => $student->updated_at
            ],
            'total_questions_in_all_courses' => $totalQuestionsInAllCourses,
            'total_marks_obtained' => $totalMarks,
            'overall_percentage' => round($overallPercentage, 2)
        ]);
    }


}
