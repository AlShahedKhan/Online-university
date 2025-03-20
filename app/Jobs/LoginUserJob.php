<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginUserJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $credentials;

    /**
     * Create a new job instance.
     *
     * @param array $credentials
     */
    public function __construct(array $credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * Execute the job.
     *
     * @return array
     */
    public function handle(): array
    {
        try {
            Log::info('Attempting login for user', ['email' => $this->credentials['email']]);

            // Find user by email
            $user = User::where('email', $this->credentials['email'])->first();

            if (!$user) {
                Log::error('User not found.', ['email' => $this->credentials['email']]);
                return [
                    'error' => 'User not found.',
                    'status_code' => 404,
                ];
            }

            // Check if password is hashed with Bcrypt
            if (!Hash::needsRehash($user->password)) {
                // If password is Bcrypt hashed, proceed with normal authentication
                if (!$token = JWTAuth::attempt($this->credentials)) {
                    Log::warning('Invalid credentials provided.', ['email' => $this->credentials['email']]);
                    return [
                        'error' => 'Invalid credentials.',
                        'status_code' => 401,
                    ];
                }
            } else {
                // If the stored password is not Bcrypt, compare the plain password manually
                if ($user->password !== $this->credentials['password']) {
                    Log::warning('Invalid credentials provided for non-Bcrypt user.', ['email' => $this->credentials['email']]);
                    return [
                        'error' => 'Invalid credentials.',
                        'status_code' => 401,
                    ];
                }

                // Manually authenticate the user and create a token
                $token = JWTAuth::fromUser($user);
            }

            // Check user approval status for non-admins
            if (!$user->is_admin && $user->is_approved == 0) {
                Log::warning('Login attempt for unapproved account.', ['user_id' => $user->id]);
                return [
                    'error' => 'Your account is pending admin approval.',
                    'status_code' => 403,   
                ];
            }

            // Include role in JWT claims
            $role = $user->is_admin ? 'administration' : ($user->is_professor ? 'professor' : 'student');
            $name = $user->first_name . ' ' . $user->last_name;
            $email = $user->email;
            $token = JWTAuth::claims([
                'role' => $role,
                'name' => $name,
                'email' => $email,
                // 'iss' => config('app.url') . '/api/login'
                'iss' => 'https://' . parse_url(config('app.url'), PHP_URL_HOST) . '/api/login'

            ])->fromUser($user);

            Log::info('Login successful.', ['user_id' => $user->id]);

            return [
                'token' => $token,
                'user' => $user->toArray(),
                'role' => $role,
            ];
        } catch (\Exception $e) {
            Log::error('An error occurred during login.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'error' => 'Unexpected error occurred.',
                'status_code' => 500,
            ];
        }
    }
}
