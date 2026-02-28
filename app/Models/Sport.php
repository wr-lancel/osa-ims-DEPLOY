<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sport extends Model
{
    use HasFactory;

    protected $primaryKey = 'sport_id';
    public $incrementing = true;

    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    /**
     * Get the athletes for this sport.
     */
    public function athletes()
    {
        return $this->hasMany(SportAthlete::class, 'sport_id', 'sport_id');
    }

    /**
     * Get the students for this sport through the pivot.
     */
    public function students()
    {
        return $this->belongsToMany(Student::class, 'sport_athletes', 'sport_id', 'student_number', 'sport_id', 'student_number');
    }

    /**
     * Scope: active sports only.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'sport_id';
    }
}
