<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuidanceCase extends Model
{
    use HasFactory;

    protected $primaryKey = 'guidance_case_id';
    public $incrementing = true;

    protected $fillable = [
        'enrollment_id',
        'case_no',
        'case_type',
        'concern',
        'status',
        'assigned_staff_id',
        'requested_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
    ];

    /**
     * Get the enrollment that this case belongs to.
     */
    public function enrollment()
    {
        return $this->belongsTo(EnrolledStudent::class, 'enrollment_id', 'enrollment_id');
    }

    /**
     * Get the assigned staff member.
     */
    public function assignedStaff()
    {
        return $this->belongsTo(Employee::class, 'assigned_staff_id', 'employee_id');
    }

    /**
     * Get all actions for this case.
     */
    public function actions()
    {
        return $this->hasMany(GuidanceCaseAction::class, 'guidance_case_id', 'guidance_case_id')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get the student through enrollment.
     */
    public function student()
    {
        return $this->hasOneThrough(
            Student::class,
            EnrolledStudent::class,
            'enrollment_id',
            'student_id',
            'enrollment_id',
            'student_id'
        );
    }
}
