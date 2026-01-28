<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentEmergencyContact extends Model
{
    use HasFactory;

    protected $table = 'student_emergency_contacts';

    protected $fillable = [
        'student_number',
        'contact_name',
        'relationship',
        'contact_number',
        'contact_address',
    ];

    /**
     * Get the student that owns this emergency contact.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }
}

