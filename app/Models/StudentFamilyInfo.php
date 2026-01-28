<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentFamilyInfo extends Model
{
    use HasFactory;

    protected $table = 'student_family_info';

    protected $fillable = [
        'student_number',
        'father_last_name',
        'father_first_name',
        'father_middle_initial',
        'father_occupation',
        'mother_maiden_last_name',
        'mother_first_name',
        'mother_middle_initial',
        'mother_occupation',
    ];

    /**
     * Get the student that owns this family info.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }

    /**
     * Get the father's full name.
     */
    public function getFatherFullNameAttribute(): string
    {
        $name = '';
        if ($this->father_last_name) {
            $name = $this->father_last_name;
        }
        if ($this->father_first_name) {
            $name .= ($name ? ', ' : '') . $this->father_first_name;
        }
        if ($this->father_middle_initial) {
            $name .= ' ' . $this->father_middle_initial . '.';
        }
        return $name ?: 'N/A';
    }

    /**
     * Get the mother's full name.
     */
    public function getMotherFullNameAttribute(): string
    {
        $name = '';
        if ($this->mother_maiden_last_name) {
            $name = $this->mother_maiden_last_name;
        }
        if ($this->mother_first_name) {
            $name .= ($name ? ', ' : '') . $this->mother_first_name;
        }
        if ($this->mother_middle_initial) {
            $name .= ' ' . $this->mother_middle_initial . '.';
        }
        return $name ?: 'N/A';
    }
}

