<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuidanceCaseAction extends Model
{
    use HasFactory;

    protected $primaryKey = 'action_id';
    public $incrementing = true;

    protected $fillable = [
        'guidance_case_id',
        'action_by_user_id',
        'note',
        'action_status',
        'action_at',
    ];

    public $timestamps = false;
    const CREATED_AT = 'created_at';

    protected $casts = [
        'action_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Get the guidance case that this action belongs to.
     */
    public function guidanceCase()
    {
        return $this->belongsTo(GuidanceCase::class, 'guidance_case_id', 'guidance_case_id');
    }

    /**
     * Get the user who performed this action.
     */
    public function actionByUser()
    {
        return $this->belongsTo(User::class, 'action_by_user_id', 'user_id');
    }
}
