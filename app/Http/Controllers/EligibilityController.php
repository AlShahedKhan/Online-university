<?php

namespace App\Http\Controllers;

use App\Models\OurFaculty;
use App\Helpers\AuthHelper;
use App\Models\Eligibility;
use Illuminate\Http\Request;
use App\Traits\HandlesApiResponse;

class EligibilityController extends Controller
{
    use HandlesApiResponse;

    public function index($facultyId, $departmentId)
    {
        return $this->safeCall(function () use ($facultyId, $departmentId) {

            $eligibility = Eligibility::where('our_department_id', $departmentId)
                ->whereHas('department', function ($query) use ($facultyId) {
                    $query->where('our_faculty_id', $facultyId);
                })
                ->get();

            if ($eligibility->isEmpty()) {
                return response()->json(
                    (object)[
                        'status' => false,
                        'message' => 'No eligibility found for this department.',
                        'status_code' => 200,
                        'error' => null
                    ],
                    200
                );
            }

            $eligibilityData = $eligibility->map(function ($eligibilityItem) {
                return (object)$eligibilityItem->toArray();
            });

            return response()->json(
                (object)[
                    'status' => true,
                    'message' => 'Eligibility retrieved successfully.',
                    'data' => $eligibilityData // Return eligibility data as an array of objects
                ]
            );
        });
    }



    public function storeOrUpdate(Request $request, $id = null)
    {
        return $this->safeCall(function () use ($request, $id) {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();

            // Validate request
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'our_faculty_id' => 'required|exists:our_faculties,id',
                'our_department_id' => 'required|exists:our_departments,id',
            ]);

            // Check if an Eligibility with the same faculty and department already exists
            $existingEligibility = Eligibility::where('our_faculty_id', $validatedData['our_faculty_id'])
                ->where('our_department_id', $validatedData['our_department_id'])
                ->where('id', '!=', $id) // Exclude the current ID when updating
                ->first();

            if ($existingEligibility) {
                return $this->errorResponse('An eligibility with the same faculty and department already exists.', 400);
            }

            // Use updateOrCreate for efficiency
            $eligibility = Eligibility::updateOrCreate(
                ['id' => $id],
                $validatedData
            );

            return $this->successResponse('Eligibility criteria saved successfully.', ['eligibility' => $eligibility]);
        });
    }


    public function destroy($facultyId, $departmentId, $eligibilityId)
    {
        return $this->safeCall(function () use ($facultyId, $departmentId, $eligibilityId) {
            // Ensure the user is authorized
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();

            // Find the Faculty by ID
            $faculty = OurFaculty::find($facultyId);
            if (!$faculty) {
                return $this->errorResponse('Faculty not found.', 200);
            }

            // Find the Department by ID and ensure it belongs to the given Faculty
            $department = $faculty->departments()->find($departmentId);
            if (!$department) {
                return $this->errorResponse('Department not found in the given faculty.', 200);
            }

            // Find the Eligibility by ID and ensure it belongs to the given Department
            $eligibility = $department->eligibilities()->find($eligibilityId);
            if (!$eligibility) {
                return $this->errorResponse('Eligibility not found in the given department.', 200);
            }

            // Delete the Eligibility
            $eligibility->delete();

            return $this->successResponse('Eligibility deleted successfully.', ['eligibility' => $eligibility]);
        });
    }
}
