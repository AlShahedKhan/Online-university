<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->string('stu_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('program');
            $table->string('batch');
            $table->decimal('cgpa', 3, 2);
            $table->string('certificate_date');
            $table->string('issued_by');
            $table->boolean('approved')->default(false);
            $table->timestamps();
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');

        });
    }
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
