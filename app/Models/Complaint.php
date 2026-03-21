<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $table = 'complaints';
    protected $primaryKey = 'complaint_id';
    public $incrementing = true;

    protected $fillable = [
        'complainant_enrolled_id',
        'respondent_enrolled_id',
        'respondent_type',
        'respondent_employee_id',
        'respondent_name',
        'category',
        'subject',
        'description',
        'incident_date',
        'location',
        'status',
        'anonymous',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'anonymous' => 'boolean',
    ];

    /**
     * Get the complainant's enrollment.
     */
    public function complainantEnrollment()
    {
        return $this->belongsTo(EnrolledStudent::class, 'complainant_enrolled_id', 'enrollment_id');
    }

    /**
     * Get the respondent's enrollment (if any) — used when respondent_type = 'student'.
     */
    public function respondentEnrollment()
    {
        return $this->belongsTo(EnrolledStudent::class, 'respondent_enrolled_id', 'enrollment_id');
    }

    /**
     * Get the respondent employee — used when respondent_type = 'employee'.
     */
    public function respondentEmployee()
    {
        return $this->belongsTo(Employee::class, 'respondent_employee_id', 'employee_id');
    }

    /**
     * Get a display name for the respondent regardless of type.
     */
    public function getRespondentDisplayNameAttribute(): ?string
    {
        return match ($this->respondent_type) {
            'student'  => $this->respondentEnrollment?->student?->full_name,
            'employee' => $this->respondentEmployee?->full_name,
            'other'    => $this->respondent_name,
            default    => null,
        };
    }

    /**
     * Get the status/remarks history for this complaint.
     */
    public function complaintHistories()
    {
        return $this->hasMany(ComplaintHistory::class, 'complaint_id', 'complaint_id');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'complaint_id';
    }
}
