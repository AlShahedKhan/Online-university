<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAnswer extends Model
{
    use HasFactory;

    protected $fillable = ['student_id', 'mcq_id', 'selected_answer', 'is_correct'];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

 
    public function mcq()
    {
        return $this->belongsTo(Mcq::class, 'mcq_id');
    }
}
