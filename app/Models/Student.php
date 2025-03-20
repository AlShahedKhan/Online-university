<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'profile_picture',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'program',
        'address',
        'postal_code',
        'student_id',
        'blood_group',
        'gender',
        'user_status',
        'description',
        'batch_id',
        'user_id', // Add user_id to the fillable property
    ];

    /**
     * Get the full name of the student.
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function professor()
    {
        return $this->belongsTo(Professor::class, 'batch_id', 'id');
    }
    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class, 'student_table_id', 'id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
