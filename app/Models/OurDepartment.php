<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OurDepartment extends Model
{
    protected $table = 'our_departments';

    protected $fillable = [
        'image',
        'title',
        'overview',
        'description',
        'our_faculty_id'
    ];

    public function course(){
        return $this->hasMany(Course::class);
    }
    /**po
     * Relationship: Department belongs to a Faculty.
     */

    public function faculty()
    {
        return $this->belongsTo(OurFaculty::class, 'our_faculty_id'); // assuming 'our_faculty_id' is the foreign key
    }

     // A department has many tuition fees
     public function tuitionFees()
     {
         return $this->hasMany(TuitionFee::class);
     }

     public function eligibilities()
     {
         return $this->hasMany(Eligibility::class);
     }

}
