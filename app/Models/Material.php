<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'materials';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'batch_id',
        'course_id',
        'professor_id',
        'title',
        'subtitle',
        'date',
        'total_time',
        'description',
        'video_path',
        'assignment_path',
        'submited_assignment_path',
        'marks',
    ];

    /**
     * Get the batch associated with the material.
     */
    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Get the course associated with the material.
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    // In Material.php
    public function mcqs()
    {
        return $this->hasMany(MCQ::class);
    }
}
