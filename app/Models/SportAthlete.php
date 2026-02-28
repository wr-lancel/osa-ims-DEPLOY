<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SportAthlete extends Model
{
    use HasFactory;

    protected $fillable = [
        'sport_id',
        'student_number',
    ];

    /**
     * Get the sport.
     */
    public function sport()
    {
        return $this->belongsTo(Sport::class, 'sport_id', 'sport_id');
    }

    /**
     * Get the student.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }
}
