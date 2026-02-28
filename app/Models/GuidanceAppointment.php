<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuidanceAppointment extends Model
{
    use HasFactory;

    protected $table = 'guidance_appointments';
    protected $primaryKey = 'appointment_id';
    public $incrementing = true;

    protected $fillable = [
        'student_number',
        'employee_id',
        'appointment_date',
        'appointment_time',
        'concern',
        'appointment_type',
        'status',
        'notes',
        'admin_remarks',
        'narrative_report',
        'narrative_report_file',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'appointment_time' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Get the student that requested this appointment.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }

    /**
     * Get the employee that requested this appointment.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    /**
     * Get the user who approved this appointment request.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    /**
     * Get the user who rejected this appointment request.
     */
    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by', 'user_id');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'appointment_id';
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match (strtolower($this->status)) {
            'approved' => 'green',
            'completed' => 'green',
            'pending' => 'yellow',
            'rejected' => 'red',
            'cancelled' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get formatted status display.
     */
    public function getFormattedStatusAttribute(): string
    {
        return ucfirst(strtolower($this->status));
    }

    /**
     * Scope a query to only include pending appointments.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved appointments.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected appointments.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope a query to only include completed appointments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include cancelled appointments.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
}
