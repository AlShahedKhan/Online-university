<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssignmentSubmissionsTable extends Migration
{
    public function up()
    {
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id'); // Foreign key to the student
            $table->unsignedBigInteger('material_id'); // Foreign key to the material
            $table->unsignedBigInteger('course_id');   // Foreign key to the course
            $table->unsignedBigInteger('batch_id');    // Foreign key to the batch
            $table->string('submited_assignment_path'); // Path to the uploaded assignment
            $table->decimal('marks', 5, 2)->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
            $table->foreign('batch_id')->references('id')->on('batches')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('assignment_submissions');
    }
}
