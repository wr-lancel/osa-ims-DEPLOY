<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $primaryKey = 'course_id';
    public $incrementing = true;

    protected $fillable = [
        'course_code',
        'course_name',
        'description',
    ];

    /**
     * Get the sections for this course.
     */
    public function sections()
    {
        return $this->hasMany(Section::class, 'course_id', 'course_id');
    }

    /**
     * Get the enrolled students for this course.
     */
    public function enrolledStudents()
    {
        return $this->hasMany(EnrolledStudent::class, 'course_id', 'course_id');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'course_id';
    }
}

