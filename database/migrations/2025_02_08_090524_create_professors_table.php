<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfessorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('professors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email_address')->unique();
            $table->string('phone_number');
            $table->string('designation');
            $table->string('address');
            $table->string('postal_code');
            $table->string('employee_id')->unique();
            $table->string('blood_group');
            $table->enum('gender', ['Male', 'Female', 'Other']);
            $table->enum('user_status', ['Active', 'Inactive']);
            $table->text('description')->nullable();
            $table->string('profile_picture')->nullable(); // Path to the uploaded photo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('professors');
    }
}
