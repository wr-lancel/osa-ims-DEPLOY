<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisciplineHistory extends Model
{
    use HasFactory;

    protected $table = 'discipline_history';
    protected $primaryKey = 'history_id';
    public $incrementing = true;

    public $timestamps = false;
    const CREATED_AT = 'created_at';

    protected $fillable = [
        'case_id',
        'changed_by_user_id',
        'old_status',
        'new_status',
        'note',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the discipline case.
     */
    public function discipline()
    {
        return $this->belongsTo(Discipline::class, 'case_id', 'discipline_id');
    }

    /**
     * Get the user who made the change.
     */
    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id', 'user_id');
    }
}
