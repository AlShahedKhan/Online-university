<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Material;
use App\Models\BatchCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\AssignmentSubmission;
use Illuminate\Support\Facades\Auth;

class StudentMarksController extends Controller
{
    /**
     * Get student marks for a specific course and all courses.
     */
    public function getStudentMarks($courseId)
    {
        // Get authenticated user ID
        $userId = Auth::id();

        // Fetch student record
        $student = Student::where('user_id', $userId)->with('batch')->firstOrFail();

        // Check if the student is enrolled in this course
        $isEnrolled = BatchCourse::where('batch_id', $student->batch_id)
            ->where('course_id', $courseId)
            ->exists();

        if (!$isEnrolled) {
            return response()->json([
                'message' => 'Student is not enrolled in this course.'
            ], 403);
        }

        // Get student ID
        $studentId = $student->id;

        // Get total marks obtained in this course
        $totalMarksInCourse = AssignmentSubmission::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->sum('marks');

        // Get total marks obtained in all courses
        $totalMarksInAllCourses = AssignmentSubmission::where('student_id', $studentId)
            ->sum('marks');

        // Get expected total marks in this course (sum of max marks from materials)
        $expectedTotalMarksInCourse = Material::where('course_id', $courseId)->sum('marks');

        // Get expected total marks in all courses
        $expectedTotalMarksInAllCourses = Material::sum('marks');

        // Calculate average marks in this course
        $assignmentsInCourse = AssignmentSubmission::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->count();
        $averageMarksInCourse = $assignmentsInCourse > 0 ? $totalMarksInCourse / $assignmentsInCourse : 0;

        // Calculate average marks in all courses
        $assignmentsInAllCourses = AssignmentSubmission::where('student_id', $studentId)->count();
        $averageMarksInAllCourses = $assignmentsInAllCourses > 0 ? $totalMarksInAllCourses / $assignmentsInAllCourses : 0;

        // Return response
        return response()->json([
            'student_id' => $studentId,
            'course_id' => $courseId,
            'total_marks_in_course' => $totalMarksInCourse,
            'total_marks_in_all_courses' => $totalMarksInAllCourses,
            'expected_total_marks_in_course' => $expectedTotalMarksInCourse,
            'expected_total_marks_in_all_courses' => $expectedTotalMarksInAllCourses,
            'average_marks_in_course' => round($averageMarksInCourse, 2),
            'average_marks_in_all_courses' => round($averageMarksInAllCourses, 2)
        ]);
    }

    public function getStudentAssignmentOverallMarks()
    {
        // Get authenticated user ID
        $userId = Auth::id();

        // Log the user ID for debugging
        Log::info('Authenticated User ID: ' . $userId);

        // Check if the user is authenticated
        if (!$userId) {
            Log::error('User is not authenticated');
            return response()->json(['message' => 'User is not authenticated'], 401);
        }

        // Fetch student record
        $student = Student::where('user_id', $userId)->with('batch')->first();

        // Log the SQL query being executed for the Student model
        Log::info('Student Query: ' . Student::where('user_id', $userId)->toSql());

        // Check if student record exists
        if (!$student) {
            Log::error('Student record not found for user_id: ' . $userId);
            return response()->json(['message' => 'Student record not found for this user'], 404);
        }

        // Get student ID
        $studentId = $student->id;

        // Log the student ID
        Log::info('Student ID: ' . $studentId);

        // Get total marks obtained in all courses
        $totalMarksInAllCourses = AssignmentSubmission::where('student_id', $studentId)
            ->sum('marks');

        // Log the total marks
        Log::info('Total Marks in All Courses: ' . $totalMarksInAllCourses);

        // Get total marks obtained across all materials in all courses
        $totalExpectedMarksInAllCourses = Material::sum('marks');

        // Log the total expected marks
        Log::info('Total Expected Marks in All Courses: ' . $totalExpectedMarksInAllCourses);

        // Calculate overall average marks across all courses
        $assignmentsInAllCourses = AssignmentSubmission::where('student_id', $studentId)->count();
        $averageMarksInAllCourses = $assignmentsInAllCourses > 0 ? $totalMarksInAllCourses / $assignmentsInAllCourses : 0;

        // Log the average marks
        Log::info('Average Marks in All Courses: ' . round($averageMarksInAllCourses, 2));

        // Return response with the calculated data
        return response()->json([
            'student_id' => $studentId,
            'total_marks_in_all_courses' => $totalMarksInAllCourses,
            'total_expected_marks_in_all_courses' => $totalExpectedMarksInAllCourses,
            'average_marks_in_all_courses' => round($averageMarksInAllCourses, 2)
        ]);
    }
    // public function getStudentAssignmentOverallMarks()
    // {
    //     // Get authenticated user ID
    //     $userId = Auth::id();

    //     // Fetch student record
    //     $student = Student::where('user_id', $userId)->with('batch')->firstOrFail();

    //     // Get student ID
    //     $studentId = $student->id;

    //     // Get total marks obtained in all courses
    //     $totalMarksInAllCourses = AssignmentSubmission::where('student_id', $studentId)
    //         ->sum('marks');

    //     // Get total marks obtained across all materials in all courses
    //     $totalExpectedMarksInAllCourses = Material::sum('marks');

    //     // Calculate overall average marks across all courses
    //     $assignmentsInAllCourses = AssignmentSubmission::where('student_id', $studentId)->count();
    //     $averageMarksInAllCourses = $assignmentsInAllCourses > 0 ? $totalMarksInAllCourses / $assignmentsInAllCourses : 0;

    //     // Return response
    //     return response()->json([
    //         'student_id' => $studentId,
    //         'total_marks_in_all_courses' => $totalMarksInAllCourses,
    //         'total_expected_marks_in_all_courses' => $totalExpectedMarksInAllCourses,
    //         'average_marks_in_all_courses' => round($averageMarksInAllCourses, 2)
    //     ]);
    // }

    public function getStudentOvallMarksForAdmin($student_id)
    {
        // Fetch student record
        $student = Student::where('id', $student_id)->with('batch')->firstOrFail();

        // Get total marks obtained in all courses
        $totalMarksInAllCourses = AssignmentSubmission::where('student_id', $student_id)
            ->sum('marks');

        // Get expected total marks in all courses
        $expectedTotalMarksInAllCourses = Material::sum('marks');

        // Calculate average marks in all courses
        $assignmentsInAllCourses = AssignmentSubmission::where('student_id', $student_id)->count();
        $averageMarksInAllCourses = $assignmentsInAllCourses > 0 ? $totalMarksInAllCourses / $assignmentsInAllCourses : 0;

        // Get total marks in assignments for this student in all courses
        $totalAssignmentMarksInAllCourses = AssignmentSubmission::where('student_id', $student_id)
            ->sum('marks');

        // Get expected marks for all assignments in all courses (sum of marks from the related materials)
        $totalExpectedAssignmentMarksInAllCourses = Material::sum('marks');

        // Calculate overall assignment percentage
        $overallAssignmentPercentage = ($totalExpectedAssignmentMarksInAllCourses > 0)
            ? ($totalAssignmentMarksInAllCourses / $totalExpectedAssignmentMarksInAllCourses) * 100
            : 0;

        // Return response
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
            'total_marks_in_all_courses' => $totalMarksInAllCourses,
            'total_expected_marks_in_all_courses' => $expectedTotalMarksInAllCourses,
            'average_marks_in_all_courses' => round($averageMarksInAllCourses, 2),
            'overall_assignment_percentage' => round($overallAssignmentPercentage, 2)
        ]);
    }

    public function getStudentMarksForAdmin($student_id, $courseId)
    {
        // Fetch student record
        $student = Student::where('id', $student_id)->with('batch')->firstOrFail();

        // Check if the student is enrolled in this course
        $isEnrolled = BatchCourse::where('batch_id', $student->batch_id)
            ->where('course_id', $courseId)
            ->exists();

        if (!$isEnrolled) {
            return response()->json([
                'message' => 'Student is not enrolled in this course.'
            ], 403);
        }

        // Get total marks obtained in this course
        $totalMarksInCourse = AssignmentSubmission::where('student_id', $student_id)
            ->where('course_id', $courseId)
            ->sum('marks');

        // Get total marks obtained in all courses
        $totalMarksInAllCourses = AssignmentSubmission::where('student_id', $student_id)
            ->sum('marks');

        // Get expected total marks in this course (sum of max marks from materials)
        $expectedTotalMarksInCourse = Material::where('course_id', $courseId)->sum('marks');

        // Get expected total marks in all courses
        $expectedTotalMarksInAllCourses = Material::sum('marks');

        // Calculate average marks in this course
        $assignmentsInCourse = AssignmentSubmission::where('student_id', $student_id)
            ->where('course_id', $courseId)
            ->count();
        $averageMarksInCourse = $assignmentsInCourse > 0 ? $totalMarksInCourse / $assignmentsInCourse : 0;

        // Calculate average marks in all courses
        $assignmentsInAllCourses = AssignmentSubmission::where('student_id', $student_id)->count();
        $averageMarksInAllCourses = $assignmentsInAllCourses > 0 ? $totalMarksInAllCourses / $assignmentsInAllCourses : 0;

        // Calculate percentage in this course
        $percentageInCourse = ($expectedTotalMarksInCourse > 0)
            ? ($totalMarksInCourse / $expectedTotalMarksInCourse) * 100
            : 0;

        // Calculate overall percentage in all courses
        $overallPercentage = ($expectedTotalMarksInAllCourses > 0)
            ? ($totalMarksInAllCourses / $expectedTotalMarksInAllCourses) * 100
            : 0;

        // Return response
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
            'total_marks_in_course' => $totalMarksInCourse,
            'total_marks_in_all_courses' => $totalMarksInAllCourses,
            'expected_total_marks_in_course' => $expectedTotalMarksInCourse,
            'expected_total_marks_in_all_courses' => $expectedTotalMarksInAllCourses,
            'average_marks_in_course' => round($averageMarksInCourse, 2),
            'average_marks_in_all_courses' => round($averageMarksInAllCourses, 2),
            'percentage_in_course' => round($percentageInCourse, 2) ,
            'overall_percentage' => round($overallPercentage, 2) . '%'
        ]);
    }
}
