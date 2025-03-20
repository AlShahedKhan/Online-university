<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'course_image',
        'department_id',
        'status',
        'credit'
    ];

    public function materials()
    {
        return $this->hasMany(Material::class);
    }
    public function batches()
    {
        return $this->belongsToMany(Batch::class, 'batch_course');
    }

    public function department()
    {
        return $this->belongsTo(OurDepartment::class);
    }

    public function mcqs()
    {
        return $this->hasMany(MCQ::class);
    }
}
