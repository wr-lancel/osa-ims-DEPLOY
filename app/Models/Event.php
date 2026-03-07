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

    /**
     * Get organizations (names) that already have an event on the given date.
     * Exclude a specific event (when editing) and/or a specific org.
     *
     * @param  string  $date  Y-m-d
     * @param  int|null  $excludeEventId
     * @param  int|null  $excludeOrgId
     * @return array<string>
     */
    public static function otherOrgsOnDate(string $date, ?int $excludeEventId = null, ?int $excludeOrgId = null): array
    {
        $query = static::with('organization')
            ->whereDate('event_date', $date);

        if ($excludeEventId !== null) {
            $query->where('event_id', '!=', $excludeEventId);
        }
        if ($excludeOrgId !== null) {
            $query->where('org_id', '!=', $excludeOrgId);
        }

        $events = $query->get();
        $names = [];
        foreach ($events as $event) {
            $name = $event->organization?->org_name ?? 'Unknown';
            if (! in_array($name, $names, true)) {
                $names[] = $name;
            }
        }
        return $names;
    }
}

