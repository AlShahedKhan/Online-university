<?php

namespace App\Http\Controllers;

use App\Models\OurFaculty;
use App\Models\TuitionFee;
use App\Helpers\AuthHelper;
use Illuminate\Http\Request;
use App\Traits\HandlesApiResponse;
use Illuminate\Container\Attributes\Auth;

class TuitionFeeController extends Controller
{
    use HandlesApiResponse;

    public function index($facultyId, $departmentId)
    {
        return $this->safeCall(function () use ($facultyId, $departmentId) {

            // Fetch tuition fees for the given department and faculty
            $tuitionFees = TuitionFee::where('our_department_id', $departmentId)
                ->whereHas('department', function ($query) use ($facultyId) {
                    $query->where('our_faculty_id', $facultyId);
                })
                ->with('department')
                ->get();

            if ($tuitionFees->isEmpty()) {
                return $this->errorResponse('No tuition fees found for this department.', 200);
            }

            // Calculate total fee for each tuition fee
            $tuitionFeesWithTotal = $tuitionFees->map(function ($tuitionFee) {
                // Calculate total fee: admission_fee + (credit_fee * credit_hour)
                $tuitionFee->total_fee = $tuitionFee->admission_fee + ($tuitionFee->credit_fee * $tuitionFee->credit_hour);
                return $tuitionFee;
            });

            // Return success response with total_fee added
            return $this->successResponse(
                'Tuition fees retrieved successfully.',
                [
                    'tuition_fees' => $tuitionFeesWithTotal,
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
                'credit_hour' => 'required|string',  // Ensure the correct database column name
                'program_duration' => 'required|string',
                'admission_fee' => 'required|string',
                'credit_fee' => 'nullable|string',
                'our_faculty_id' => 'required|exists:our_faculties,id',
                'our_department_id' => 'required|exists:our_departments,id',
            ]);

            // Check if a TuitionFee already exists with the same faculty and department combination
            $existingTuitionFee = TuitionFee::where('our_faculty_id', $validatedData['our_faculty_id'])
                ->where('our_department_id', $validatedData['our_department_id'])
                ->where('id', '!=', $id) // Exclude the current id when updating
                ->first();

            if ($existingTuitionFee) {
                // If the combination exists, return an error response
                return $this->errorResponse('A tuition fee already exists for this department and faculty.', 400);
            }

            // Create or update the tuition fee
            $tuitionFee = TuitionFee::updateOrCreate(
                ['id' => $id],
                $validatedData
            );

            return $this->successResponse('Tuition fee saved successfully.', $tuitionFee->toArray());
        });
    }


    public function destroy($facultyId, $departmentId, $tuitionFeeId)
    {
        return $this->safeCall(function () use ($facultyId, $departmentId, $tuitionFeeId) {
            // Check if the user is authorized
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();

            // Find the Faculty by ID
            $faculty = OurFaculty::find($facultyId);
            if (!$faculty) {
                return $this->errorResponse('Faculty not found.', 200);
            }

            $department = $faculty->departments()->find($departmentId);
            if (!$department) {
                return $this->errorResponse('Department not found in the given faculty.', 200);
            }

            $tuitionFee = $department->tuitionFees()->find($tuitionFeeId);
            if (!$tuitionFee) {
                return $this->errorResponse('Tuition Fee not found in the given department.', 200);
            }

            $tuitionFee->delete();



            return $this->successResponse('Tuition Fee deleted successfully.', ['tuition_fee' => $tuitionFee]);
        });
    }
}
