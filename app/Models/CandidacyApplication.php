<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidacyApplication extends Model
{
    use HasFactory;

    protected $table = 'candidacy_applications';
    protected $primaryKey = 'application_id';
    public $incrementing = true;

    protected $fillable = [
        'org_id',
        'enrollment_id',
        'position_id',
        'acad_id',
        'party_affiliation',
        'unit_load',
        'platform_statement',
        'motivation',
        'status',
        'submitted_at',
        'reviewed_at',
        'review_remarks',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the organization.
     */
    public function organization()
    {
        return $this->belongsTo(StudentOrganization::class, 'org_id', 'org_id');
    }

    /**
     * Get the enrollment (student-in-term).
     */
    public function enrollment()
    {
        return $this->belongsTo(EnrolledStudent::class, 'enrollment_id', 'enrollment_id');
    }

    /**
     * Get the position.
     */
    public function position()
    {
        return $this->belongsTo(OrgPosition::class, 'position_id', 'position_id');
    }

    /**
     * Get the academic calendar (term).
     */
    public function academicCalendar()
    {
        return $this->belongsTo(AcademicCalendar::class, 'acad_id', 'calendar_id');
    }

    /**
     * Get the attachments.
     */
    public function attachments()
    {
        return $this->hasMany(CandidacyApplicationAttachment::class, 'application_id', 'application_id');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'application_id';
    }
}
