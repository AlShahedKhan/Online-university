<?php

namespace App\Http\Controllers;

use App\Models\OurFaculty;
use App\Helpers\AuthHelper;
use Illuminate\Http\Request;
use App\Traits\HandlesApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Container\Attributes\Auth;

class OurFacultyController extends Controller
{
    use HandlesApiResponse;

    public function index()
    {
        return $this->safeCall(function () {

            $ourFaculties = OurFaculty::all();
            return $this->successResponse('Faculty members fetched successfully.', $ourFaculties->toArray());
        });
    }

    public function storeOrUpdate(Request $request, $id = null)
    {
        return $this->safeCall(function () use ($request, $id) {
            // Log full request data
            Log::info('Incoming request for storeOrUpdate', [
                'id' => $id,
                'request_data' => $request->all()
            ]);

            $requestData = json_decode($request->input('data'), true);

            $validatedData = validator($requestData, [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
            ])->validate();

            AuthHelper::checkUser();
            AuthHelper::checkAdmin();

            // Prepare data for database
            $data = [
                'title' => $validatedData['title'],
                'description' => $validatedData['description'] ?? null,
            ];

            if ($request->hasFile('profile_picture')) {
                $imageFile = $request->file('profile_picture');
            } elseif ($request->hasFile('image')) {
                $imageFile = $request->file('image');
            } else {
                $imageFile = null;
            }

            if ($imageFile) {

                if (!$imageFile->isValid()) {
                    Log::error('Invalid image file uploaded');
                    return $this->errorResponse('Invalid image file uploaded.');
                }

                $imageName = time() . '.' . $imageFile->getClientOriginalExtension();
                $imagePath = $imageFile->storeAs('uploads', $imageName, 'public');

                if ($imagePath) {
                    $data['image'] = $imagePath;
                } else {
                    return $this->errorResponse('Failed to store image.');
                }
            } else {
                Log::info('No image provided in request.');
            }

            $ourFaculty = OurFaculty::updateOrCreate(['id' => $id], $data);

            $ourFaculty->refresh();

            // Convert model to array before sending response
            return $this->successResponse('Faculty member saved successfully.', $ourFaculty->toArray());
        });
    }

    public function destroy(OurFaculty $faculty)
    {
        return $this->safeCall(function () use ($faculty) {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();
            $faculty->delete();

            return $this->successResponse('Faculty member deleted successfully.', ['faculty' => $faculty]);
        });
    }


}
