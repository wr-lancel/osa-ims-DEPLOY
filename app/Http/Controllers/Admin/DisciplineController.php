<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDisciplineMeetingRequest;
use App\Http\Requests\Admin\StoreDisciplineRequest;
use App\Http\Requests\Admin\UpdateDisciplineMeetingRequest;
use App\Http\Requests\Admin\UpdateDisciplineRequest;
use App\Models\AcademicCalendar;
use App\Models\Discipline;
use App\Models\DisciplineHistory;
use App\Models\DisciplineMeeting;
use App\Models\DisciplineViolationType;
use App\Models\DisciplineWorkflowStep;
use App\Models\EnrolledStudent;
use App\Models\Notification;
use App\Models\RiskPrediction;
use App\Models\Student;
use App\Models\SystemSetting;
use App\Services\DisciplineService;
use App\Services\RiskScoringService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DisciplineController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected DisciplineService $disciplineService
    ) {
    }

    /**
     * Display a listing of violations.
     */
    public function index(Request $request): Response
    {
        $showVoided = $request->boolean('show_voided', false);

        $totalViolations = Discipline::whereNull('voided_at')->count();
        $pendingCases = Discipline::whereNull('voided_at')->where('status', 'pending')->count();
        $resolvedCases = Discipline::whereNull('voided_at')->where('status', 'resolved')->count();
        $majorCases = Discipline::whereNull('voided_at')->where('severity', 'Major')->count();
        $voidedCount = Discipline::whereNotNull('voided_at')->count();

        $query = $this->buildFilteredDisciplineQuery($request)
            ->with(['student', 'reportedBy', 'enrollment.academicCalendar', 'voidedBy']);

        $sortBy = in_array($request->input('sort_by'), ['violation_date', 'created_at', 'severity', 'status']) ? $request->input('sort_by') : 'violation_date';
        $sortDir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';

        $violations = $query->orderBy($sortBy, $sortDir)
            ->paginate($request->input('perPage', 20))
            ->withQueryString();

        $violations->getCollection()->transform(function ($violation) {
            return [
                'discipline_id' => $violation->discipline_id,
                'student_number' => $violation->student->student_number ?? '',
                'student_name' => $violation->student->full_name ?? '',
                'violation_date' => $violation->violation_date->format('Y-m-d'),
                'violation_type' => $violation->violation_type,
                'description' => $violation->description,
                'sanction' => $violation->sanction,
                'remarks' => $violation->remarks,
                'date_resolved' => $violation->date_resolved?->format('Y-m-d'),
                'severity' => $violation->severity,
                'status' => $violation->status,
                'reported_by' => $violation->reportedBy->email ?? null,
                'severity_color' => $violation->severity_color,
                'status_color' => $violation->status_color,
                'voided_at' => $violation->voided_at?->format('Y-m-d H:i'),
                'void_reason' => $violation->void_reason,
                'voided_by' => $violation->voidedBy->email ?? null,
            ];
        });

        $terms = AcademicCalendar::orderBy('start_date', 'desc')
            ->get()
            ->map(fn($c) => ['calendar_id' => $c->calendar_id, 'display_label' => $c->display_label]);

        $activeCalendar = AcademicCalendar::active()->first();

        $enrollments = EnrolledStudent::with(['student'])
            ->where('enrollment_status', 'enrolled')
            ->when($activeCalendar, fn($q) => $q->where('acad_id', $activeCalendar->calendar_id))
            ->orderBy('enrollment_id', 'desc')
            ->get()
            ->map(function ($e) {
                return [
                    'enrollment_id' => $e->enrollment_id,
                    'student_number' => $e->student_number,
                    'student_name' => $e->student->full_name ?? '—',
                    'display_label' => ($e->student->full_name ?? $e->student_number) . ' (' . $e->student_number . ')',
                ];
            });

        $workflowSteps = DisciplineWorkflowStep::getStepsForProgressBar();

        // --- Risk Assessment tab data ---
        $activeTab = $request->input('tab', 'violations');
        $riskStudents = null;
        $riskStats = null;
        $riskFilters = null;

        if ($activeTab === 'risk') {
            $riskSearch   = $request->input('risk_search', '');
            $riskLevel    = $request->input('risk_level', '');

            $riskQuery = Student::query()
                ->leftJoin('risk_prediction', 'students.student_number', '=', 'risk_prediction.student_number')
                ->select(
                    'students.student_number',
                    'students.first_name',
                    'students.last_name',
                    'students.middle_name',
                    'students.course',
                    'students.year_level',
                    'students.status',
                    'risk_prediction.risk_score',
                    'risk_prediction.risk_level',
                    'risk_prediction.factors',
                    'risk_prediction.prediction_date',
                    'risk_prediction.updated_at as last_computed_at',
                );

            if ($riskLevel) {
                $riskQuery->where('risk_prediction.risk_level', $riskLevel);
            }
            if ($riskSearch) {
                $riskQuery->where(function ($q) use ($riskSearch) {
                    $q->where('students.first_name', 'like', "%{$riskSearch}%")
                        ->orWhere('students.last_name', 'like', "%{$riskSearch}%")
                        ->orWhere('students.student_number', 'like', "%{$riskSearch}%");
                });
            }

            $riskQuery->orderByRaw('CASE WHEN risk_prediction.risk_score IS NULL THEN 1 ELSE 0 END')
                ->orderBy('risk_prediction.risk_score', 'desc');

            $riskStudentsPaginated = $riskQuery
                ->paginate($request->input('perPage', 20))
                ->withQueryString();

            $riskStudentsPaginated->getCollection()->transform(function ($s) {
                $factors = $s->factors
                    ? (is_array($s->factors) ? $s->factors : json_decode($s->factors, true))
                    : null;
                return [
                    'student_number'   => $s->student_number,
                    'student_name'     => "{$s->last_name}, {$s->first_name}" . ($s->middle_name ? " {$s->middle_name}" : ''),
                    'course'           => $s->course,
                    'year_level'       => $s->year_level,
                    'risk_score'       => $s->risk_score,
                    'risk_level'       => $s->risk_level,
                    'factors'          => $factors,
                    'prediction_date'  => $s->prediction_date,
                    'last_computed_at' => $s->last_computed_at,
                ];
            });

            $totalStudents = Student::count();
            $computed      = RiskPrediction::count();
            $riskStats = [
                ['title' => 'Total Students',   'value' => $totalStudents,                      'color' => 'blue'],
                ['title' => 'High Risk',         'value' => RiskPrediction::where('risk_level', 'High')->count(),     'color' => 'red'],
                ['title' => 'Moderate Risk',     'value' => RiskPrediction::where('risk_level', 'Moderate')->count(), 'color' => 'yellow'],
                ['title' => 'Low Risk',          'value' => RiskPrediction::where('risk_level', 'Low')->count(),      'color' => 'green'],
                ['title' => 'Not Yet Computed',  'value' => max(0, $totalStudents - $computed),  'color' => 'gray'],
            ];
            $riskStudents  = $riskStudentsPaginated;
            $riskFilters   = ['risk_search' => $riskSearch, 'risk_level' => $riskLevel];
        }

        return Inertia::render('Admin/Discipline/Index', [
            'violations' => $violations,
            'filters' => $request->only(['search', 'severity', 'status', 'acad_id', 'sort_by', 'sort_dir', 'show_voided']),
            'enrollments' => $enrollments,
            'terms' => $terms,
            'workflowSteps' => $workflowSteps,
            'violationTypes' => DisciplineViolationType::getAllForDropdown(),
            'violationSeverities' => SystemSetting::getList('violation_severities'),
            'voidedCount' => $voidedCount,
            'dashboardStats' => [
                ['title' => 'Total Violations', 'value' => $totalViolations, 'color' => 'blue'],
                ['title' => 'Pending Cases', 'value' => $pendingCases, 'color' => 'yellow'],
                ['title' => 'Resolved Cases', 'value' => $resolvedCases, 'color' => 'green'],
                ['title' => 'Major Cases', 'value' => $majorCases, 'color' => 'red'],
            ],
            'activeTab'    => $activeTab,
            'riskStudents' => $riskStudents,
            'riskStats'    => $riskStats,
            'riskFilters'  => $riskFilters,
        ]);
    }

    /**
     * Compute risk scores for all students.
     */
    public function computeRiskAll(RiskScoringService $service): RedirectResponse
    {
        $count = $service->computeAll();
        return redirect()->route('admin.discipline.index', ['tab' => 'risk'])
            ->with('success', "Risk scores computed for {$count} students.");
    }

    /**
     * Recompute risk score for a single student.
     */
    public function computeRiskOne(Student $student, RiskScoringService $service): RedirectResponse
    {
        $service->computeAndSave($student);
        return back()->with('success', "Risk score updated for {$student->full_name}.");
    }

    /**
     * Store a newly created violation and notify the student.
     */
    public function store(StoreDisciplineRequest $request): RedirectResponse
    {
        $this->authorize('create', Discipline::class);

        $enrollment = EnrolledStudent::find($request->enrollment_id);
        if (!$enrollment) {
            return redirect()->back()->withErrors(['enrollment_id' => 'Invalid enrollment.'])->withInput();
        }

        $data = $request->validated();
        $data['student_number'] = $enrollment->student_number;
        $data['enrollment_id'] = $enrollment->enrollment_id;
        $data['reported_by'] = $data['reported_by'] ?? Auth::id();

        // Handle narrative report file upload
        $narrativeFilePath = null;
        if ($request->hasFile('narrative_report_file')) {
            $narrativeFilePath = $request->file('narrative_report_file')
                ->store('discipline/narratives', 'public');
        }

        DB::transaction(function () use ($data, $request, $narrativeFilePath) {
            // Default to first workflow step if no status is specified
            $defaultStatus = $data['status'] ?? DisciplineWorkflowStep::ordered()->first()?->name ?? 'Violation Reported';

            $discipline = Discipline::create([
                'student_number' => $data['student_number'],
                'enrollment_id' => $data['enrollment_id'],
                'violation_date' => $data['violation_date'],
                'violation_type' => $data['violation_type'],
                'description' => $data['description'],
                'sanction' => $data['sanction'] ?? null,
                'severity' => $data['severity'] ?? null,
                'status' => $defaultStatus,
                'remarks' => $data['remarks'] ?? null,
                'narrative_report' => $data['narrative_report'] ?? null,
                'narrative_report_file' => $narrativeFilePath,
                'reported_by' => $data['reported_by'],
            ]);

            $userId = $this->disciplineService->resolveUserIdForEnrollment($discipline->enrollment_id);
            if ($userId) {
                $descSummary = Str::length($discipline->description) > 100
                    ? Str::limit($discipline->description, 100) . ' See details in the system.'
                    : $discipline->description;
                $message = 'A new violation has been recorded for your account. Violation type: ' . $discipline->violation_type . '. Date: ' . $discipline->violation_date->format('F j, Y') . '. ' . $descSummary . "\n\n" . notification_contact_footer('discipline');
                Notification::create([
                    'user_id' => $userId,
                    'type' => 'discipline',
                    'title' => 'New violation recorded',
                    'message' => $message,
                    'related_case_id' => $discipline->discipline_id,
                    'related_meeting_id' => null,
                    'is_read' => false,
                ]);
            }
        });

        return redirect()->route('admin.discipline.index')
            ->with('success', 'Violation record created successfully.');
    }

    /**
     * Display the specified violation with meetings and history.
     */
    public function show(Discipline $discipline): Response
    {
        $this->authorize('view', $discipline);

        $discipline->load([
            'student',
            'reportedBy',
            'voidedBy',
            'enrollment.academicCalendar',
            'meetings',
            'disciplineHistories.changedBy',
        ]);

        $violation = [
            'discipline_id' => $discipline->discipline_id,
            'student' => [
                'student_number' => $discipline->student->student_number ?? '',
                'full_name' => $discipline->student->full_name ?? '',
            ],
            'violation_date' => $discipline->violation_date->format('Y-m-d'),
            'violation_type' => $discipline->violation_type,
            'description' => $discipline->description,
            'sanction' => $discipline->sanction,
            'date_resolved' => $discipline->date_resolved?->format('Y-m-d'),
            'severity' => $discipline->severity,
            'status' => $discipline->status,
            'remarks' => $discipline->remarks,
            'narrative_report' => $discipline->narrative_report,
            'narrative_report_file' => $discipline->narrative_report_file,
            'narrative_report_file_url' => $discipline->narrative_report_file
                ? Storage::disk('public')->url($discipline->narrative_report_file)
                : null,
            'narrative_report_file_name' => $discipline->narrative_report_file
                ? basename($discipline->narrative_report_file)
                : null,
            'reported_by' => $discipline->reportedBy ? [
                'user_id' => $discipline->reportedBy->user_id,
                'email' => $discipline->reportedBy->email,
            ] : null,
            'created_at' => $discipline->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $discipline->updated_at->format('Y-m-d H:i:s'),
            'voided_at' => $discipline->voided_at?->format('Y-m-d H:i'),
            'void_reason' => $discipline->void_reason,
            'void_notes' => $discipline->void_notes,
            'voided_by' => $discipline->voidedBy ? [
                'user_id' => $discipline->voidedBy->user_id,
                'email' => $discipline->voidedBy->email,
            ] : null,
        ];

        $meetings = $discipline->meetings->map(fn($m) => [
            'meeting_id' => $m->meeting_id,
            'meeting_date' => $m->meeting_date->format('Y-m-d'),
            'meeting_time' => $m->meeting_time,
            'location' => $m->location,
            'purpose_notes' => $m->purpose_notes,
            'status' => $m->status,
        ]);

        $history = $discipline->disciplineHistories->map(fn($h) => [
            'history_id' => $h->history_id,
            'old_status' => $h->old_status,
            'new_status' => $h->new_status,
            'note' => $h->note,
            'created_at' => $h->created_at?->format('Y-m-d H:i'),
            'changed_by' => $h->changedBy->email ?? null,
        ]);

        $studentViolationCount = Discipline::where('student_number', $discipline->student_number)->count();
        $isRepeatOffender = $studentViolationCount >= 2;

        return Inertia::render('Admin/Discipline/Show', [
            'violation' => $violation,
            'meetings' => $meetings,
            'history' => $history,
            'workflowSteps' => DisciplineWorkflowStep::getStepsForProgressBar(),
            'terminalStatuses' => DisciplineWorkflowStep::getTerminalNames(),
            'violationTypes' => DisciplineViolationType::getAllForDropdown(),
            'violationSeverities' => SystemSetting::getList('violation_severities'),
            'isRepeatOffender' => $isRepeatOffender,
            'studentViolationCount' => $studentViolationCount,
        ]);
    }

    /**
     * Update the specified violation; record history and notify student.
     */
    public function update(UpdateDisciplineRequest $request, Discipline $discipline): RedirectResponse
    {
        $this->authorize('update', $discipline);

        $oldStatus = $discipline->status;
        $data = $request->validated();
        $data['reported_by'] = $data['reported_by'] ?? Auth::id();

        // Handle narrative report file upload
        $narrativeFilePath = $discipline->narrative_report_file;
        if ($request->hasFile('narrative_report_file')) {
            // Delete old file if exists
            if ($discipline->narrative_report_file) {
                Storage::disk('public')->delete($discipline->narrative_report_file);
            }
            $narrativeFilePath = $request->file('narrative_report_file')
                ->store('discipline/narratives', 'public');
        } elseif ($request->boolean('remove_narrative_file') && $discipline->narrative_report_file) {
            Storage::disk('public')->delete($discipline->narrative_report_file);
            $narrativeFilePath = null;
        }

        DB::transaction(function () use ($discipline, $data, $oldStatus, $narrativeFilePath, $request) {
            $updateData = [
                'violation_date' => $data['violation_date'],
                'violation_type' => $data['violation_type'],
                'description' => $data['description'],
                'severity' => $data['severity'] ?? null,
                'status' => $data['status'],
                'sanction' => $data['sanction'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'date_resolved' => $data['date_resolved'] ?? null,
                'reported_by' => $data['reported_by'],
            ];

            // Only update narrative fields when they are explicitly sent
            if ($request->has('narrative_report')) {
                $updateData['narrative_report'] = $data['narrative_report'] ?? null;
                $updateData['narrative_report_file'] = $narrativeFilePath;
            }

            $discipline->update($updateData);

            if ($oldStatus !== $discipline->status) {
                DisciplineHistory::create([
                    'case_id' => $discipline->discipline_id,
                    'changed_by_user_id' => Auth::id(),
                    'old_status' => $oldStatus,
                    'new_status' => $discipline->status,
                    'note' => null,
                ]);
            }

            $userId = $this->disciplineService->resolveUserIdForDiscipline($discipline);
            if ($userId) {
                $message = 'Your violation record has been updated. Current status: ' . $discipline->status . "\n\n" . notification_contact_footer('discipline');
                Notification::create([
                    'user_id' => $userId,
                    'type' => 'discipline',
                    'title' => 'Violation case updated',
                    'message' => $message,
                    'related_case_id' => $discipline->discipline_id,
                    'related_meeting_id' => null,
                    'is_read' => false,
                ]);
            }
        });

        return redirect()->route('admin.discipline.show', $discipline)
            ->with('success', 'Violation record updated successfully.');
    }

    /**
     * Store a meeting for a discipline case and notify the student.
     */
    public function storeMeeting(StoreDisciplineMeetingRequest $request, Discipline $discipline): RedirectResponse
    {
        $this->authorize('view', $discipline);

        $data = $request->validated();
        $data['location'] = $data['location'] ?? 'Discipline Office';

        DB::transaction(function () use ($discipline, $data) {
            $meeting = DisciplineMeeting::create([
                'case_id' => $discipline->discipline_id,
                'meeting_date' => $data['meeting_date'],
                'meeting_time' => $data['meeting_time'] ?? null,
                'location' => $data['location'],
                'purpose_notes' => $data['purpose_notes'] ?? null,
                'status' => $data['status'],
                'created_by_user_id' => Auth::id(),
            ]);

            $userId = $this->disciplineService->resolveUserIdForDiscipline($discipline);
            if ($userId) {
                $date = $meeting->meeting_date->format('M j, Y');
                $time = $meeting->meeting_time ? \Carbon\Carbon::parse($meeting->meeting_time)->format('g:i A') : 'TBD';
                $loc = $meeting->location;
                $message = "You are requested to report to the following office call. Location: {$loc}. Date: {$date}. Time: {$time}.\n\n" . notification_contact_footer('discipline');
                Notification::create([
                    'user_id' => $userId,
                    'type' => 'discipline',
                    'title' => 'Office call scheduled',
                    'message' => $message,
                    'related_case_id' => $discipline->discipline_id,
                    'related_meeting_id' => $meeting->meeting_id,
                    'is_read' => false,
                ]);
            }
        });

        return redirect()->route('admin.discipline.show', $discipline)
            ->with('success', 'Meeting scheduled successfully.');
    }

    /**
     * Update a meeting and notify the student if rescheduled or cancelled.
     */
    public function updateMeeting(UpdateDisciplineMeetingRequest $request, Discipline $discipline, DisciplineMeeting $meeting): RedirectResponse
    {
        $this->authorize('view', $discipline);

        if ($meeting->case_id !== $discipline->discipline_id) {
            abort(404);
        }

        $oldStatus = $meeting->status;
        $meeting->update([
            'meeting_date' => $request->meeting_date,
            'meeting_time' => $request->meeting_time,
            'location' => $request->location ?? $meeting->location,
            'purpose_notes' => $request->purpose_notes,
            'status' => $request->status,
        ]);

        $userId = $this->disciplineService->resolveUserIdForDiscipline($discipline);
        if ($userId && $oldStatus !== $meeting->status && in_array($meeting->status, ['rescheduled', 'cancelled'], true)) {
            if ($meeting->status === 'cancelled') {
                $message = "Your scheduled office call has been cancelled.\n\n" . notification_contact_footer('discipline');
                Notification::create([
                    'user_id' => $userId,
                    'type' => 'discipline',
                    'title' => 'Meeting cancelled',
                    'message' => $message,
                    'related_case_id' => $discipline->discipline_id,
                    'related_meeting_id' => $meeting->meeting_id,
                    'is_read' => false,
                ]);
            } else {
                $date = $meeting->meeting_date->format('M j, Y');
                $time = $meeting->meeting_time ? \Carbon\Carbon::parse($meeting->meeting_time)->format('g:i A') : 'TBD';
                $loc = $meeting->location;
                $message = "You are requested to report to the following office call. Location: {$loc}. Date: {$date}. Time: {$time}.\n\n" . notification_contact_footer('discipline');
                Notification::create([
                    'user_id' => $userId,
                    'type' => 'discipline',
                    'title' => 'Meeting rescheduled',
                    'message' => $message,
                    'related_case_id' => $discipline->discipline_id,
                    'related_meeting_id' => $meeting->meeting_id,
                    'is_read' => false,
                ]);
            }
        }

        return redirect()->route('admin.discipline.show', $discipline)
            ->with('success', 'Meeting updated successfully.');
    }

    /**
     * Export discipline violations to PDF.
     */
    public function exportPdf(Request $request)
    {
        $query = $this->buildFilteredDisciplineQuery($request)
            ->with(['student', 'enrollment.academicCalendar']);

        $violations = $query->orderBy('violation_date', 'desc')->get();

        $headers = ['ID', 'Student', 'Violation Date', 'Type', 'Severity', 'Status'];
        $rows = $violations->map(fn($v) => [
            $v->discipline_id,
            ($v->student?->full_name ?? '') . ' (' . ($v->student?->student_number ?? '') . ')',
            $v->violation_date->format('Y-m-d'),
            $v->violation_type,
            $v->severity,
            $v->status,
        ])->toArray();

        // Build human-readable filter labels
        $filterLabels = [];
        if ($request->filled('search')) {
            $filterLabels['Search'] = $request->search;
        }
        if ($request->filled('severity')) {
            $filterLabels['Severity'] = $request->severity;
        }
        if ($request->filled('status')) {
            $filterLabels['Status'] = $request->status;
        }
        if ($request->filled('acad_id')) {
            $term = AcademicCalendar::find($request->acad_id);
            $filterLabels['Term'] = $term ? $term->display_label : $request->acad_id;
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.pdf-table', [
            'title' => 'Discipline Violations Report',
            'date' => now()->format('F j, Y g:i A'),
            'headers' => $headers,
            'rows' => $rows,
            'filters' => $filterLabels,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('discipline_export_' . date('Y-m-d_His') . '.pdf');
    }

    /**
     * Update only the status of a discipline case (from progress bar).
     */
    public function updateStatus(Request $request, Discipline $discipline): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'string', Rule::in(DisciplineWorkflowStep::getStepNames())],
        ]);

        $oldStatus = $discipline->status;
        $newStatus = $request->input('status');

        if ($oldStatus === $newStatus) {
            return redirect()->back();
        }

        DB::transaction(function () use ($discipline, $oldStatus, $newStatus) {
            $updateData = ['status' => $newStatus];

            // Auto-set date_resolved when marking as Resolved
            if ($newStatus === 'resolved' && !$discipline->date_resolved) {
                $updateData['date_resolved'] = now()->toDateString();
            }

            $discipline->update($updateData);

            DisciplineHistory::create([
                'case_id' => $discipline->discipline_id,
                'changed_by_user_id' => Auth::id(),
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'note' => 'Status updated via progress bar',
            ]);

            $userId = $this->disciplineService->resolveUserIdForDiscipline($discipline);
            if ($userId) {
                $message = "Your violation record status has been updated from {$oldStatus} to {$newStatus}.\n\n" . notification_contact_footer('discipline');
                Notification::create([
                    'user_id' => $userId,
                    'type' => 'discipline',
                    'title' => 'Case status updated',
                    'message' => $message,
                    'related_case_id' => $discipline->discipline_id,
                    'is_read' => false,
                ]);
            }
        });

        return redirect()->route('admin.discipline.show', $discipline)
            ->with('success', "Status updated to {$newStatus}.");
    }

    /**
     * Void a violation record (soft invalidation with reason).
     */
    public function void(Request $request, Discipline $discipline): RedirectResponse
    {
        $request->validate([
            'void_reason' => ['required', 'string', 'in:Wrong Student,Wrong Violation Type,Duplicate Entry,Data Entry Error,Other'],
            'void_notes'  => ['nullable', 'string', 'max:1000'],
        ]);

        if ($discipline->voided_at) {
            return back()->with('error', 'This violation is already voided.');
        }

        DB::transaction(function () use ($discipline, $request) {
            $discipline->update([
                'voided_at'   => now(),
                'voided_by'   => Auth::id(),
                'void_reason' => $request->void_reason,
                'void_notes'  => $request->void_notes,
            ]);

            DisciplineHistory::create([
                'case_id'             => $discipline->discipline_id,
                'changed_by_user_id'  => Auth::id(),
                'old_status'          => $discipline->status,
                'new_status'          => 'voided',
                'note'                => 'Voided — ' . $request->void_reason
                    . ($request->void_notes ? ': ' . $request->void_notes : ''),
            ]);
        });

        return back()->with('success', 'Violation voided successfully.');
    }

    /**
     * Permanently delete a violation record (requires admin password confirmation).
     */
    public function destroy(Request $request, Discipline $discipline): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (!Hash::check($request->password, Auth::user()->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Deletion cancelled.']);
        }

        $disciplineId = $discipline->discipline_id;

        if ($discipline->narrative_report_file) {
            Storage::disk('public')->delete($discipline->narrative_report_file);
        }

        $discipline->disciplineHistories()->delete();
        $discipline->meetings()->delete();
        $discipline->delete();

        return redirect()->route('admin.discipline.index')
            ->with('success', "Violation record #{$disciplineId} permanently deleted.");
    }

    /**
     * Build a Discipline query with search, severity, status, and acad_id filters applied.
     */
    private function buildFilteredDisciplineQuery(Request $request)
    {
        $query = Discipline::query();

        // By default show only active (non-voided) records; toggle with show_voided=1
        if ($request->boolean('show_voided', false)) {
            $query->whereNotNull('voided_at');
        } else {
            $query->whereNull('voided_at');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', function ($studentQuery) use ($search) {
                    $studentQuery->where('student_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('violation_type', 'like', "%{$search}%");
                if (is_numeric($search)) {
                    $q->orWhere('discipline_id', (int) $search);
                }
            });
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('acad_id')) {
            $query->whereHas('enrollment', function ($q) use ($request) {
                $q->where('acad_id', $request->acad_id);
            });
        }

        return $query;
    }
}
