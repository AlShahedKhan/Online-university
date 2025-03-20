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
        Schema::create('materials', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete(); // Foreign key for batches table
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete(); // Foreign key for courses table
            $table->foreignId('professor_id')->constrained('users')->cascadeOnDelete(); // Foreign key for professors table
            $table->string('title'); // Course title
            $table->string('subtitle')->nullable(); // Optional subtitle
            $table->date('date'); // Date of the course
            $table->string('total_time'); // Total time (e.g., '5 Hours')
            $table->text('description'); // Course description
            $table->string('video_path')->nullable(); // Path to uploaded video
            $table->string('assignment_path')->nullable(); // Path to uploaded assignment
            $table->string('submited_assignment_path')->nullable(); // Path to uploaded assignment
            $table->string('marks')->nullable();
            $table->timestamps(); // Created at and updated at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
