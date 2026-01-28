<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicCalendar extends Model
{
    use HasFactory;

    protected $table = 'academic_calendar';
    protected $primaryKey = 'calendar_id';
    public $incrementing = true;

    protected $fillable = [
        'academic_year',
        'semester',
        'start_date',
        'end_date',
        'status',
    ];

    /**
     * Scope to get the active academic calendar.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the display label for the academic calendar.
     */
    public function getDisplayLabelAttribute(): string
    {
        $label = $this->academic_year;
        if ($this->semester) {
            $label .= ' - ' . $this->semester;
        }
        return $label;
    }

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the enrolled students for this academic calendar.
     */
    public function enrolledStudents()
    {
        return $this->hasMany(EnrolledStudent::class, 'acad_id', 'calendar_id');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'calendar_id';
    }
}

