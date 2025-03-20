<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OurFaculty extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['image', 'title', 'description'];

    public function departments()
    {
        return $this->hasMany(OurDepartment::class, 'our_faculty_id'); // assuming 'our_faculty_id' is the foreign key
    }


}
