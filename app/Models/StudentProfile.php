<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    use HasFactory;

    protected $primaryKey = 'profile_id';
    public $incrementing = true;

    protected $fillable = [
        'student_number',
        'birth_place',
        'gender',
        'citizenship',
        'civil_status',
        'spouse_name',
        'is_single_parent',
        'has_disability',
        'disability_details',
        'is_employed',
        'company_name',
        'profile_status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'is_single_parent' => 'boolean',
        'has_disability' => 'boolean',
        'is_employed' => 'boolean',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /**
     * Get the student that owns this profile.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }

    /**
     * Get the user who reviewed this profile.
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by', 'user_id');
    }
}

