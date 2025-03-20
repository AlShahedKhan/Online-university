<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Result extends Model
{
    protected $fillable = [
        'student_table_id',
        'first_name',
        'last_name',
        'date',
        'topic',
        'result',
        'remarks',
        'course_id'  // Add course_id to relate the result to a course
    ];

    /**
     * Get the student associated with the result.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_table_id', 'id');
    }

    // In your Result model

    /**
     * Get the course associated with the result.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

}
