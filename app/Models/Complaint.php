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
     * Get the respondent's enrollment (if any).
     */
    public function respondentEnrollment()
    {
        return $this->belongsTo(EnrolledStudent::class, 'respondent_enrolled_id', 'enrollment_id');
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
