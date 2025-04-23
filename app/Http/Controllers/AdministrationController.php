<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Student;
use App\Models\Professor;
use App\Helpers\AuthHelper;
use Illuminate\Http\Request;
use App\Traits\HandlesApiResponse;
use Illuminate\Support\Facades\Log;

class AdministrationController extends Controller
{
    // 
    use HandlesApiResponse;
    public function index()
    {
        return $this->safeCall(function () {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();

            $students = Student::count();
            $professors = Professor::count();
            $courses = Course::count();
            return $this->successResponse('Students retrieved successfully', [
                'students' => $students,
                'professors' => $professors,
                'courses' => $courses
            ]);
        });
    }

    public function getStudentAnalysis($year)
    {
        return $this->safeCall(function () use ($year) {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();

            // Fetch the count of students for each month in the specified year
            $studentsByMonth = Student::selectRaw('MONTH(created_at) as month, COUNT(*) as total_students')
                ->whereYear('created_at', $year)
                ->groupBy('month')
                ->orderBy('month', 'asc')  // To order the months from January to December
                ->get();

            // Prepare the data as an array with month names for easy understanding
            $data = [];
            foreach ($studentsByMonth as $student) {
                $monthName = \Carbon\Carbon::create()->month($student->month)->format('F');  // Get month name (January, February, etc.)
                $data[] = [
                    'month' => $monthName,
                    'total_students' => $student->total_students,
                ];
            }

            return $this->successResponse('Students retrieved successfully by month', [
                'students_by_month' => $data,
            ]);
        });
    }

    public function courseOverview()
    {
        return $this->safeCall(function () {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();

            // Get the total number of students
            $totalStudents = Student::count();

            // Fetch all courses
            $courses = Course::all();

            // Initialize an array to store the percentage data for each course
            $coursePercentages = [];

            // Loop through each course to calculate the number of students and their percentages
            foreach ($courses as $course) {
                // Get the number of students enrolled in the current course
                $studentsEnrolled = Student::whereHas('batch.courses', function ($query) use ($course) {
                    $query->where('course_id', $course->id);
                })->count();

                // Calculate the percentage of students enrolled in this course
                $percentage = $totalStudents > 0 ? round(($studentsEnrolled / $totalStudents) * 100, 2) : 0;

                // Store the course ID, name, number of students, and percentage
                $coursePercentages[] = [
                    'course_id' => $course->id,
                    'course_name' => $course->name,
                    'students_enrolled' => $studentsEnrolled,
                    'percentage' => $percentage,
                ];
            }

            return $this->successResponse('Courses retrieved successfully', [
                'total_students' => $totalStudents,
                'courses' => $coursePercentages,
            ]);
        });
    }



}
