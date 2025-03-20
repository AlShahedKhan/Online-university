<?php

namespace App\Models;

use App\Models\Material;
use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    protected $table = 'batches';
    protected $fillable = [
        'title',
        'subtitle',
        'batch_image',
        'instructor_id'
    ];

    public function materials()
    {
        return $this->hasMany(Material::class);
    }
    public function instructors()
    {
        return $this->belongsToMany(Professor::class, 'batch_instructor');
    }
    public function students()
    {
        return $this->hasMany(Student::class, 'batch_id');
    }
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function courses()
    {
        return $this->hasManyThrough(Course::class, BatchCourse::class, 'batch_id', 'id', 'id', 'course_id');
    }

    // public function courses()
    // {
    //     return $this->belongsToMany(Course::class, 'batch_course', 'batch_id', 'course_id')
    //                 ->withPivot('professor_id') // Optional: if you need professor data as well
    //                 ->withTimestamps(); // Optional: if you want to retrieve timestamps
    // }
}
