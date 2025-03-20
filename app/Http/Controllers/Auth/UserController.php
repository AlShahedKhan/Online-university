<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\AuthHelper;
use App\Models\User;
use App\Mail\OtpMail;
use Ichtrojan\Otp\Otp;
use App\Mail\UserApprove;
use App\Jobs\LoginUserJob;
use Illuminate\Http\Request;
use App\Traits\HandlesApiResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Exceptions\JWTException;

class UserController extends Controller
{
    use HandlesApiResponse;

    public function login(Request $request)
    {
        return $this->safeCall(function () use ($request) {
            $credentials = $request->only(['email', 'password']);

            // Dispatch the LoginUser job and handle the response
            $job = new LoginUserJob($credentials);
            $result = $job->handle();

            // Handle job errors
            if (isset($result['error'])) {
                return $this->errorResponse($result['error'], $result['status_code'] ?? 400);
            }

            // Generate a secure cookie for the token
            $cookie = cookie('token', $result['token'], 10080, '/', null, true, true, false, 'Strict');

            return $this->successResponse('Login successful', [
                'token' => $result['token'],
                'user' => $result['user'],
            ])->cookie($cookie);
        });
    }
    public function forgotPassword(Request $request)
    {
        return $this->safeCall(function () use ($request) {

            $request->validate([
                'email' => 'required|email',
            ]);

            // Check if user exists
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return $this->errorResponse('Email not found.', 404);
            }

            // Generate a numeric OTP (6 digits)
            $otp = new Otp();
            $generatedOtp = $otp->generate($request->email, 'numeric', 6); // Numeric OTP, 6 digits

            // Check if OTP generation was successful (the returned object should have 'status' key)
            if ($generatedOtp->status) {
                // Send OTP via email
                Mail::to($request->email)->send(new OtpMail($generatedOtp->token));

                return $this->successResponse('OTP sent to your email for password reset.');
            }
        });

        return $this->errorResponse('Failed to generate OTP.', 500);
    }
    public function verifyOtp(Request $request)
    {
        return $this->safeCall(function () use ($request) {

            $request->validate([
                'email' => 'required|email',
                'otp' => 'required|numeric|digits:6',
            ]);

            $otp = new Otp();
            $validation = $otp->validate($request->email, $request->otp);

            if (!$validation->status) {
                return $this->errorResponse($validation->message, 400);
            }

            // Store OTP verification status in cache/session
            cache()->put('otp_verified:' . $request->email, true, now()->addMinutes(10)); // Expires in 10 minutes

            return $this->successResponse('OTP is valid. You can now reset your password.');
        });
    }
    public function resetPassword(Request $request)
    {
        return $this->safeCall(function () use ($request) {

            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            // Check if OTP was verified
            $otpVerified = cache()->get('otp_verified:' . $request->email);

            if (!$otpVerified) {
                return $this->errorResponse('OTP verification is required before resetting the password.', 400);
            }

            // Find the user by email
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return $this->errorResponse('User not found.', 404);
            }

            // Reset the user's password
            $user->password = bcrypt($request->password);
            $user->save();

            // Invalidate the OTP verification status in cache
            cache()->forget('otp_verified:' . $request->email);

            return $this->successResponse(
                'Password reset successfully. OTP verification is now invalidated.',
                ['user' => $user],
            );
        });
    }
    public function logout()
    {
        return $this->safeCall(function () {
            try {
                if (!$token = JWTAuth::getToken()) {
                    return $this->errorResponse('Token not provided', 400);
                }

                JWTAuth::invalidate($token);
                return $this->successResponse('Logout successful');
            } catch (JWTException $e) {
                return $this->errorResponse('Failed to invalidate token', 500);
            }
        });
    }

    public function getProfile()
    {
        return $this->safeCall(function () {
            $user = Auth::user();

            // Check if the user is a professor and eager load the professor relationship
            if ($user->is_professor) {
                $user = $user->load('professor');  // Use load to include the professor data
            }else{
                $user = $user->load('student');
            }
            return $this->successResponse('User profile', ['user' => $user]);
        });
    }

}
