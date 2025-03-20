<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TuitionFee extends Model
{
    protected $fillable = [
        'title',
        'credit_hour',
        'program_duration',
        'admission_fee',
        'credit_fee',
        'our_department_id', // Ensure this is included
        'our_faculty_id'
    ];

    /**
     * Tuition fee belongs to a department.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(OurDepartment::class, 'our_department_id');
    }



}
