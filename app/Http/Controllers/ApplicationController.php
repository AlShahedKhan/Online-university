<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Helpers\AuthHelper;
use App\Models\Application;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Jobs\StoreApplicationJob;
use App\Mail\ApplicationApproved;
use App\Mail\ApplicationRejected;
use App\Traits\HandlesApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ApplicationController extends Controller
{
    use HandlesApiResponse;

    public function index()
    {
        return $this->safeCall(function () {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();
            if (!Application::exists()) {
                return $this->errorResponse('No applications found', 404);
            }
            $applications = Application::orderBy('created_at', 'desc')->paginate(10);
            return $this->successResponse('Applications retrieved successfully!', ['applications' => $applications]);
        });
    }

    public function show(Application $application)
    {
        return $this->safeCall(function () use ($application) {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();
            if (!$application) {
                return $this->errorResponse('Application not found', 404);
            }
            return $this->successResponse('Application retrieved successfully!', ['application' => $application]);
        });
    }

    public function store(Request $request)
    {
        return $this->safeCall(function () use ($request) {
            // Decode the JSON data field
            $data = json_decode($request->input('data'), true);

            if (!$data || !is_array($data)) {
                return $this->errorResponse('Invalid JSON format in "data" field.', 400);
            }

            $validated = validator($data, [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'date_of_birth' => 'required|date',
                'gender' => 'required|in:male,female,other',
                'nationality' => 'required|string|max:255',
                'contact_number' => 'required|string|max:20',
                'email' => 'required|email|max:255|unique:applications,email',
                'address' => 'required|string',
                'nid_number' => 'nullable|string|max:50',
                'program' => 'required|string|max:255',
                'bachelor_institution' => 'nullable|string|max:255',
                'degree_earned' => 'nullable|string|max:255',
                'graduation_year' => 'nullable|digits:4|integer',
                'gpa' => 'nullable|numeric|between:0,4.00',
                'job_title' => 'nullable|string|max:255',
                'years_experience' => 'nullable|integer|min:0',
                'responsibilities' => 'nullable|string',
                'application_status' => 'nullable|in:pending,approved,rejected', // New validation
            ])->validate();

            // Handle file uploads
            if ($request->hasFile('passport')) {
                $validated['passport_path'] = $request->file('passport')->store('documents/passports', 'public');
            }

            if ($request->hasFile('nid')) {
                $validated['nid_path'] = $request->file('nid')->store('documents/nids', 'public');
            }

            // Default application_status to 'pending' if not provided
            $validated['application_status'] = $validated['application_status'] ?? 'pending';

            // Dispatch job to store application
            StoreApplicationJob::dispatch($validated);

            return $this->successResponse('Application submitted successfully!', ['Application' => $validated], 201);
        });
    }

    public function approveOrReject(Request $request, Application $application)
    {
        return $this->safeCall(function () use ($request, $application) {
            if (!Auth::check()) {
                return $this->errorResponse('Unauthorized', 401);
            }

            if (!Auth::user()->is_admin) {
                return $this->errorResponse('Forbidden: You are not authorized to perform this action.', 403);
            }

            $validated = $request->validate([
                'status' => 'required|in:approved,rejected',
                'student_id' => 'nullable|string',
                'batch_id' => 'nullable|exists:batches,id',
            ]);

            // Update application status
            $application->update(['application_status' => $validated['status']]);

            try {
                if ($validated['status'] === 'approved') {
                    Log::info("Application approved: " . $application->id);

                    // Generate a random password
                    $randomPassword = Str::random(12);

                    // Create student account in users table
                    $user = User::create([
                        'first_name' => $application->first_name,
                        'last_name' => $application->last_name,
                        'email' => $application->email,
                        'password' => Hash::make($randomPassword),
                        'is_approved' => 1, // Mark the user as approved
                    ]);

                    Log::info("User created with ID: " . $user->id);

                    // Store student data in the `students` table
                    $student = Student::create([
                        'first_name' => $application->first_name,
                        'last_name' => $application->last_name,
                        'email' => $application->email,
                        'phone_number' => $application->contact_number,
                        'address' => $application->address,
                        'program' => $application->program,
                        'postal_code' => null, // No postal code in applications, set to null
                        'student_id' => $validated['student_id'], // Employee ID is not in applications, set to null
                        'blood_group' => null, // Blood group is not in applications, set to null
                        'gender' => ucfirst($application->gender), // Capitalize gender (Male, Female, Other)
                        'user_status' => 'Active', // Default to Active
                        'description' => "Enrolled in " . $application->program, // Short description
                        'batch_id' => $validated['batch_id'],
                        'course_id' => null, // Explicitly set course_id to null
                        'user_id' => $user->id
                    ]);

                    Log::info("Student record created with ID: " . $student->id);

                    // Send email with credentials
                    Mail::to($application->email)->send(new ApplicationApproved($application, $randomPassword, $validated['batch_id'], $validated['student_id']));
                } else {
                    Mail::to($application->email)->send(new ApplicationRejected($application));
                }
            } catch (\Exception $e) {
                Log::error('Failed to process application:', ['error' => $e->getMessage()]);
            }

            return $this->successResponse(
                "Application {$validated['status']} successfully",
                ['application' => $application->refresh()]
            );
        });
    }

}
