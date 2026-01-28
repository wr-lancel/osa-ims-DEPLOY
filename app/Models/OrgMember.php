<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrgMember extends Model
{
    use HasFactory;

    protected $table = 'org_members';
    protected $primaryKey = 'member_id';
    public $incrementing = true;

    protected $fillable = [
        'org_id',
        'student_number',
        'join_date',
        'status',
    ];

    protected $casts = [
        'join_date' => 'date',
    ];

    /**
     * Get the organization that this member belongs to.
     */
    public function organization()
    {
        return $this->belongsTo(StudentOrganization::class, 'org_id', 'org_id');
    }

    /**
     * Get the student that is this member.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }
}

