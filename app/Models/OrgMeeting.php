<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrgMeeting extends Model
{
    use HasFactory;

    protected $table = 'org_meetings';
    protected $primaryKey = 'meeting_id';
    public $incrementing = true;

    protected $fillable = [
        'org_id',
        'title',
        'description',
        'meeting_date',
        'start_time',
        'end_time',
        'venue',
        'target_audience',
        'called_by',
        'status',
    ];

    protected $casts = [
        'meeting_date' => 'date',
    ];

    /**
     * Get the organization this meeting belongs to.
     */
    public function organization()
    {
        return $this->belongsTo(StudentOrganization::class, 'org_id', 'org_id');
    }

    /**
     * Get the user who called this meeting.
     */
    public function caller()
    {
        return $this->belongsTo(User::class, 'called_by', 'user_id');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'meeting_id';
    }
}
