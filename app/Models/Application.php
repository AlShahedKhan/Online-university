<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    use HasFactory;

    protected $table = 'applications';

    protected $fillable = [
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'nationality',
        'contact_number',
        'email',
        'address',
        'nid_number',
        'program',
        'bachelor_institution',
        'degree_earned',
        'graduation_year',
        'gpa',
        'job_title',
        'years_experience',
        'responsibilities',
        'passport_path',
        'nid_path',
        'application_status', // Newly added
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'graduation_year' => 'integer',
        'gpa' => 'float',
        'years_experience' => 'integer',
        'application_status' => 'string',
    ];
}
