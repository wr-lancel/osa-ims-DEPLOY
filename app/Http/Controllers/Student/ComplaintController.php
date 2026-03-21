<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreComplaintRequest;
use App\Models\Complaint;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Student;
use App\Services\ComplaintService;
use App\Services\DisciplineService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected DisciplineService $disciplineService,
        protected ComplaintService $complaintService
    ) {
    }

    /**
     * Show the form for submitting a complaint.
     */
    public function create(): Response
    {
        $this->authorize('create', Complaint::class);

        $categories = [
            ['value' => 'Academic Integrity', 'label' => 'Academic Integrity'],
            ['value' => 'Campus Conduct', 'label' => 'Campus Conduct'],
            ['value' => 'Prohibited Activities', 'label' => 'Prohibited Activities'],
            ['value' => 'Other', 'label' => 'Other'],
        ];

        $employees = Employee::select('employee_id', 'first_name', 'last_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn($e) => [
                'value' => $e->employee_id,
                'label' => $e->last_name . ', ' . $e->first_name,
            ]);

        return Inertia::render('Student/Complaint/Create', [
            'categories' => $categories,
            'employees' => $employees,
        ]);
    }

    /**
     * Store a newly created complaint and notify discipline admins.
     */
    public function store(StoreComplaintRequest $request): RedirectResponse
    {
        $this->authorize('create', Complaint::class);

        $user = Auth::user();
        $enrollment = $this->disciplineService->currentEnrollmentForStudent($user->student_number);
        if (!$enrollment) {
            return redirect()->back()
                ->withErrors(['enrollment' => 'No active enrollment found. You must be enrolled to submit a complaint.'])
                ->withInput();
        }

        $data = $request->validated();

        $respondentEnrolledId = null;
        $respondentEmployeeId = null;
        $respondentName = null;
        $respondentType = $data['respondent_type'] ?? null;

        if ($respondentType === 'student' && !empty($data['respondent_student_number'])) {
            $respondentEnrollment = $this->disciplineService->currentEnrollmentForStudent($data['respondent_student_number']);
            if ($respondentEnrollment) {
                $respondentEnrolledId = $respondentEnrollment->enrollment_id;
            }
        } elseif ($respondentType === 'employee') {
            $respondentEmployeeId = $data['respondent_employee_id'] ?? null;
        } elseif ($respondentType === 'other') {
            $respondentName = $data['respondent_name'] ?? null;
        }

        DB::transaction(function () use ($data, $enrollment, $respondentType, $respondentEnrolledId, $respondentEmployeeId, $respondentName) {
            $complaint = Complaint::create([
                'complainant_enrolled_id' => $enrollment->enrollment_id,
                'respondent_type' => $respondentType,
                'respondent_enrolled_id' => $respondentEnrolledId,
                'respondent_employee_id' => $respondentEmployeeId,
                'respondent_name' => $respondentName,
                'category' => $data['category'],
                'subject' => $data['subject'],
                'description' => $data['description'],
                'incident_date' => $data['incident_date'],
                'location' => $data['location'] ?? null,
                'status' => 'submitted',
                'anonymous' => (bool) ($data['anonymous'] ?? false),
            ]);

            \App\Models\ComplaintHistory::create([
                'complaint_id' => $complaint->complaint_id,
                'changed_by_user_id' => null,
                'old_status' => null,
                'new_status' => 'submitted',
                'remarks' => null,
            ]);

            $this->complaintService->notifyAdminsNewComplaint($complaint);
        });

        return redirect()->route('student.discipline.complaints.index')
            ->with('success', 'Your complaint has been submitted successfully.');
    }

    /**
     * List the authenticated student's complaints (My Complaints).
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Complaint::class);

        $user = Auth::user();
        $studentNumber = $user->student_number;

        $sortBy = in_array($request->input('sort_by'), ['created_at', 'category', 'status']) ? $request->input('sort_by') : 'created_at';
        $sortDir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';

        $query = Complaint::with(['complainantEnrollment.academicCalendar'])
            ->whereHas('complainantEnrollment', function ($q) use ($studentNumber) {
                $q->where('student_number', $studentNumber);
            })
            ->orderBy($sortBy, $sortDir);

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $complaints = $query->paginate($request->input('perPage', 20))
            ->withQueryString()
            ->through(fn($c) => [
                'complaint_id' => $c->complaint_id,
                'subject' => $c->subject,
                'category' => $c->category,
                'date_submitted' => $c->created_at->format('Y-m-d'),
                'status' => $c->status,
            ]);

        $categories = [
            ['value' => '', 'label' => 'All Categories'],
            ['value' => 'Academic Integrity', 'label' => 'Academic Integrity'],
            ['value' => 'Campus Conduct', 'label' => 'Campus Conduct'],
            ['value' => 'Prohibited Activities', 'label' => 'Prohibited Activities'],
            ['value' => 'Other', 'label' => 'Other'],
        ];
        $statusOptions = [
            ['value' => '', 'label' => 'All Statuses'],
            ['value' => 'submitted', 'label' => 'Submitted'],
            ['value' => 'under_review', 'label' => 'Under Review'],
            ['value' => 'escalated', 'label' => 'Escalated'],
            ['value' => 'dismissed', 'label' => 'Dismissed'],
            ['value' => 'resolved', 'label' => 'Resolved'],
        ];

        return Inertia::render('Student/Complaint/Index', [
            'complaints' => $complaints,
            'filters' => $request->only(['category', 'status', 'sort_by', 'sort_dir']),
            'categories' => $categories,
            'statusOptions' => $statusOptions,
        ]);
    }

    /**
     * Show complaint detail; mark related notifications as read.
     */
    public function show(Complaint $complaint): Response|RedirectResponse
    {
        $this->authorize('view', $complaint);

        $user = Auth::user();
        $complaint->load(['complainantEnrollment.academicCalendar', 'respondentEnrollment', 'complaintHistories.changedBy']);

        $violation = [
            'complaint_id' => $complaint->complaint_id,
            'subject' => $complaint->subject,
            'category' => $complaint->category,
            'description' => $complaint->description,
            'incident_date' => $complaint->incident_date,
            'location' => $complaint->location,
            'status' => $complaint->status,
            'date_submitted' => $complaint->created_at->format('Y-m-d H:i'),
        ];

        $history = $complaint->complaintHistories->map(fn($h) => [
            'old_status' => $h->old_status,
            'new_status' => $h->new_status,
            'remarks' => $h->remarks,
            'created_at' => $h->created_at?->format('Y-m-d H:i'),
            'changed_by' => $h->changedBy->email ?? null,
        ]);

        Notification::where('user_id', $user->user_id)
            ->where('type', 'complaint')
            ->where('related_complaint_id', $complaint->complaint_id)
            ->update(['is_read' => true]);

        return Inertia::render('Student/Complaint/Show', [
            'complaint' => $violation,
            'history' => $history,
        ]);
    }
}
