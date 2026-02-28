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
use App\Services\DisciplineService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        $totalViolations = Discipline::count();
        $pendingCases = Discipline::where('status', 'Pending')->count();
        $resolvedCases = Discipline::where('status', 'Resolved')->count();
        $majorCases = Discipline::where('severity', 'Major')->count();

        $query = Discipline::with(['student', 'reportedBy', 'enrollment.academicCalendar']);

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

        $violations = $query->orderBy('violation_date', 'desc')
            ->orderBy('created_at', 'desc')
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

        return Inertia::render('Admin/Discipline/Index', [
            'violations' => $violations,
            'filters' => $request->only(['search', 'severity', 'status', 'acad_id']),
            'enrollments' => $enrollments,
            'terms' => $terms,
            'workflowSteps' => $workflowSteps,
            'violationTypes' => DisciplineViolationType::getAllForDropdown(),
            'dashboardStats' => [
                ['title' => 'Total Violations', 'value' => $totalViolations, 'color' => 'blue'],
                ['title' => 'Pending Cases', 'value' => $pendingCases, 'color' => 'yellow'],
                ['title' => 'Resolved Cases', 'value' => $resolvedCases, 'color' => 'green'],
                ['title' => 'Major Cases', 'value' => $majorCases, 'color' => 'red'],
            ],
        ]);
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
        unset($data['enrollment_id']); // already set
        $data['enrollment_id'] = $enrollment->enrollment_id;

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
                Notification::create([
                    'user_id' => $userId,
                    'type' => 'discipline',
                    'title' => 'New violation recorded',
                    'message' => 'A violation has been recorded for your account. Case #' . $discipline->discipline_id,
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
                ? Storage::url($discipline->narrative_report_file)
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

        return Inertia::render('Admin/Discipline/Show', [
            'violation' => $violation,
            'meetings' => $meetings,
            'history' => $history,
            'workflowSteps' => DisciplineWorkflowStep::getStepsForProgressBar(),
            'terminalStatuses' => DisciplineWorkflowStep::getTerminalNames(),
            'violationTypes' => DisciplineViolationType::getAllForDropdown(),
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
                Notification::create([
                    'user_id' => $userId,
                    'type' => 'discipline',
                    'title' => 'Violation case updated',
                    'message' => 'Your violation case #' . $discipline->discipline_id . ' has been updated. Status: ' . $discipline->status,
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
                Notification::create([
                    'user_id' => $userId,
                    'type' => 'discipline',
                    'title' => 'Office call scheduled',
                    'message' => "You are requested to report to {$loc} on {$date} at {$time}.",
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
        if ($userId && in_array($meeting->status, ['rescheduled', 'cancelled'], true)) {
            if ($meeting->status === 'cancelled') {
                Notification::create([
                    'user_id' => $userId,
                    'type' => 'discipline',
                    'title' => 'Meeting cancelled',
                    'message' => 'Your scheduled office call for case #' . $discipline->discipline_id . ' has been cancelled.',
                    'related_case_id' => $discipline->discipline_id,
                    'related_meeting_id' => $meeting->meeting_id,
                    'is_read' => false,
                ]);
            } else {
                $date = $meeting->meeting_date->format('M j, Y');
                $time = $meeting->meeting_time ? \Carbon\Carbon::parse($meeting->meeting_time)->format('g:i A') : 'TBD';
                $loc = $meeting->location;
                Notification::create([
                    'user_id' => $userId,
                    'type' => 'discipline',
                    'title' => 'Meeting rescheduled',
                    'message' => "You are requested to report to {$loc} on {$date} at {$time}.",
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
        $query = Discipline::with(['student', 'enrollment.academicCalendar']);

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

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.pdf-table', [
            'title' => 'Discipline Violations Report',
            'date' => now()->format('F j, Y g:i A'),
            'headers' => $headers,
            'rows' => $rows,
            'filters' => $request->only(['search', 'severity', 'status', 'acad_id']),
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
            if ($newStatus === 'Resolved' && !$discipline->date_resolved) {
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
                Notification::create([
                    'user_id' => $userId,
                    'type' => 'discipline',
                    'title' => 'Case status updated',
                    'message' => "Your case #{$discipline->discipline_id} status changed from {$oldStatus} to {$newStatus}.",
                    'related_case_id' => $discipline->discipline_id,
                    'is_read' => false,
                ]);
            }
        });

        return redirect()->route('admin.discipline.show', $discipline)
            ->with('success', "Status updated to {$newStatus}.");
    }
}
