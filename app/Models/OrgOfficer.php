<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrgOfficer extends Model
{
    use HasFactory;

    protected $table = 'org_officers';
    protected $primaryKey = 'officer_id';
    public $incrementing = true;

    protected $fillable = [
        'org_id',
        'student_number',
        'position',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the organization that this officer belongs to.
     */
    public function organization()
    {
        return $this->belongsTo(StudentOrganization::class, 'org_id', 'org_id');
    }

    /**
     * Get the student that is this officer.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }
}

