<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'employee_id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'department',
        'position',
    ];

    /**
     * Get the user associated with this employee.
     */
    public function user()
    {
        return $this->hasOne(User::class, 'employee_id', 'employee_id');
    }

    /**
     * Get the employee's full name.
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Get the guidance cases assigned to this employee.
     */
    public function guidanceCases()
    {
        return $this->hasMany(GuidanceCase::class, 'assigned_staff_id', 'employee_id');
    }
}

