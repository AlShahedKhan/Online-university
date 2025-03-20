<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchCourse extends Model
{
    use HasFactory;

    // Table name (if different from plural of model name)
    protected $table = 'batch_course';

    // Fillable attributes for mass assignment
    protected $fillable = [
        'batch_id',
        'course_id',
        'professor_id',
    ];

    // Relationships (if needed, for example)
    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

   
}
