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
        Schema::create('applications', function (Blueprint $table) {
            // Personal Information
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('nationality')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('nid_number')->nullable();
            $table->string('program')->nullable();

            // Academic Background
            $table->string('bachelor_institution')->nullable();
            $table->string('degree_earned')->nullable();
            $table->year('graduation_year')->nullable();
            $table->decimal('gpa', 4, 2)->nullable();

            // Work Experience
            $table->string('job_title')->nullable();
            $table->integer('years_experience')->nullable();
            $table->text('responsibilities')->nullable();

            // Supporting Documents
            $table->string('passport_path')->nullable();
            $table->string('nid_path')->nullable();
            $table->enum('application_status', ['pending', 'approved', 'rejected'])->default('pending');
            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
