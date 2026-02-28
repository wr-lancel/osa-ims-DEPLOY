<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisciplineMeeting extends Model
{
    use HasFactory;

    protected $table = 'discipline_meetings';
    protected $primaryKey = 'meeting_id';
    public $incrementing = true;

    protected $fillable = [
        'case_id',
        'meeting_date',
        'meeting_time',
        'location',
        'purpose_notes',
        'status',
        'created_by_user_id',
    ];

    protected $casts = [
        'meeting_date' => 'date',
    ];

    /**
     * Get the discipline case.
     */
    public function discipline()
    {
        return $this->belongsTo(Discipline::class, 'case_id', 'discipline_id');
    }

    /**
     * Get the user who created the meeting.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id', 'user_id');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'meeting_id';
    }
}
