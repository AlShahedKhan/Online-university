<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class AuthHelper
{
    public static function checkUser()
    {
        $user = Auth::check();
        if (!$user) {
            abort(403, 'Forbidden: You are not authorized to perform this action.');
        }
    }
    public static function checkAdmin()
    {
        $user = Auth::user();
        if (!$user || !$user->is_admin) {
            abort(403, 'Forbidden: Only admins are authorized to perform this action.');
        }
    }
    public static function checkProfessor()
    {
        $user = Auth::user();
        if (!$user || !$user->is_professor) {
            abort(403, 'Forbidden: Only professors are authorized to perform this action.');
        }
    }
}
