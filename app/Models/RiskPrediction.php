<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiskPrediction extends Model
{
    protected $table = 'risk_prediction';
    protected $primaryKey = 'prediction_id';

    protected $fillable = [
        'student_number',
        'risk_score',
        'risk_level',
        'factors',
        'prediction_date',
    ];

    protected $casts = [
        'risk_score' => 'float',
        'prediction_date' => 'date',
        'factors' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_number', 'student_number');
    }
}
