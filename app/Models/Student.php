<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $primaryKey = 'student_number';
    public $incrementing = false;

    protected $fillable = [
        'student_number',
        'first_name',
        'last_name',
        'middle_name',
        'email',
        'phone',
        'birth_date',
        'address',
        'status',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    /**
     * Get the enrolled students for this student.
     */
    public function enrollments()
    {
        return $this->hasMany(EnrolledStudent::class, 'student_number', 'student_number');
    }

    /**
     * Get the student's extended profile.
     */
    public function profile()
    {
        return $this->hasOne(StudentProfile::class, 'student_number', 'student_number');
    }

    /**
     * Get the student's educational background.
     */
    public function educationalBackground()
    {
        return $this->hasOne(StudentEducationalBackground::class, 'student_number', 'student_number');
    }

    /**
     * Get the student's family information.
     */
    public function familyInfo()
    {
        return $this->hasOne(StudentFamilyInfo::class, 'student_number', 'student_number');
    }

    /**
     * Get the student's emergency contact.
     */
    public function emergencyContact()
    {
        return $this->hasOne(StudentEmergencyContact::class, 'student_number', 'student_number');
    }

    /**
     * Get the user account associated with this student.
     */
    public function user()
    {
        return $this->hasOne(User::class, 'student_number', 'student_number');
    }

    /**
     * Check if the student has a user account.
     */
    public function hasAccount(): bool
    {
        return $this->user()->exists();
    }

    /**
     * Get the current active enrollment.
     */
    public function currentEnrollment()
    {
        $activeAcademicCalendar = AcademicCalendar::orderBy('start_date', 'desc')->first();
        if (!$activeAcademicCalendar) {
            return null;
        }

        return $this->enrollments()
            ->where('acad_id', $activeAcademicCalendar->calendar_id)
            ->first();
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'student_number';
    }

    /**
     * Get full name attribute.
     */
    public function getFullNameAttribute(): string
    {
        $name = "{$this->first_name}";
        if ($this->middle_name) {
            $name .= " {$this->middle_name}";
        }
        $name .= " {$this->last_name}";
        return $name;
    }
}

