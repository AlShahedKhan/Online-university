<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Traits\HandlesApiResponse;

class CourseController extends Controller
{
    use HandlesApiResponse;

    public function index()
    {
        return $this->safeCall(function () {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();
            $courses = Course::orderBy('created_at', 'desc')->paginate(10);
            return $this->successResponse('Courses fetched successfully', ['courses' => $courses]);
        });
    }
    public function storeOrUpdate(Request $request, Course $course = null): JsonResponse
    {
        return $this->safeCall(function () use ($request, $course) {
            // Validate initial request data
            $validateData = $request->validate([
                'data' => 'required|string',
                'course_image' => 'nullable|image|mimes:png,jpg,jpeg,gif|max:2048'
            ]);

            AuthHelper::checkUser();
            AuthHelper::checkAdmin();

            // Decode JSON data
            $data = json_decode($validateData['data'], true);

            if (!is_array($data)) {
                return $this->errorResponse('The data field must be a valid JSON array.', 400);
            }

            // Merge decoded data with request
            $request->merge($data);

            // Validate merged data
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                // 'professor_id' => 'required|exists:professors,id',
                'department_id' => 'required|exists:our_departments,id',
                'status' => 'required',
                'credit' => 'required',
                'course_image' => 'nullable|image|mimes:png,jpg,jpeg,gif|max:2048',
            ]);

            if ($request->hasFile('course_image')) {
                $imagePath = $request->file('course_image')->store('course_images', 'public');
                $data['course_image'] = $imagePath;
            }

            $course = Course::updateOrCreate(
                ['id' => $course->id ?? null],
                $data
            );

            return $this->successResponse('Course created successfully', ['course' => $course]);
        });
    }

    public function destroy(Course $course)
    {
        return $this->safeCall(function () use ($course) {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();
            $course->delete();
            return $this->successResponse('Course deleted successfully', ['course' => $course]);
        });
    }
}
