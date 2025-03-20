<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MCQ extends Model
{
    protected $fillable = ['course_id', 'material_id', 'question', 'answers', 'correct_answer'];

    protected $casts = [
        'answers' => 'array',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
