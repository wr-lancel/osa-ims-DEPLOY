<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComplaintHistory extends Model
{
    use HasFactory;

    protected $table = 'complaint_history';
    protected $primaryKey = 'history_id';
    public $incrementing = true;

    public $timestamps = false;
    const CREATED_AT = 'created_at';

    protected $fillable = [
        'complaint_id',
        'changed_by_user_id',
        'old_status',
        'new_status',
        'remarks',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the complaint.
     */
    public function complaint()
    {
        return $this->belongsTo(Complaint::class, 'complaint_id', 'complaint_id');
    }

    /**
     * Get the user who made the change.
     */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id', 'user_id');
    }
}
