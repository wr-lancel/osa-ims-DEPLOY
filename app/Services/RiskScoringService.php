<?php

namespace App\Services;

use App\Models\RiskPrediction;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class RiskScoringService
{
    /**
     * Compute risk score for a single student.
     *
     * Formula:
     *   violation_sub_score = min(100, minor*10 + moderate*25 + major*40)
     *   guidance_sub_score  = min(100, referral*15 + other*5)
     *   risk_score = violation_sub_score * 0.70 + guidance_sub_score * 0.30
     */
    public function computeScore(Student $student): array
    {
        // --- Factor 1: Violation history ---
        $violations = DB::table('discipline')
            ->where('student_number', $student->student_number)
            ->whereNull('voided_at')
            ->selectRaw("
                COUNT(CASE WHEN severity = 'Minor'    THEN 1 END) as minor_count,
                COUNT(CASE WHEN severity = 'Moderate' THEN 1 END) as moderate_count,
                COUNT(CASE WHEN severity = 'Major'    THEN 1 END) as major_count
            ")
            ->first();

        $minorCount    = (int) ($violations->minor_count    ?? 0);
        $moderateCount = (int) ($violations->moderate_count ?? 0);
        $majorCount    = (int) ($violations->major_count    ?? 0);

        $violationSubScore = min(100, ($minorCount * 10) + ($moderateCount * 25) + ($majorCount * 40));

        // --- Factor 2: Guidance incidents ---
        $guidanceCasesData = DB::table('guidance_cases')
            ->join('enrolled_students', 'guidance_cases.enrollment_id', '=', 'enrolled_students.enrollment_id')
            ->where('enrolled_students.student_number', $student->student_number)
            ->selectRaw("
                COUNT(CASE WHEN guidance_cases.case_type = 'referral'    THEN 1 END) as referral_count,
                COUNT(CASE WHEN guidance_cases.case_type != 'referral'   THEN 1 END) as other_count
            ")
            ->first();

        $referralCount = (int) ($guidanceCasesData->referral_count ?? 0);
        $otherCount    = (int) ($guidanceCasesData->other_count    ?? 0);

        $guidanceSubScore = min(100, ($referralCount * 15) + ($otherCount * 5));

        // --- Final weighted score ---
        $riskScore = round(($violationSubScore * 0.70) + ($guidanceSubScore * 0.30), 2);

        $riskLevel = match (true) {
            $riskScore >= 67 => 'High',
            $riskScore >= 34 => 'Moderate',
            default          => 'Low',
        };

        $factors = [
            'violation_history' => [
                'minor'     => $minorCount,
                'moderate'  => $moderateCount,
                'major'     => $majorCount,
                'sub_score' => $violationSubScore,
                'weight'    => 0.70,
            ],
            'guidance_incidents' => [
                'referral'  => $referralCount,
                'other'     => $otherCount,
                'sub_score' => $guidanceSubScore,
                'weight'    => 0.30,
            ],
        ];

        return [
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'factors'    => $factors,
        ];
    }

    /**
     * Compute and persist risk score for a single student.
     */
    public function computeAndSave(Student $student): RiskPrediction
    {
        $result = $this->computeScore($student);

        return RiskPrediction::updateOrCreate(
            ['student_number' => $student->student_number],
            [
                'risk_score'      => $result['risk_score'],
                'risk_level'      => $result['risk_level'],
                'factors'         => $result['factors'],
                'prediction_date' => now()->toDateString(),
            ]
        );
    }

    /**
     * Compute and persist risk scores for all students.
     */
    public function computeAll(): int
    {
        $students = Student::all();
        foreach ($students as $student) {
            $this->computeAndSave($student);
        }
        return $students->count();
    }
}
