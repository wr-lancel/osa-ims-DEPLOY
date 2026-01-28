<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentEducationalBackground extends Model
{
    use HasFactory;

    protected $table = 'student_educational_backgrounds';

    protected $fillable = [
        'student_number',
        'elementary_school',
        'elementary_address',
        'elementary_graduated',
        'senior_high_school',
        'senior_high_strand',
        'senior_high_address',
        'senior_high_graduated',
        'honors_received',
    ];

    protected $casts = [
        'elementary_graduated' => 'date',
        'senior_high_graduated' => 'date',
    ];

    /**
     * Get the student that owns this educational background.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }
}

