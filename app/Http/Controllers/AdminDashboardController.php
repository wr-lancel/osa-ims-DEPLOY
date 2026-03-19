<?php

namespace App\Http\Controllers;

use App\Models\AcademicCalendar;
use App\Models\Course;
use App\Models\CandidacyApplication;
use App\Models\Complaint;
use App\Models\Discipline;
use App\Models\Event;
use App\Models\EnrolledStudent;
use App\Models\GuidanceAppointment;
use App\Models\GuidanceCase;
use App\Models\RiskPrediction;
use App\Models\Student;
use App\Models\SportsBorrowing;
use App\Models\StudentOrganization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminDashboardController extends Controller
{
    private const CACHE_TTL_SECONDS = 120; // 2 minutes

    /**
     * Display the admin dashboard.
     */
    public function index(): Response
    {
        $activeCalendar = AcademicCalendar::active()->first();
        $cacheKey = 'dashboard.stats.' . ($activeCalendar ? $activeCalendar->calendar_id : 'none');

        $data = Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($activeCalendar) {
            return $this->computeDashboardData($activeCalendar);
        });

        return Inertia::render('Admin/Dashboard', $data);
    }

    /**
     * Compute all dashboard data (stats, upcoming events, term label, comparison).
     */
    private function computeDashboardData(?AcademicCalendar $activeCalendar): array
    {
        $calendarId = $activeCalendar?->calendar_id;
        $termStart = $activeCalendar?->start_date?->toDateString();
        $termEnd = $activeCalendar?->end_date?->toDateString();

        // Enrolled students (this term)
        $totalStudents = $calendarId
            ? EnrolledStudent::where('enrollment_status', 'enrolled')->where('acad_id', $calendarId)->count()
            : 0;

        // Discipline: total this term (via enrollment), active/pending (all)
        $disciplineTotalTerm = $calendarId
            ? Discipline::whereHas('enrollment', fn($q) => $q->where('acad_id', $calendarId))->count()
            : 0;
        $activeDisciplineCases = Discipline::whereIn('status', ['pending', 'under investigation'])->count();

        // Complaints: total this term (complainant enrollment), pending (all)
        $complaintsTotalTerm = $calendarId
            ? Complaint::whereHas('complainantEnrollment', fn($q) => $q->where('acad_id', $calendarId))->count()
            : 0;
        $pendingComplaints = Complaint::where('status', 'pending')->count();

        // Guidance: cases this term (enrollment), pending appointments (term-aware via student enrollment)
        $guidanceCasesTerm = $calendarId
            ? GuidanceCase::whereHas('enrollment', fn($q) => $q->where('acad_id', $calendarId))->count()
            : 0;
        $pendingAppointments = $calendarId
            ? GuidanceAppointment::where('status', 'pending')
                ->whereHas('enrollments', fn($q) => $q->where('acad_id', $calendarId))
                ->count()
            : GuidanceAppointment::where('status', 'pending')->count();

        // Events: this term (by event_date in range), this month, upcoming count
        $eventsThisTerm = ($termStart && $termEnd)
            ? Event::whereBetween('event_date', [$termStart, $termEnd])->count()
            : 0;
        $eventsThisMonth = Event::whereMonth('event_date', now()->month)->whereYear('event_date', now()->year)->count();
        $upcomingEventsCount = Event::where('event_date', '>=', now()->toDateString())
            ->whereIn('status', ['Upcoming', 'Planning'])
            ->count();

        // Organizations, sports, candidacies
        $totalOrganizations = StudentOrganization::where('status', 'active')->count();
        $pendingBorrowings = SportsBorrowing::where('status', 'pending')->count();
        $pendingCandidacies = $calendarId
            ? CandidacyApplication::where('acad_id', $calendarId)->where('status', 'pending')->count()
            : CandidacyApplication::where('status', 'pending')->count();

        // Upcoming events: exactly 5
        $upcomingEvents = Event::with(['organization'])
            ->where('event_date', '>=', now()->toDateString())
            ->where('event_date', '<=', now()->addDays(30)->toDateString())
            ->whereIn('status', ['Upcoming', 'Planning'])
            ->orderBy('event_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($event) {
                return [
                    'event_id' => $event->event_id,
                    'event_name' => $event->event_name,
                    'organization_name' => $event->organization->org_name ?? 'General',
                    'event_date' => $event->event_date->format('Y-m-d'),
                    'event_date_display' => $event->event_date->format('M d, Y'),
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'venue' => $event->venue,
                    'status' => $event->status,
                    'days_until' => now()->startOfDay()->diffInDays($event->event_date, false),
                ];
            });

        // Academic term label
        $academicTermLabel = $activeCalendar ? $activeCalendar->display_label : null;

        // Previous term for comparison (calendar that ended before current start)
        $previousCalendar = $activeCalendar
            ? AcademicCalendar::where('start_date', '<', $activeCalendar->start_date)
                ->orderBy('start_date', 'desc')
                ->first()
            : null;

        $previousCalendarId = $previousCalendar?->calendar_id;
        $comparison = null;
        if ($previousCalendarId && $calendarId) {
            $prevEnrolled = EnrolledStudent::where('enrollment_status', 'enrolled')->where('acad_id', $previousCalendarId)->count();
            $prevDiscipline = Discipline::whereHas('enrollment', fn($q) => $q->where('acad_id', $previousCalendarId))->count();
            $prevComplaints = Complaint::whereHas('complainantEnrollment', fn($q) => $q->where('acad_id', $previousCalendarId))->count();
            $prevGuidance = GuidanceCase::whereHas('enrollment', fn($q) => $q->where('acad_id', $previousCalendarId))->count();
            $prevStart = $previousCalendar->start_date->toDateString();
            $prevEnd = $previousCalendar->end_date->toDateString();
            $prevEvents = Event::whereBetween('event_date', [$prevStart, $prevEnd])->count();

            $comparison = [
                'previous_term_label' => $previousCalendar->display_label,
                'students' => ['current' => $totalStudents, 'previous' => $prevEnrolled],
                'discipline' => ['current' => $disciplineTotalTerm, 'previous' => $prevDiscipline],
                'complaints' => ['current' => $complaintsTotalTerm, 'previous' => $prevComplaints],
                'guidance_cases' => ['current' => $guidanceCasesTerm, 'previous' => $prevGuidance],
                'events' => ['current' => $eventsThisTerm, 'previous' => $prevEvents],
            ];
        }

        $stats = [
            'total_students' => $totalStudents,
            'total_organizations' => $totalOrganizations,
            'upcoming_events' => $upcomingEventsCount,
            'events_this_month' => $eventsThisMonth,
            'events_this_term' => $eventsThisTerm,
            'active_discipline_cases' => $activeDisciplineCases,
            'discipline_total_term' => $disciplineTotalTerm,
            'pending_appointments' => $pendingAppointments,
            'guidance_cases_term' => $guidanceCasesTerm,
            'pending_borrowings' => $pendingBorrowings,
            'pending_complaints' => $pendingComplaints,
            'complaints_total_term' => $complaintsTotalTerm,
            'pending_candidacies' => $pendingCandidacies,
        ];

        // Chart: term summary (bar)
        $chartTermSummary = [
            'labels' => ['Enrolled', 'Discipline', 'Complaints', 'Guidance', 'Events', 'Orgs', 'Candidacies'],
            'values' => [
                $totalStudents,
                $disciplineTotalTerm,
                $complaintsTotalTerm,
                $guidanceCasesTerm,
                $eventsThisTerm,
                $totalOrganizations,
                $pendingCandidacies,
            ],
        ];

        // Chart: comparison (grouped bar) – structure for frontend
        $chartComparison = null;
        if ($comparison) {
            $chartComparison = [
                'labels' => ['Students', 'Discipline', 'Complaints', 'Guidance', 'Events'],
                'currentValues' => [
                    $comparison['students']['current'],
                    $comparison['discipline']['current'],
                    $comparison['complaints']['current'],
                    $comparison['guidance_cases']['current'],
                    $comparison['events']['current'],
                ],
                'previousValues' => [
                    $comparison['students']['previous'],
                    $comparison['discipline']['previous'],
                    $comparison['complaints']['previous'],
                    $comparison['guidance_cases']['previous'],
                    $comparison['events']['previous'],
                ],
                'previousTermLabel' => $comparison['previous_term_label'],
            ];
        }

        // Chart: events by month (this term)
        $chartEventsByMonth = ['labels' => [], 'values' => []];
        if ($termStart && $termEnd) {
            $start = \Carbon\Carbon::parse($termStart)->startOfMonth();
            $end = \Carbon\Carbon::parse($termEnd)->endOfMonth();
            $monthKeys = [];
            $current = $start->copy();
            while ($current->lte($end)) {
                $monthKeys[] = $current->format('Y-m');
                $current->addMonth();
            }
            $counts = Event::whereBetween('event_date', [$termStart, $termEnd])
                ->selectRaw('YEAR(event_date) as year, MONTH(event_date) as month, COUNT(*) as count')
                ->groupBy('year', 'month')
                ->get()
                ->keyBy(fn($r) => $r->year . '-' . str_pad($r->month, 2, '0', STR_PAD_LEFT));
            $chartEventsByMonth['labels'] = array_map(fn($ym) => \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('M'), $monthKeys);
            $chartEventsByMonth['values'] = array_map(fn($ym) => ($counts->get($ym))->count ?? 0, $monthKeys);
        }

        // Chart: discipline by type (this term)
        $chartDisciplineByType = ['labels' => [], 'values' => []];
        if ($calendarId) {
            $byType = Discipline::whereHas('enrollment', fn($q) => $q->where('acad_id', $calendarId))
                ->get(['violation_type'])
                ->groupBy(fn($r) => trim((string) $r->violation_type) !== '' ? $r->violation_type : 'Unspecified')
                ->map->count();
            $chartDisciplineByType['labels'] = $byType->keys()->toArray();
            $chartDisciplineByType['values'] = $byType->values()->toArray();
        }

        // Chart: complaints by category (this term)
        $chartComplaintsByCategory = ['labels' => [], 'values' => []];
        if ($calendarId) {
            $byCategory = Complaint::whereHas('complainantEnrollment', fn($q) => $q->where('acad_id', $calendarId))
                ->get(['category'])
                ->groupBy(fn($r) => trim((string) $r->category) !== '' ? $r->category : 'Unspecified')
                ->map->count();
            $chartComplaintsByCategory['labels'] = $byCategory->keys()->toArray();
            $chartComplaintsByCategory['values'] = $byCategory->values()->toArray();
        }

        // Chart: most course with violations (top N, this term)
        $chartDisciplineByCourse = ['labels' => [], 'values' => []];
        if ($calendarId) {
            $byCourse = Discipline::whereHas('enrollment', fn($q) => $q->where('acad_id', $calendarId))
                ->with('enrollment.course')
                ->get()
                ->groupBy(fn($d) => $d->enrollment?->course_id ?? 0)
                ->map->count()
                ->sortDesc();
            $courseIds = $byCourse->keys()->filter(fn($id) => $id !== 0)->values()->all();
            $courses = Course::whereIn('course_id', $courseIds)->get()->keyBy('course_id');
            $chartDisciplineByCourse['labels'] = $byCourse->keys()->map(fn($id) => $id === 0 ? 'No course' : ($courses->get($id)?->course_code ?? $courses->get($id)?->course_name ?? 'Unknown'))->toArray();
            $chartDisciplineByCourse['values'] = $byCourse->values()->toArray();
        }

        // Chart: most guidance cases by course (top N, this term)
        $chartGuidanceByCourse = ['labels' => [], 'values' => []];
        if ($calendarId) {
            $byCourse = GuidanceCase::whereHas('enrollment', fn($q) => $q->where('acad_id', $calendarId))
                ->with('enrollment.course')
                ->get()
                ->groupBy(fn($g) => $g->enrollment?->course_id ?? 0)
                ->map->count()
                ->sortDesc();
            $courseIds = $byCourse->keys()->filter(fn($id) => $id !== 0)->values()->all();
            $courses = Course::whereIn('course_id', $courseIds)->get()->keyBy('course_id');
            $chartGuidanceByCourse['labels'] = $byCourse->keys()->map(fn($id) => $id === 0 ? 'No course' : ($courses->get($id)?->course_code ?? $courses->get($id)?->course_name ?? 'Unknown'))->toArray();
            $chartGuidanceByCourse['values'] = $byCourse->values()->toArray();
        }

        // Chart: discipline by severity (this term)
        $chartDisciplineBySeverity = ['labels' => [], 'values' => []];
        if ($calendarId) {
            $bySeverity = Discipline::whereHas('enrollment', fn($q) => $q->where('acad_id', $calendarId))
                ->get(['severity'])
                ->groupBy(fn($r) => trim((string) $r->severity) !== '' ? $r->severity : 'Unspecified')
                ->map->count();
            $chartDisciplineBySeverity['labels'] = $bySeverity->keys()->toArray();
            $chartDisciplineBySeverity['values'] = $bySeverity->values()->toArray();
        }

        // Chart: enrollment by year level (this term)
        $chartEnrollmentByYearLevel = ['labels' => [], 'values' => []];
        if ($calendarId) {
            $byYear = EnrolledStudent::where('acad_id', $calendarId)
                ->where('enrollment_status', 'enrolled')
                ->get(['year_level'])
                ->groupBy(fn($r) => $r->year_level !== null && $r->year_level !== '' ? $r->year_level : 'Unspecified')
                ->map->count();
            $yearLabels = [
                1 => '1st Year',
                2 => '2nd Year',
                3 => '3rd Year',
                4 => '4th Year',
                5 => '5th Year',
            ];
            $chartEnrollmentByYearLevel['labels'] = $byYear->keys()->map(fn($y) => $yearLabels[(int) $y] ?? (string) $y)->toArray();
            $chartEnrollmentByYearLevel['values'] = $byYear->values()->toArray();
        }

        // Chart: Enrollment Per Semester Trend
        $chartEnrollmentPerSemester = ['labels' => [], 'values' => []];
        if ($calendarId) {
            $allCalendars = AcademicCalendar::where('start_date', '<=', $termStart)->orderBy('start_date', 'asc')->take(10)->get();
            foreach ($allCalendars as $cal) {
                $count = EnrolledStudent::where('enrollment_status', 'enrolled')->where('acad_id', $cal->calendar_id)->count();
                if ($count > 0 || $cal->calendar_id == $calendarId) {
                    $yearShort = str_replace('AY 20', '', $cal->academic_year);
                    $yearShort = str_replace('-20', '-', $yearShort);
                    $semStr = '';
                    if (str_contains($cal->semester, '1st'))
                        $semStr = '1st Sem';
                    elseif (str_contains($cal->semester, '2nd'))
                        $semStr = '2nd Sem';
                    elseif (str_contains($cal->semester, 'Midyear'))
                        $semStr = 'Midyear';
                    else
                        $semStr = $cal->semester;

                    $chartEnrollmentPerSemester['labels'][] = trim($yearShort . ' ' . $semStr);
                    $chartEnrollmentPerSemester['values'][] = $count;
                }
            }
        }

        // Chart: events by organization (top N, this term)
        $chartEventsByOrganization = ['labels' => [], 'values' => []];
        if ($termStart && $termEnd) {
            $byOrg = Event::whereBetween('event_date', [$termStart, $termEnd])
                ->with('organization')
                ->get()
                ->groupBy('org_id')
                ->map->count()
                ->sortDesc();
            $orgIds = $byOrg->keys()->filter()->values()->all();
            $orgs = StudentOrganization::whereIn('org_id', $orgIds)->get()->keyBy('org_id');
            $chartEventsByOrganization['labels'] = $byOrg->keys()->map(fn($id) => !$id ? 'General' : ($orgs->get($id)?->org_name ?? 'Unknown'))->toArray();
            $chartEventsByOrganization['values'] = $byOrg->values()->toArray();
        }

        // Chart: number of violations per month this semester (line chart)
        $chartViolationsPerMonth = ['labels' => [], 'values' => []];
        if ($calendarId && $termStart && $termEnd) {
            $start = \Carbon\Carbon::parse($termStart)->startOfMonth();
            $end = \Carbon\Carbon::parse($termEnd)->endOfMonth();
            $monthKeys = [];
            $current = $start->copy();
            while ($current->lte($end)) {
                $monthKeys[] = $current->format('Y-m');
                $current->addMonth();
            }
            $counts = Discipline::whereHas('enrollment', fn($q) => $q->where('acad_id', $calendarId))
                ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as count')
                ->groupBy('year', 'month')
                ->get()
                ->keyBy(fn($r) => $r->year . '-' . str_pad($r->month, 2, '0', STR_PAD_LEFT));
            $chartViolationsPerMonth['labels'] = array_map(fn($ym) => \Carbon\Carbon::createFromFormat('Y-m', $ym)->format('M'), $monthKeys);
            $chartViolationsPerMonth['values'] = array_map(fn($ym) => ($counts->get($ym))->count ?? 0, $monthKeys);
        }

        // ── Predictive Analytics ──────────────────────────────────────────
        $totalStudents       = Student::count();
        $riskHighCount       = RiskPrediction::where('risk_level', 'High')->count();
        $riskModerateCount   = RiskPrediction::where('risk_level', 'Moderate')->count();
        $riskLowCount        = RiskPrediction::where('risk_level', 'Low')->count();
        $riskComputedCount   = RiskPrediction::count();
        $riskNotComputed     = max(0, $totalStudents - $riskComputedCount);

        $riskSummary = [
            'high'         => $riskHighCount,
            'moderate'     => $riskModerateCount,
            'low'          => $riskLowCount,
            'not_computed' => $riskNotComputed,
            'total'        => $totalStudents,
        ];

        // Doughnut: risk level distribution
        $chartRiskLevelDistribution = [
            'labels' => ['High', 'Moderate', 'Low'],
            'values' => [$riskHighCount, $riskModerateCount, $riskLowCount],
        ];

        // Bar: High-risk student count by course
        $riskByCourseRows = DB::table('risk_prediction')
            ->join('students', 'risk_prediction.student_number', '=', 'students.student_number')
            ->where('risk_prediction.risk_level', 'High')
            ->select('students.course', DB::raw('COUNT(*) as count'))
            ->whereNotNull('students.course')
            ->groupBy('students.course')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $chartRiskByCourse = [
            'labels' => $riskByCourseRows->pluck('course')->toArray(),
            'values' => $riskByCourseRows->pluck('count')->toArray(),
        ];

        // Top 5 highest-risk students
        $topAtRiskStudents = DB::table('risk_prediction')
            ->join('students', 'risk_prediction.student_number', '=', 'students.student_number')
            ->where('risk_prediction.risk_level', 'High')
            ->select(
                'students.student_number',
                'students.first_name',
                'students.last_name',
                'students.course',
                'students.year_level',
                'risk_prediction.risk_score',
                'risk_prediction.risk_level',
            )
            ->orderByDesc('risk_prediction.risk_score')
            ->limit(5)
            ->get()
            ->map(fn($s) => [
                'student_number' => $s->student_number,
                'student_name'   => "{$s->last_name}, {$s->first_name}",
                'course'         => $s->course,
                'year_level'     => $s->year_level,
                'risk_score'     => $s->risk_score,
                'risk_level'     => $s->risk_level,
            ])
            ->toArray();

        return [
            'upcomingEvents' => $upcomingEvents,
            'stats' => $stats,
            'academicTermLabel' => $academicTermLabel,
            'comparison' => $comparison,
            'chartTermSummary' => $chartTermSummary,
            'chartComparison' => $chartComparison,
            'chartEventsByMonth' => $chartEventsByMonth,
            'chartDisciplineByType' => $chartDisciplineByType,
            'chartComplaintsByCategory' => $chartComplaintsByCategory,
            'chartDisciplineByCourse' => $chartDisciplineByCourse,
            'chartGuidanceByCourse' => $chartGuidanceByCourse,
            'chartDisciplineBySeverity' => $chartDisciplineBySeverity,
            'chartEnrollmentByYearLevel' => $chartEnrollmentByYearLevel,
            'chartEnrollmentPerSemester' => $chartEnrollmentPerSemester,
            'chartEventsByOrganization' => $chartEventsByOrganization,
            'chartViolationsPerMonth' => $chartViolationsPerMonth,
            'riskSummary'                  => $riskSummary,
            'chartRiskLevelDistribution'   => $chartRiskLevelDistribution,
            'chartRiskByCourse'            => $chartRiskByCourse,
            'topAtRiskStudents'            => $topAtRiskStudents,
        ];
    }

    /**
     * Term/semester summary report page.
     */
    public function termSummaryReport(Request $request): Response
    {
        $calendars = AcademicCalendar::orderBy('start_date', 'desc')->get(['calendar_id', 'academic_year', 'semester', 'start_date', 'end_date', 'status']);
        $calendarId = $request->input('calendar_id');
        $calendar = $calendarId
            ? AcademicCalendar::find($calendarId)
            : AcademicCalendar::active()->first();

        $summary = $this->computeTermSummaryForCalendar($calendar);

        return Inertia::render('Admin/Reports/TermSummary', [
            'calendars' => $calendars->map(fn($c) => [
                'calendar_id' => $c->calendar_id,
                'label' => $c->display_label,
                'start_date' => $c->start_date?->format('Y-m-d'),
                'end_date' => $c->end_date?->format('Y-m-d'),
                'status' => $c->status,
            ]),
            'selectedCalendarId' => $calendar?->calendar_id,
            'summary' => $summary,
        ]);
    }

    /**
     * Term summary as PDF download.
     */
    public function termSummaryPdf(Request $request)
    {
        $calendarId = $request->input('calendar_id');
        $calendar = $calendarId
            ? AcademicCalendar::find($calendarId)
            : AcademicCalendar::active()->first();
        $summary = $this->computeTermSummaryForCalendar($calendar);

        $pdf = Pdf::loadView('reports.term-summary-pdf', ['summary' => $summary]);
        $filename = 'term-summary-' . ($summary['term_label'] ?? 'report') . '.pdf';
        $filename = preg_replace('/[^a-z0-9\-_\.]/i', '-', $filename);

        return $pdf->download($filename);
    }

    /**
     * Compute term summary stats for a given calendar (for report/PDF).
     */
    private function computeTermSummaryForCalendar(?AcademicCalendar $calendar): array
    {
        $calendarId = $calendar?->calendar_id;
        $termStart = $calendar?->start_date?->toDateString();
        $termEnd = $calendar?->end_date?->toDateString();

        $totalStudents = $calendarId
            ? EnrolledStudent::where('enrollment_status', 'enrolled')->where('acad_id', $calendarId)->count()
            : 0;
        $disciplineTotal = $calendarId
            ? Discipline::whereHas('enrollment', fn($q) => $q->where('acad_id', $calendarId))->count()
            : 0;
        $complaintsTotal = $calendarId
            ? Complaint::whereHas('complainantEnrollment', fn($q) => $q->where('acad_id', $calendarId))->count()
            : 0;
        $guidanceCasesTotal = $calendarId
            ? GuidanceCase::whereHas('enrollment', fn($q) => $q->where('acad_id', $calendarId))->count()
            : 0;
        $eventsTotal = ($termStart && $termEnd)
            ? Event::whereBetween('event_date', [$termStart, $termEnd])->count()
            : 0;
        $activeOrgs = StudentOrganization::where('status', 'active')->count();
        $pendingCandidacies = $calendarId
            ? CandidacyApplication::where('acad_id', $calendarId)->where('status', 'pending')->count()
            : CandidacyApplication::where('status', 'pending')->count();

        return [
            'term_label' => $calendar ? $calendar->display_label : 'No term selected',
            'total_students' => $totalStudents,
            'discipline_total' => $disciplineTotal,
            'complaints_total' => $complaintsTotal,
            'guidance_cases_total' => $guidanceCasesTotal,
            'events_total' => $eventsTotal,
            'active_organizations' => $activeOrgs,
            'pending_candidacies' => $pendingCandidacies,
            'generated_at' => now()->format('M j, Y g:i A'),
        ];
    }
}
