<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViolationSummary extends Model
{
    use HasFactory;

    protected $table = 'violation_summary';
    protected $primaryKey = 'summary_id';
    public $incrementing = true;

    protected $fillable = [
        'student_number',
        'academic_year',
        'total_violations',
        'minor_violations',
        'major_violations',
        'status',
    ];

    /**
     * Get the student this summary belongs to.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }
}
