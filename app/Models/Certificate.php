<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    protected $fillable = [
        'student_id',
        'stu_id',
        'first_name',
        'last_name',
        'program',
        'batch',
        'cgpa',
        'certificate_date',
        'issued_by',
        'approved', // Add the approved field to the fillable array
    ];
}
