<?php

namespace App\Observers;

use App\Models\Discipline;
use App\Models\ViolationSummary;


class DisciplineObserver
{
    /**
     * Recalculate violation summary when a discipline record is saved.
     */
    public function saved(Discipline $discipline): void
    {
        $this->recalculate($discipline);
    }

    /**
     * Recalculate violation summary when a discipline record is deleted.
     */
    public function deleted(Discipline $discipline): void
    {
        $this->recalculate($discipline);
    }

    /**
     * Recalculate the violation_summary row for the student + academic year.
     */
    private function recalculate(Discipline $discipline): void
    {
        $studentNumber = $discipline->student_number;
        if (!$studentNumber) {
            return;
        }

        $violationDate = $discipline->violation_date;
        if (!$violationDate) {
            return;
        }

        // Academic year runs Aug 1 – Jul 31
        $year  = (int) $violationDate->format('Y');
        $month = (int) $violationDate->format('n');

        if ($month >= 8) {
            $academicYear = "{$year}-" . ($year + 1);
            $periodStart  = "{$year}-08-01";
            $periodEnd    = ($year + 1) . "-07-31";
        } else {
            $academicYear = ($year - 1) . "-{$year}";
            $periodStart  = ($year - 1) . "-08-01";
            $periodEnd    = "{$year}-07-31";
        }

        $violations = Discipline::where('student_number', $studentNumber)
            ->whereNull('voided_at')
            ->whereBetween('violation_date', [$periodStart, $periodEnd])
            ->get();

        $total    = $violations->count();
        $minor    = $violations->where('severity', 'Minor')->count();
        $moderate = $violations->where('severity', 'Moderate')->count();
        $major    = $violations->where('severity', 'Major')->count();

        ViolationSummary::updateOrCreate(
            [
                'student_number' => $studentNumber,
                'academic_year'  => $academicYear,
            ],
            [
                'total_violations' => $total,
                'minor_violations' => $minor,
                'major_violations' => $major,
                'status'           => $total === 0 ? null : ($major > 0 ? 'at_risk' : ($moderate > 0 ? 'flagged' : 'minor')),
            ]
        );
    }
}
