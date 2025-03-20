<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Professor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', // Add this line
        'first_name',
        'last_name',
        'email_address',
        'phone_number',
        'designation',
        'address',
        'postal_code',
        'employee_id',
        'blood_group',
        'gender',
        'user_status',
        'description',
        'profile_picture',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function batches()
    {
        return $this->belongsToMany(Batch::class, 'batch_instructor');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'batch_id');
    }

    public function materials()
    {
        return $this->hasMany(Material::class, 'professor_id');
    }

}

