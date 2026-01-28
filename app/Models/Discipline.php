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

    protected $fillable = [
        'student_number',
        'violation_date',
        'violation_type',
        'description',
        'severity',
        'status',
        'reported_by',
    ];

    protected $casts = [
        'violation_date' => 'date',
    ];

    /**
     * Get the student that this violation belongs to.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }

    /**
     * Get the user who reported this violation.
     */
    public function reportedBy()
    {
        return $this->belongsTo(User::class, 'reported_by', 'user_id');
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
        return match($this->severity) {
            'Major' => 'red',
            'Moderate' => 'yellow',
            'Minor' => 'green',
            default => 'gray',
        };
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'Resolved' => 'green',
            'Under Investigation' => 'yellow',
            'Pending' => 'gray',
            default => 'gray',
        };
    }
}

