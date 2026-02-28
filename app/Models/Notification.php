<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';
    protected $primaryKey = 'notification_id';
    public $incrementing = true;

    public $timestamps = false;
    const CREATED_AT = 'created_at';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'related_case_id',
        'related_meeting_id',
        'related_complaint_id',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Get the recipient user.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Get the related discipline case.
     */
    public function relatedCase()
    {
        return $this->belongsTo(Discipline::class, 'related_case_id', 'discipline_id');
    }

    /**
     * Get the related meeting.
     */
    public function relatedMeeting()
    {
        return $this->belongsTo(DisciplineMeeting::class, 'related_meeting_id', 'meeting_id');
    }

    /**
     * Get the related complaint.
     */
    public function relatedComplaint()
    {
        return $this->belongsTo(Complaint::class, 'related_complaint_id', 'complaint_id');
    }
}
