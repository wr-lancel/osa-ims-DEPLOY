<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrgAdviser extends Model
{
    use HasFactory;

    protected $table = 'org_advisers';
    protected $primaryKey = 'adviser_id';
    public $incrementing = true;

    protected $fillable = [
        'org_id',
        'employee_id',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the organization that this adviser belongs to.
     */
    public function organization()
    {
        return $this->belongsTo(StudentOrganization::class, 'org_id', 'org_id');
    }

    /**
     * Get the employee that is this adviser.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }
}

