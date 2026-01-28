<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $primaryKey = 'section_id';
    public $incrementing = true;

    protected $fillable = [
        'course_id',
        'calendar_id',
        'section_code',
        'section_name',
        'year_level',
    ];

    /**
     * Get the course that this section belongs to.
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    /**
     * Get the academic calendar that this section belongs to.
     */
    public function academicCalendar()
    {
        return $this->belongsTo(AcademicCalendar::class, 'calendar_id', 'calendar_id');
    }

    /**
     * Get the enrolled students for this section.
     */
    public function enrolledStudents()
    {
        return $this->hasMany(EnrolledStudent::class, 'section_id', 'section_id');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'section_id';
    }
}

