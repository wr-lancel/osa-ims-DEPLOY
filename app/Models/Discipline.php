<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discipline extends Model
{
    use HasFactory;

    protected $table = 'discipline';
    protected $primaryKey = 'discipline_id';
    public $incrementing = true;

    /**
     * Cached workflow steps to avoid repeated DB queries when rendering lists.
     */
    private static ?object $cachedSteps = null;

    protected $fillable = [
        'student_number',
        'enrollment_id',
        'violation_date',
        'violation_type',
        'description',
        'sanction',
        'date_resolved',
        'severity',
        'status',
        'remarks',
        'narrative_report',
        'narrative_report_file',
        'reported_by',
    ];

    protected $casts = [
        'violation_date' => 'date',
        'date_resolved' => 'date',
    ];

    /**
     * Get the student that this violation belongs to.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }

    /**
     * Get the enrollment (student-in-term) for this violation.
     */
    public function enrollment()
    {
        return $this->belongsTo(EnrolledStudent::class, 'enrollment_id', 'enrollment_id');
    }

    /**
     * Get the user who reported this violation.
     */
    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by', 'user_id');
    }

    /**
     * Get the status history for this case.
     */
    public function disciplineHistories()
    {
        return $this->hasMany(DisciplineHistory::class, 'case_id', 'discipline_id');
    }

    /**
     * Get the scheduled meetings for this case.
     */
    public function meetings()
    {
        return $this->hasMany(DisciplineMeeting::class, 'case_id', 'discipline_id');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'discipline_id';
    }

    /**
     * Get severity badge color.
     */
    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'Major' => 'red',
            'Moderate' => 'yellow',
            'Minor' => 'green',
            default => 'gray',
        };
    }

    /**
     * Get status badge color based on workflow position.
     */
    public function getStatusColorAttribute(): string
    {
        if (static::$cachedSteps === null) {
            static::$cachedSteps = DisciplineWorkflowStep::ordered()->get();
        }

        $steps = static::$cachedSteps;
        $total = $steps->count();

        if ($total === 0) {
            return 'gray';
        }

        $step = $steps->firstWhere('name', $this->status);

        if (!$step) {
            return 'gray';
        }

        if ($step->is_terminal) {
            return 'green';
        }

        $position = $step->sort_order / $total;

        if ($position > 0.5) {
            return 'yellow';
        }

        return 'gray';
    }
}

