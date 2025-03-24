<?php

namespace App\Http\Controllers;

use Stripe\Stripe;
use App\Models\Payment;
use App\Models\Student;
use App\Helpers\AuthHelper;
use App\Models\Certificate;
use Illuminate\Http\Request;
use App\Traits\HandlesApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    use HandlesApiResponse;

    public function getAdminPayments($student_id)
    {
        return $this->safeCall(function () use ($student_id) {
            AuthHelper::checkUser();
            AuthHelper::checkAdmin();

            // Get payments for the specific student and load associated users with their students
            $payments = Payment::whereHas('user.student', function ($query) use ($student_id) {
                $query->where('student_id', $student_id);
            })
                ->with('user.student.batch.courses.department.tuitionFees')
                ->get();

            Log::info($payments);

            $uniqueStudents = [];
            $formattedData = [];

            foreach ($payments as $payment) {
                $student = $payment->user->student;

                if ($student) {
                    // Check if the student is already included in the formatted data
                    if (!in_array($student->id, $uniqueStudents)) {
                        $uniqueStudents[] = $student->id;

                        // Initialize total_credits and total_credit_fee
                        $totalCredits = 0;
                        $totalCreditFee = 0;
                        $coursesList = [];

                        // Collect all courses for the student
                        foreach ($student->batch->courses as $course) {
                            $totalCredits += $course->credit;
                            $totalCreditFee += $course->department->tuitionFees->sum('credit_fee');

                            $totalFee = $totalCredits * $totalCreditFee;

                            $totalCreditFee = $totalFee + $course->department->tuitionFees->sum('admission_fee');



                            // Store course details
                            $coursesList[] = [
                                'id' => $course->id,
                                'name' => $course->name,
                                'credit' => $course->credit,
                                // 'department' => $course->department->name,
                                // 'tuition_fee' => $course->department->tuitionFees->sum('credit_fee'),
                            ];
                        }

                        // Add the student along with their payments to the formatted data
                        $formattedData[] = [
                            'student' => [
                                'id' => $student->id,
                                'first_name' => $student->first_name,
                                'last_name' => $student->last_name,
                                'email' => $student->email,
                                'phone_number' => $student->phone_number,
                                'address' => $student->address,
                                'student_id' => $student->student_id,
                                'program' => $student->program,
                                'user_status' => $student->user_status,
                                'description' => $student->description,
                                'batch_id' => $student->batch_id,
                                'blood_group' => $student->blood_group,
                                'gender' => $student->gender,
                                'postal_code' => $student->postal_code,
                                'profile_picture' => $student->profile_picture,
                                'created_at' => $student->created_at,
                                'updated_at' => $student->updated_at,
                            ],
                            'courses' => $coursesList,  // Include all courses
                            'payments' => [$payment->only(['id', 'amount', 'currency', 'payment_intent_id', 'created_at', 'updated_at'])],
                            'total_paid' => $payment->amount,  // Initialize total with the current payment amount
                            'total_credits' => $totalCredits,
                            'total_credit_fee' => $totalCreditFee,
                            'remaining_balance' => $totalCreditFee - $payment->amount,  // Remaining balance calculation
                        ];
                    } else {
                        // If the student is already in the response, add the payment to the existing student
                        $index = array_search($student->id, array_column($formattedData, 'student.id'));
                        $formattedData[$index]['payments'][] = $payment->only(['id', 'amount', 'currency', 'payment_intent_id', 'created_at', 'updated_at']);
                        $formattedData[$index]['total_paid'] += $payment->amount;  // Add the amount to the total
                    }
                }
            }

            // Return the response in the desired format
            return $this->successResponse('Payments retrieved successfully', ['payments' => $formattedData]);
        });
    }
    // public function getAdminPayments($student_id)
    // {
    //     return $this->safeCall(function () use ($student_id) {
    //         AuthHelper::checkAdmin();
    //         $userID = Auth::id();
    //         $student = Student::where('id', $student_id)
    //             ->with('user.payments')->with('batch.courses')
    //             ->first();

    //         if (!$student) {
    //             return $this->errorResponse('Student not found', 404);
    //         }
    //         return $this->successResponse('Payments retrieved successfully', ['student' => $student]);
    //     });
    // }


    public function getStudentPayments()
    {
        return $this->safeCall(function () {
            // Ensure the user is authenticated
            AuthHelper::checkUser();

            $payments = Payment::with('user.student.batch.courses.department.tuitionFees')
                ->where('user_id', Auth::user()->id)
                ->get();

            $uniqueStudents = [];
            $formattedData = [];

            foreach ($payments as $payment) {
                $student = $payment->user->student;

                if ($student) {
                    // Check if the student is already included in the formatted data
                    if (!in_array($student->id, $uniqueStudents)) {
                        $uniqueStudents[] = $student->id;

                        // Initialize total amount paid
                        $totalPaid = $payment->amount;

                        // Calculate total credits
                        $totalCredits = $student->batch->courses->sum('credit');

                        $firstCourse = $student->batch->courses->first();
                        $tuitionFeeData = $firstCourse->department->tuitionFees->first();

                        $creditFee = $tuitionFeeData->credit_fee ?? 0;
                        $programDuration = $tuitionFeeData->program_duration ?? '';
                        $admissionFee = $tuitionFeeData->admission_fee ?? 0;

                        // Calculate total credit fee
                        $totalCreditFee = $student->batch->courses->sum(fn($course) => $course->credit * $creditFee);

                        $totalCreditFee = $totalCreditFee + $admissionFee;

                        // Calculate remaining balance
                        $remainingBalance = $totalCreditFee - $totalPaid;

                        // Collect all courses for the student
                        $coursesList = [];
                        foreach ($student->batch->courses as $course) {
                            $coursesList[] = [
                                'id' => $course->id,
                                'name' => $course->name,
                                'credit' => $course->credit,
                            ];
                        }

                        // Payment data for the student
                        $paymentData = [
                            'id' => $payment->id,
                            'amount' => $payment->amount,
                            'currency' => $payment->currency,
                            'payment_intent_id' => $payment->payment_intent_id,
                            'created_at' => $payment->created_at,
                            'updated_at' => $payment->updated_at
                        ];

                        // Only add payments if the student has made a payment
                        $paymentsArray = $totalPaid > 0 ? [$paymentData] : [];

                        $formattedData[] = [
                            'student' => [
                                'id' => $student->id,
                                'first_name' => $student->first_name,
                                'last_name' => $student->last_name,
                                'email' => $student->email,
                                'phone_number' => $student->phone_number,
                                'address' => $student->address,
                                'student_id' => $student->student_id,
                                'program' => $student->program,
                                'user_status' => $student->user_status,
                                'description' => $student->description,
                                'batch_id' => $student->batch_id,
                                'blood_group' => $student->blood_group,
                                'gender' => $student->gender,
                                'postal_code' => $student->postal_code,
                                'profile_picture' => $student->profile_picture,
                                'created_at' => $student->created_at,
                                'updated_at' => $student->updated_at,
                                'tuition_fee' => [
                                    'program_duration' => $programDuration,
                                    'admission_fee' => $admissionFee,
                                    'credit_fee' => $creditFee,
                                ],
                            ],
                            'courses' => $coursesList, // Include all courses
                            'payments' => $paymentsArray,  // Only include payments if there's any
                            'total_paid' => $totalPaid,
                            'total_credits' => $totalCredits,
                            'total_credit_fee' => $totalCreditFee,
                            'remaining_balance' => $remainingBalance,
                        ];
                    } else {
                        // If student already exists, add payment to the existing student
                        $index = array_search($student->id, array_column($formattedData, 'student.id'));
                        $formattedData[$index]['total_paid'] += $payment->amount;  // Add to total paid
                        $formattedData[$index]['remaining_balance'] = $formattedData[$index]['total_credit_fee'] - $formattedData[$index]['total_paid'];

                        // Add payment data only if it has been paid
                        if ($payment->amount > 0) {
                            $formattedData[$index]['payments'][] = [
                                'id' => $payment->id,
                                'amount' => $payment->amount,
                                'currency' => $payment->currency,
                                'payment_intent_id' => $payment->payment_intent_id,
                                'created_at' => $payment->created_at,
                                'updated_at' => $payment->updated_at
                            ];
                        }
                    }
                }
            }

            // If no payments exist, return empty payments array
            if (empty($formattedData)) {
                return $this->successResponse('Payments retrieved successfully', ['payments' => []]);
            }

            return $this->successResponse('Payments retrieved successfully', [
                'payments' => $formattedData
            ]);
        });
    }

    public function getAdmissionFee()
    {
        return $this->safeCall(function () {
            $userId = Auth::id();
            $student = Student::where('user_id', $userId)
                ->with('batch.courses.department.tuitionFees')
                ->first();

            if (!$student) {
                return $this->errorResponse('Student not found', 404);
            }

            // Prepare the response with only the admission fee for each course
            $tuitionFees = [];
            foreach ($student->batch->courses as $course) {
                $department = $course->department;
                $tuitionFee = $department->tuitionFees->first(); // Assuming one tuition fee per department/course

                // If there's a tuition fee, add the admission fee to the response
                if ($tuitionFee) {
                    $tuitionFees[] = [
                        'course_name' => $course->name,
                        'admission_fee' => $tuitionFee->admission_fee,
                    ];
                }
            }

            // Return the admission fee data for each course
            return $this->successResponse('Tuition fee retrieved successfully', ['tuition_fees' => $tuitionFees]);
        });
    }




    public function checkout(Request $request)
    {
        return $this->safeCall(function () use ($request) {
            // Set the Stripe API key
            \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

            // Validate request
            $validated = $request->validate([
                'payment_method' => 'required|string',
                'amount' => 'required|numeric|min:0.01',
            ]);

            $user = Auth::user();
            $amountInCents = intval($validated['amount'] * 100); // Convert to cents

            // Ensure the user has a Stripe customer ID
            if (empty($user->stripe_id)) {
                try {
                    $user->createAsStripeCustomer();
                    $user = $user->fresh(); // Refresh user data
                    Log::info("Stripe customer created for user ID: {$user->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to create Stripe customer: " . $e->getMessage());
                    return $this->errorResponse('Failed to create Stripe customer.', 500);
                }
            }

            // Attach payment method to customer
            try {
                $user->addPaymentMethod($validated['payment_method']);
                Log::info("Payment method attached for user ID: {$user->id}");
            } catch (\Exception $e) {
                Log::error("Failed to attach payment method: " . $e->getMessage());
                return $this->errorResponse('Failed to attach payment method.', 500);
            }

            // Update default payment method
            try {
                $user->updateDefaultPaymentMethod($validated['payment_method']);
                Log::info("Default payment method updated for user ID: {$user->id}");
            } catch (\Exception $e) {
                Log::error("Failed to set default payment method: " . $e->getMessage());
                return $this->errorResponse('Failed to set default payment method.', 500);
            }

            // Create Payment Intent
            try {
                $paymentIntent = \Stripe\PaymentIntent::create([
                    'amount' => $amountInCents,
                    'currency' => 'usd',
                    'customer' => $user->stripe_id,
                    'payment_method' => $validated['payment_method'],
                    'confirm' => true,
                    'automatic_payment_methods' => [
                        'enabled' => true,
                        'allow_redirects' => 'never',
                    ],
                ]);

                Log::info("Payment Intent Created: " . json_encode($paymentIntent));

                $amountInDollars = number_format($amountInCents / 100, 2);

                // Save payment in database
                Payment::create([
                    'user_id' => $user->id,
                    // 'amount' => $amountInCents,
                    'amount' => $amountInDollars,
                    'currency' => 'usd',
                    'payment_intent_id' => $paymentIntent->id,
                ]);

                if ($paymentIntent->status === 'succeeded') {
                    Log::info("Payment successful for user ID: {$user->id}");

                    return $this->successResponse('Payment successful', [
                        // 'amount in cent' => $amountInCents,
                        'amount in dollar' => $amountInDollars,
                        'currency' => 'usd',
                        'payment_method_type' => $paymentIntent->payment_method ?? 'Unknown',
                        'student_name' => $user->first_name . ' ' . $user->last_name,
                        'payment_intent_status' => $paymentIntent->status,
                    ]);
                } else {
                    Log::error("Payment failed: Payment Intent Status - {$paymentIntent->status}");
                    return $this->errorResponse('Payment failed. Please try again.', 500);
                }
            } catch (\Stripe\Exception\CardException $e) {
                Log::error("Stripe Card Exception: " . $e->getMessage());
                return $this->errorResponse('Payment failed: ' . $e->getMessage(), 500);
            } catch (\Stripe\Exception\RateLimitException $e) {
                Log::error("Stripe Rate Limit Exception: " . $e->getMessage());
                return $this->errorResponse('Too many requests to Stripe.', 500);
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                Log::error("Stripe Invalid Request: " . $e->getMessage());
                return $this->errorResponse('Invalid request to Stripe.', 500);
            } catch (\Stripe\Exception\AuthenticationException $e) {
                Log::error("Stripe Authentication Error: " . $e->getMessage());
                return $this->errorResponse('Stripe authentication failed.', 500);
            } catch (\Stripe\Exception\ApiConnectionException $e) {
                Log::error("Stripe API Connection Error: " . $e->getMessage());
                return $this->errorResponse('Network error. Please try again.', 500);
            } catch (\Stripe\Exception\ApiErrorException $e) {
                Log::error("Stripe API Error: " . $e->getMessage());
                return $this->errorResponse('Payment processing error.', 500);
            } catch (\Exception $e) {
                Log::error("General Payment Error: " . $e->getMessage());
                return $this->errorResponse('Payment failed. Please try again.', 500);
            }
        });
    }
}
