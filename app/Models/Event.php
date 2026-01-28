<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $primaryKey = 'event_id';
    public $incrementing = true;

    protected $fillable = [
        'org_id',
        'event_name',
        'description',
        'event_date',
        'start_time',
        'end_time',
        'venue',
        'status',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    /**
     * Get the organization that this event belongs to.
     */
    public function organization()
    {
        return $this->belongsTo(StudentOrganization::class, 'org_id', 'org_id');
    }

    /**
     * Get the user who created this event.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'event_id';
    }

    /**
     * Get status badge color.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'Completed' => 'green',
            'Upcoming' => 'blue',
            'Planning' => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Check if event is upcoming.
     */
    public function getIsUpcomingAttribute(): bool
    {
        return $this->event_date >= now()->toDateString() && $this->status === 'Upcoming';
    }
}

