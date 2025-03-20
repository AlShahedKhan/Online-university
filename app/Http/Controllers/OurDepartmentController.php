<?php

namespace App\Http\Controllers;

use App\Models\TuitionFee;
use App\Helpers\AuthHelper;
use Illuminate\Http\Request;
use App\Models\OurDepartment;
use App\Models\OurFaculty;
use App\Traits\HandlesApiResponse;
use Illuminate\Support\Facades\Log;

class OurDepartmentController extends Controller
{
    use HandlesApiResponse;

    public function index($facultyId)
    {
        // Fetch all departments belonging to the given facultyId
        $departments = OurDepartment::where('our_faculty_id', $facultyId)->with('faculty')->get();

        // Check if departments exist
        if ($departments->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No departments found for this faculty.',
            ], 404);
        }

        // Return success response with the departments
        return response()->json([
            'status' => 'success',
            'message' => 'Departments retrieved successfully.',
            'data' => $departments
        ], 200);
    }



    public function storeOrUpdate(Request $request, $id = null)
    {
        return $this->safeCall(function () use ($request, $id) {
            $requestData = json_decode($request->input('data'), true);

            AuthHelper::checkUser();
            AuthHelper::checkAdmin();

            $validatedData = validator($requestData, [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'our_faculty_id' => 'required|exists:our_faculties,id',
            ])->validate();

            $data = [
                'title' => $validatedData['title'],
                'description' => $validatedData['description'] ?? null,
                'our_faculty_id' => $validatedData['our_faculty_id'],
            ];

            $imageFile = $request->file('department_image') ?? $request->file('image');

            if ($imageFile) {
                Log::info('Image file detected', ['image_name' => $imageFile->getClientOriginalName()]);

                if (!$imageFile->isValid()) {
                    Log::error('Invalid image file uploaded');
                    return $this->errorResponse('Invalid image file uploaded.');
                }

                $imageName = time() . '.' . $imageFile->getClientOriginalExtension();
                $imagePath = $imageFile->storeAs('departments', $imageName, 'public');

                if ($imagePath) {
                    Log::info('Image stored successfully', ['path' => $imagePath]);
                    $data['image'] = $imagePath;
                } else {
                    Log::error('Failed to store image');
                    return $this->errorResponse('Failed to store image.');
                }
            } else {
                Log::info('No image provided in request.');
            }

            $ourDepartment = OurDepartment::updateOrCreate(['id' => $id], $data);
            $ourDepartment->refresh();

            return $this->successResponse('Department stored successfully', ['department' => $ourDepartment]);
        });
    }

    public function destroy($facultyId, $departmentId)
    {
        return $this->safeCall(function () use ($facultyId, $departmentId) {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();

            // Find the faculty by its ID
            $faculty = OurFaculty::find($facultyId);

            if (!$faculty) {
                return $this->errorResponse('Faculty not found.', 404);
            }

            // Find the department that belongs to this faculty
            $department = $faculty->departments()->where('id', $departmentId)->first();

            if (!$department) {
                return $this->errorResponse('Department not found.', 404);
            }

            // Delete the department
            $department->delete();

            return $this->successResponse('Department deleted successfully.', ['department' => $department]);
        });
    }

}
