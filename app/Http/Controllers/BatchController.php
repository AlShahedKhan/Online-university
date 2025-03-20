<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Models\Batch;
use App\Traits\HandlesApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BatchController extends Controller
{
    use HandlesApiResponse;

    public function index()
    {
        return $this->safeCall(function () {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();
            $batches = Batch::orderBy('created_at', 'desc')->paginate(10);
            return $this->successResponse('Batches retrieved successfully', ['batches' => $batches]);
        });
    }

    public function storeOrUpdate(Request $request, Batch $batch = null)
    {
        return $this->safeCall(function () use ($request, $batch) {
            // Validate initial request data
            Log::info($request->all());

            $validateData = $request->validate([
                'data' => 'required|string',
                'batch_image' => 'nullable|image|mimes:png,jpg,jpeg,gif|max:2048',
                'instructor_ids' => 'required|array', // Array of instructor IDs
                'instructor_ids.*' => 'exists:professors,id' // Validate each instructor ID
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
                'title' => 'required|string|max:255',
                'subtitle' => 'required|string|max:255',
                'batch_image' => 'nullable|image|mimes:png,jpg,jpeg,gif|max:2048',
                'instructor_ids' => 'required|array',
            ]);

            // Handle image upload
            if ($request->hasFile('batch_image')) {
                $imagePath = $request->file('batch_image')->store('batch_images', 'public');
                $data['batch_image'] = $imagePath;
            }

            // Create or update batch
            $batch = Batch::updateOrCreate(
                ['id' => $batch->id ?? null], // Search condition
                $data // Data to insert/update
            );

            // Sync the instructors
            $batch->instructors()->sync($request->instructor_ids);

            return $this->successResponse('Batch created successfully', ['batch' => $batch]);
        });
    }

    public function show(Batch $batch)
    {
        return $this->safeCall(function () use ($batch) {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();
            return $this->successResponse('Batch retrieved successfully', ['batch' => $batch]);
        });
    }

    public function destroy(Batch $batch)
    {
        return $this->safeCall(function () use ($batch) {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();
            $batch->delete();
            return $this->successResponse('Batch deleted successfully', ['batch' => $batch]);
        });
    }
}
