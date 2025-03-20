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
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Required batch title
            $table->string('subtitle')->nullable(); // Optional subtitle
            $table->string('batch_image')->nullable(); // Stores image path
            $table->timestamps();
        });
        Schema::create('batch_instructor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('batches')->onDelete('cascade');  // Reference to batches table
            $table->foreignId('professor_id')->constrained('professors')->onDelete('cascade');  // Reference to professors table
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
        Schema::dropIfExists('batch_instructor');
    }
};
