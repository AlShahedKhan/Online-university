<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('profile_picture')->nullable(); // Profile image path
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone_number')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('student_id')->nullable();
            $table->string('blood_group')->nullable();
            $table->string('program')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->enum('user_status', ['Active', 'Inactive'])->default('Active');
            $table->text('description')->nullable();
            $table->foreignId('batch_id')->constrained('batches')->onDelete('cascade');
            $table->string('topic')->nullable();
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
