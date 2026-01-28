<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SportsBorrowing extends Model
{
    use HasFactory;

    protected $table = 'sports_borrowing';
    protected $primaryKey = 'borrowing_id';
    public $incrementing = true;

    protected $fillable = [
        'student_number',
        'employee_id',
        'item_name',
        'description',
        'borrow_date',
        'return_date',
        'expected_return_date',
        'status',
        'notes',
        'admin_remarks',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
    ];

    protected $casts = [
        'borrow_date' => 'date',
        'return_date' => 'date',
        'expected_return_date' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Get the student that borrowed this item.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }

    /**
     * Get the employee that borrowed this item.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    /**
     * Get the user who approved this borrowing request.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'user_id');
    }

    /**
     * Get the user who rejected this borrowing request.
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
        return 'borrowing_id';
    }

    /**
     * Check if item is overdue.
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'borrowed' 
            && $this->expected_return_date 
            && $this->expected_return_date < now()->toDateString();
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->is_overdue) {
            return 'red';
        }
        
        return match(strtolower($this->status)) {
            'returned' => 'green',
            'borrowed' => 'blue',
            'approved' => 'green',
            'pending' => 'yellow',
            'rejected' => 'red',
            'overdue' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get formatted status display.
     */
    public function getFormattedStatusAttribute(): string
    {
        if ($this->is_overdue) {
            return 'Overdue';
        }
        
        return ucfirst(strtolower($this->status));
    }

    /**
     * Scope a query to only include overdue items.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'borrowed')
            ->where('expected_return_date', '<', now()->toDateString());
    }

    /**
     * Scope a query to only include borrowed items.
     */
    public function scopeBorrowed($query)
    {
        return $query->where('status', 'borrowed');
    }

    /**
     * Scope a query to only include returned items.
     */
    public function scopeReturned($query)
    {
        return $query->where('status', 'returned');
    }

    /**
     * Scope a query to only include pending items.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved items.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected items.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}

