<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $table = 'payments';

    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'payment_intent_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // In Payment Model
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
