<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'Account',
            'email' => 'admin@test.com',
            'password' => Hash::make('12345678'),
            'is_admin' => 1
        ]);
        User::create([
            'first_name' => 'Professor',
            'last_name' => 'Account',
            'email' => 'professor@test.com',
            'password' => Hash::make('12345678'),
            'is_professor' => 1,
            'is_approved' => 1
        ]);
        User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@test.com',
            'password' => Hash::make('12345678'),
        ]);
    }
}
