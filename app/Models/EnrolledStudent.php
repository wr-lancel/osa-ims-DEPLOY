<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnrolledStudent extends Model
{
    use HasFactory;

    protected $primaryKey = 'enrollment_id';
    public $incrementing = true;

    protected $fillable = [
        'student_number',
        'acad_id',
        'course_id',
        'section_id',
        'year_level',
        'enrollment_status',
        'enrollment_date',
        'grade',
        'semester',
        'academic_year', // Keep for backward compatibility
    ];

    protected $casts = [
        'enrollment_date' => 'date',
    ];

    /**
     * Get the student that this enrollment belongs to.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }

    /**
     * Get the academic calendar that this enrollment belongs to.
     */
    public function academicCalendar()
    {
        return $this->belongsTo(AcademicCalendar::class, 'acad_id', 'calendar_id');
    }

    /**
     * Get the course that this enrollment belongs to.
     */
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    /**
     * Get the section that this enrollment belongs to.
     */
    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id', 'section_id');
    }

    /**
     * Get the candidacy applications for this enrollment.
     */
    public function candidacyApplications()
    {
        return $this->hasMany(CandidacyApplication::class, 'enrollment_id', 'enrollment_id');
    }

    /**
     * Get the discipline cases for this enrollment.
     */
    public function disciplineCases()
    {
        return $this->hasMany(Discipline::class, 'enrollment_id', 'enrollment_id');
    }

    /**
     * Get complaints where this enrollment is the complainant.
     */
    public function complaintsAsComplainant()
    {
        return $this->hasMany(Complaint::class, 'complainant_enrolled_id', 'enrollment_id');
    }

    /**
     * Get complaints where this enrollment is the respondent.
     */
    public function complaintsAsRespondent()
    {
        return $this->hasMany(Complaint::class, 'respondent_enrolled_id', 'enrollment_id');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'enrollment_id';
    }
}

