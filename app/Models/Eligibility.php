<?php

namespace App\Models;

use App\Models\OurDepartment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Eligibility extends Model
{

    protected $fillable = ['title', 'description', 'our_faculty_id', 'our_department_id'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(OurDepartment::class, 'our_department_id');
    }
    
}
