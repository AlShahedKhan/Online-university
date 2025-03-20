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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Course name (required)
            $table->text('description'); // Course description (required)
            $table->string('course_image')->nullable(); // Course image (nullable)
            $table->string('credit'); // Course credit (required)
            // $table->foreignId('professor_id')->constrained('professors')->onDelete('cascade'); // Select professor (dropdown)
            $table->foreignId('department_id')->constrained('our_departments')->onDelete('cascade'); // Select professor (dropdown)
            $table->enum('status', ['draft', 'published'])->default('published'); // Course status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
