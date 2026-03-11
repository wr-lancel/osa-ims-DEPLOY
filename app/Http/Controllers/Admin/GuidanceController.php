<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreGuidanceCaseRequest;
use App\Http\Requests\Admin\UpdateGuidanceCaseRequest;
use App\Http\Requests\Admin\StoreGuidanceCaseActionRequest;
use App\Http\Requests\Admin\ApproveGuidanceAppointmentRequest;
use App\Http\Requests\Admin\RejectGuidanceAppointmentRequest;
use App\Models\GuidanceCase;
use App\Models\GuidanceCaseAction;
use App\Models\GuidanceAppointment;
use App\Models\EnrolledStudent;
use App\Models\AcademicCalendar;
use App\Models\Employee;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class GuidanceController extends Controller
{
    /**
     * Display a listing of guidance cases.
     */
    public function index(Request $request): Response
    {
        // Get dashboard statistics for cases
        $totalCases = GuidanceCase::count();
        $pendingCaseRequests = GuidanceCase::where('status', 'pending')->count();
        $completedSessions = GuidanceCase::whereIn('status', ['resolved', 'closed'])->count();
        // Count employees assigned to guidance cases as active counselors
        $activeCounselors = Employee::whereHas('guidanceCases')->distinct()->count();

        // Get dashboard statistics for appointments
        $totalAppointments = GuidanceAppointment::count();
        $pendingAppointmentRequests = GuidanceAppointment::where('status', 'pending')->count();
        $approvedAppointments = GuidanceAppointment::where('status', 'approved')->count();
        $completedAppointments = GuidanceAppointment::where('status', 'completed')->count();

        $query = GuidanceCase::with([
            'enrollment.student',
            'enrollment.section',
            'assignedStaff',
            'actions.actionByUser'
        ]);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('case_no', 'like', "%{$search}%")
                    ->orWhere('concern', 'like', "%{$search}%")
                    ->orWhereHas('enrollment.student', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('student_number', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Case type filter
        if ($request->filled('case_type')) {
            $query->where('case_type', $request->case_type);
        }

        // Assigned staff filter
        if ($request->filled('assigned_staff_id')) {
            $query->where('assigned_staff_id', $request->assigned_staff_id);
        }

        $cases = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('perPage', 20))
            ->through(function ($case) {
                return [
                    'guidance_case_id' => $case->guidance_case_id,
                    'enrollment_id' => $case->enrollment_id,
                    'case_no' => $case->case_no,
                    'case_type' => $case->case_type,
                    'concern' => $case->concern,
                    'status' => $case->status,
                    'assigned_staff_id' => $case->assigned_staff_id,
                    'assigned_staff' => $case->assignedStaff ? [
                        'employee_id' => $case->assignedStaff->employee_id,
                        'full_name' => $case->assignedStaff->full_name,
                    ] : null,
                    'requested_at' => $case->requested_at?->format('Y-m-d H:i:s'),
                    'created_at' => $case->created_at->format('Y-m-d H:i:s'),
                    'student' => $case->enrollment->student ? [
                        'student_id' => $case->enrollment->student->student_id,
                        'student_number' => $case->enrollment->student->student_number,
                        'first_name' => $case->enrollment->student->first_name,
                        'last_name' => $case->enrollment->student->last_name,
                        'full_name' => $case->enrollment->student->first_name . ' ' . $case->enrollment->student->last_name,
                    ] : null,
                    'section' => $case->enrollment->section ? [
                        'section_id' => $case->enrollment->section->section_id,
                        'section_name' => $case->enrollment->section->section_name,
                    ] : null,
                    'actions_count' => $case->actions->count(),
                ];
            });

        // Get unique case types and statuses for filters
        $caseTypes = ['counseling', 'consultation', 'referral'];
        $statuses = ['pending', 'ongoing', 'resolved', 'closed'];

        // Get employees for assigned staff filter
        $employees = Employee::orderBy('first_name')->get()->map(fn($e) => [
            'employee_id' => $e->employee_id,
            'full_name' => $e->full_name,
        ]);

        // Get pending appointments for prominent display
        $pendingAppointments = GuidanceAppointment::with(['student', 'employee', 'approver', 'rejector'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get()
            ->map(fn($a) => $this->transformAppointmentForList($a));

        // Get all appointments with filters
        $appointmentsQuery = GuidanceAppointment::with(['student', 'employee', 'approver', 'rejector']);

        // Search filter for appointments
        if ($request->filled('appointment_search')) {
            $search = $request->appointment_search;
            $appointmentsQuery->where(function ($q) use ($search) {
                $q->where('concern', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery->where('student_number', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('employee', function ($employeeQuery) use ($search) {
                        $employeeQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter for appointments
        if ($request->filled('appointment_status')) {
            $appointmentsQuery->where('status', $request->appointment_status);
        }

        // Type filter for appointments
        if ($request->filled('appointment_type')) {
            $appointmentsQuery->where('appointment_type', $request->appointment_type);
        }

        $appointments = $appointmentsQuery
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->paginate($request->input('perPage', 20), ['*'], 'appointments_page')
            ->withQueryString();

        // Transform appointments data
        $appointments->getCollection()->transform(fn($a) => $this->transformAppointmentForList($a, true));

        return Inertia::render('Admin/Guidance/Index', [
            'cases' => $cases,
            'appointments' => $appointments,
            'pendingAppointments' => $pendingAppointments,
            'filters' => $request->only(['search', 'status', 'case_type', 'assigned_staff_id', 'appointment_search', 'appointment_status', 'appointment_type']),
            'caseTypes' => $caseTypes,
            'statuses' => $statuses,
            'employees' => $employees,
            'dashboardStats' => [
                [
                    'title' => 'Total Cases',
                    'value' => $totalCases,
                    'color' => 'blue',
                ],
                [
                    'title' => 'Pending Case Requests',
                    'value' => $pendingCaseRequests,
                    'color' => 'yellow',
                ],
                [
                    'title' => 'Pending Appointments',
                    'value' => $pendingAppointmentRequests,
                    'color' => 'yellow',
                ],
                [
                    'title' => 'Completed Sessions',
                    'value' => $completedSessions,
                    'color' => 'green',
                ],
                [
                    'title' => 'Total Appointments',
                    'value' => $totalAppointments,
                    'color' => 'blue',
                ],
                [
                    'title' => 'Active Counselors',
                    'value' => $activeCounselors,
                    'color' => 'blue',
                ],
            ],
        ]);
    }

    /**
     * Store a newly created guidance case.
     */
    public function store(StoreGuidanceCaseRequest $request)
    {
        try {
            $case = GuidanceCase::create([
                'enrollment_id' => $request->enrollment_id,
                'case_no' => $request->case_no,
                'case_type' => $request->case_type,
                'concern' => $request->concern,
                'status' => $request->status,
                'assigned_staff_id' => $request->assigned_staff_id,
                'requested_at' => $request->requested_at ? now()->parse($request->requested_at) : now(),
            ]);

            $userId = Auth::check() ? Auth::user()->user_id : 'unknown';
            Log::info("Guidance case created: {$case->case_no} by user {$userId}");

            return response()->json([
                'success' => true,
                'message' => 'Guidance case created successfully.',
                'case' => $case->load(['enrollment.student', 'assignedStaff']),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create guidance case: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create guidance case: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to create guidance case. Please try again.',
            ], 500);
        }
    }

    /**
     * Display the specified guidance case with actions.
     */
    public function show(GuidanceCase $case)
    {
        $case->load([
            'enrollment.student',
            'enrollment.section.course',
            'assignedStaff',
            'actions.actionByUser' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }
        ]);

        return response()->json([
            'success' => true,
            'case' => [
                'guidance_case_id' => $case->guidance_case_id,
                'enrollment_id' => $case->enrollment_id,
                'case_no' => $case->case_no,
                'case_type' => $case->case_type,
                'concern' => $case->concern,
                'status' => $case->status,
                'assigned_staff_id' => $case->assigned_staff_id,
                'assigned_staff' => $case->assignedStaff ? [
                    'employee_id' => $case->assignedStaff->employee_id,
                    'full_name' => $case->assignedStaff->full_name,
                ] : null,
                'requested_at' => $case->requested_at?->format('Y-m-d H:i:s'),
                'created_at' => $case->created_at->format('Y-m-d H:i:s'),
                'student' => $case->enrollment->student ? [
                    'student_id' => $case->enrollment->student->student_id,
                    'student_number' => $case->enrollment->student->student_number,
                    'first_name' => $case->enrollment->student->first_name,
                    'last_name' => $case->enrollment->student->last_name,
                    'full_name' => $case->enrollment->student->first_name . ' ' . $case->enrollment->student->last_name,
                ] : null,
                'section' => $case->enrollment->section ? [
                    'section_id' => $case->enrollment->section->section_id,
                    'section_name' => $case->enrollment->section->section_name,
                    'course' => $case->enrollment->section->course ? [
                        'course_id' => $case->enrollment->section->course->course_id,
                        'course_name' => $case->enrollment->section->course->course_name,
                    ] : null,
                ] : null,
                'actions' => $case->actions->map(function ($action) {
                    return [
                        'action_id' => $action->action_id,
                        'note' => $action->note,
                        'action_status' => $action->action_status,
                        'action_at' => $action->action_at?->format('Y-m-d H:i:s'),
                        'created_at' => $action->created_at->format('Y-m-d H:i:s'),
                        'action_by_user' => $action->actionByUser ? [
                            'user_id' => $action->actionByUser->user_id,
                            'display_name' => $action->actionByUser->display_name ?? $action->actionByUser->email,
                        ] : null,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Update the specified guidance case.
     */
    public function update(UpdateGuidanceCaseRequest $request, GuidanceCase $case)
    {
        try {
            $case->update([
                'enrollment_id' => $request->enrollment_id,
                'case_no' => $request->case_no,
                'case_type' => $request->case_type,
                'concern' => $request->concern,
                'status' => $request->status,
                'assigned_staff_id' => $request->assigned_staff_id,
                'requested_at' => $request->requested_at ? now()->parse($request->requested_at) : $case->requested_at,
            ]);

            $userId = Auth::check() ? Auth::user()->user_id : 'unknown';
            Log::info("Guidance case updated: {$case->case_no} by user {$userId}");

            return response()->json([
                'success' => true,
                'message' => 'Guidance case updated successfully.',
                'case' => $case->load(['enrollment.student', 'assignedStaff']),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update guidance case: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update guidance case: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to update guidance case. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove the specified guidance case.
     */
    public function destroy(GuidanceCase $case)
    {
        try {
            $caseNo = $case->case_no;
            $case->delete();

            $userId = Auth::check() ? Auth::user()->user_id : 'unknown';
            Log::info("Guidance case deleted: {$caseNo} by user {$userId}");

            return response()->json([
                'success' => true,
                'message' => 'Guidance case deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to delete guidance case: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete guidance case. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to delete guidance case. Please try again.',
            ], 500);
        }
    }

    /**
     * Add an action/note to a guidance case.
     */
    public function addAction(StoreGuidanceCaseActionRequest $request)
    {
        try {
            $action = GuidanceCaseAction::create([
                'guidance_case_id' => $request->guidance_case_id,
                'action_by_user_id' => Auth::user()->user_id,
                'note' => $request->note,
                'action_status' => $request->action_status,
                'action_at' => $request->action_at ? now()->parse($request->action_at) : now(),
            ]);

            // Optionally update the case status if action_status is provided
            if ($request->filled('action_status')) {
                $case = GuidanceCase::find($request->guidance_case_id);
                if ($case) {
                    $case->update(['status' => $request->action_status]);
                }
            }

            $userId = Auth::check() ? Auth::user()->user_id : 'unknown';
            Log::info("Guidance case action added: case {$request->guidance_case_id} by user {$userId}");

            return response()->json([
                'success' => true,
                'message' => 'Action added successfully.',
                'action' => $action->load('actionByUser'),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to add guidance case action: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to add action: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to add action. Please try again.',
            ], 500);
        }
    }

    /**
     * Get enrollments for dropdown (for creating cases).
     */
    public function getEnrollments(Request $request)
    {
        $activeCalendar = AcademicCalendar::active()->first();

        $query = EnrolledStudent::with(['student', 'section.course'])
            ->where('enrollment_status', 'enrolled')
            ->when($activeCalendar, fn($q) => $q->where('acad_id', $activeCalendar->calendar_id));

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('student_number', 'like', "%{$search}%");
            });
        }

        $enrollments = $query->limit(50)->get()->map(function ($enrollment) {
            return [
                'enrollment_id' => $enrollment->enrollment_id,
                'student' => $enrollment->student ? [
                    'student_id' => $enrollment->student->student_id,
                    'student_number' => $enrollment->student->student_number,
                    'first_name' => $enrollment->student->first_name,
                    'last_name' => $enrollment->student->last_name,
                    'full_name' => $enrollment->student->first_name . ' ' . $enrollment->student->last_name,
                ] : null,
                'section' => $enrollment->section ? [
                    'section_id' => $enrollment->section->section_id,
                    'section_name' => $enrollment->section->section_name,
                    'course' => $enrollment->section->course ? [
                        'course_name' => $enrollment->section->course->course_name,
                    ] : null,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'enrollments' => $enrollments,
        ]);
    }

    /**
     * Display a listing of guidance appointments.
     */
    public function appointments(Request $request): Response
    {
        // Get dashboard statistics
        $totalAppointments = GuidanceAppointment::count();
        $pendingRequests = GuidanceAppointment::where('status', 'pending')->count();
        $approvedAppointments = GuidanceAppointment::where('status', 'approved')->count();
        $completedAppointments = GuidanceAppointment::where('status', 'completed')->count();

        // Get pending requests separately for prominent display
        $pendingAppointments = GuidanceAppointment::with(['student', 'employee', 'approver', 'rejector'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get()
            ->map(fn($a) => $this->transformAppointmentForList($a));

        // Get all appointments with filters
        $appointmentsQuery = GuidanceAppointment::with(['student', 'employee', 'approver', 'rejector']);

        // Search filter
        if ($request->filled('appointment_search')) {
            $search = $request->appointment_search;
            $appointmentsQuery->where(function ($q) use ($search) {
                $q->where('concern', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery->where('student_number', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('employee', function ($employeeQuery) use ($search) {
                        $employeeQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($request->filled('appointment_status')) {
            $appointmentsQuery->where('status', $request->appointment_status);
        }

        // Type filter
        if ($request->filled('appointment_type')) {
            $appointmentsQuery->where('appointment_type', $request->appointment_type);
        }

        $appointments = $appointmentsQuery
            ->orderBy('appointment_date', 'desc')
            ->orderBy('appointment_time', 'desc')
            ->paginate($request->input('perPage', 20), ['*'], 'appointments_page')
            ->withQueryString();

        // Transform appointments data
        $appointments->getCollection()->transform(fn($a) => $this->transformAppointmentForList($a, true));

        return Inertia::render('Admin/Guidance/Index', [
            'cases' => GuidanceCase::with(['enrollment.student', 'assignedStaff'])->paginate($request->input('perPage', 20)),
            'appointments' => $appointments,
            'pendingAppointments' => $pendingAppointments,
            'filters' => $request->only(['appointment_search', 'appointment_status', 'appointment_type']),
            'dashboardStats' => [
                [
                    'title' => 'Total Appointments',
                    'value' => $totalAppointments,
                    'color' => 'blue',
                ],
                [
                    'title' => 'Pending Requests',
                    'value' => $pendingRequests,
                    'color' => 'yellow',
                ],
                [
                    'title' => 'Approved',
                    'value' => $approvedAppointments,
                    'color' => 'green',
                ],
                [
                    'title' => 'Completed',
                    'value' => $completedAppointments,
                    'color' => 'green',
                ],
            ],
        ]);
    }

    /**
     * Approve an appointment request.
     */
    public function approveAppointment(ApproveGuidanceAppointmentRequest $request, GuidanceAppointment $appointment)
    {
        try {
            if ($appointment->status !== 'pending') {
                return redirect()->back()
                    ->withErrors(['error' => 'Only pending appointments can be approved.']);
            }

            $appointment->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'admin_remarks' => $request->admin_remarks,
            ]);

            Log::info("Appointment approved: {$appointment->appointment_id} by user " . Auth::id());

            return redirect()->back()
                ->with('success', 'Appointment approved successfully.');
        } catch (\Exception $e) {
            Log::error("Failed to approve appointment: " . $e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Failed to approve appointment. Please try again.']);
        }
    }

    /**
     * Reject an appointment request.
     */
    public function rejectAppointment(RejectGuidanceAppointmentRequest $request, GuidanceAppointment $appointment)
    {
        try {
            if ($appointment->status !== 'pending') {
                return redirect()->back()
                    ->withErrors(['error' => 'Only pending appointments can be rejected.']);
            }

            $appointment->update([
                'status' => 'rejected',
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
                'admin_remarks' => $request->admin_remarks,
            ]);

            Log::info("Appointment rejected: {$appointment->appointment_id} by user " . Auth::id());

            return redirect()->back()
                ->with('success', 'Appointment rejected successfully.');
        } catch (\Exception $e) {
            Log::error("Failed to reject appointment: " . $e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Failed to reject appointment. Please try again.']);
        }
    }

    /**
     * Display the specified appointment.
     */
    public function showAppointment(GuidanceAppointment $appointment): Response
    {
        $appointment->load(['student', 'employee', 'approver', 'rejector']);

        $identity = $this->resolveAppointmentIdentity($appointment);
        $approverName = $this->resolveUserDisplayName($appointment->approver);
        $rejectorName = $this->resolveUserDisplayName($appointment->rejector);

        return Inertia::render('Admin/Guidance/ShowAppointment', [
            'appointment' => [
                'appointment_id' => $appointment->appointment_id,
                'student_name' => $identity['name'],
                'student_id' => $identity['id'],
                'appointment_date' => $appointment->appointment_date->format('Y-m-d'),
                'appointment_time' => $appointment->appointment_time->format('H:i'),
                'concern' => $appointment->concern,
                'appointment_type' => $appointment->appointment_type,
                'status' => $appointment->formatted_status,
                'status_color' => $appointment->status_color,
                'notes' => $appointment->notes,
                'admin_remarks' => $appointment->admin_remarks,
                'approved_at' => $appointment->approved_at ? $appointment->approved_at->format('Y-m-d H:i') : null,
                'rejected_at' => $appointment->rejected_at ? $appointment->rejected_at->format('Y-m-d H:i') : null,
                'approver_name' => $approverName,
                'rejector_name' => $rejectorName,
                'narrative_report' => $appointment->narrative_report,
                'narrative_report_file_url' => $appointment->narrative_report_file
                    ? Storage::url($appointment->narrative_report_file)
                    : null,
                'narrative_report_file_name' => $appointment->narrative_report_file
                    ? basename($appointment->narrative_report_file)
                    : null,
                'created_at' => $appointment->created_at->format('Y-m-d H:i'),
                'updated_at' => $appointment->updated_at->format('Y-m-d H:i'),
            ],
        ]);
    }

    /**
     * Update the specified appointment.
     */
    public function updateAppointment(Request $request, GuidanceAppointment $appointment)
    {
        $request->validate([
            'appointment_date' => ['sometimes', 'required', 'date'],
            'appointment_time' => ['sometimes', 'required', 'date_format:H:i'],
            'concern' => ['sometimes', 'required', 'string', 'max:1000'],
            'appointment_type' => ['sometimes', 'required', 'string', 'in:counseling,consultation,referral,other'],
            'status' => ['sometimes', 'required', 'string', 'in:pending,approved,rejected,completed,cancelled'],
            'notes' => ['nullable', 'string'],
            'narrative_report' => ['nullable', 'string'],
            'narrative_report_file' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
            'admin_remarks' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $data = $request->only([
                'appointment_date',
                'appointment_time',
                'concern',
                'appointment_type',
                'status',
                'notes',
                'admin_remarks',
                'narrative_report',
            ]);

            // Handle narrative report file upload
            if ($request->hasFile('narrative_report_file')) {
                // Delete old file if exists
                if ($appointment->narrative_report_file) {
                    Storage::delete($appointment->narrative_report_file);
                }
                $data['narrative_report_file'] = $request->file('narrative_report_file')
                    ->store('guidance-narratives', 'public');
            }

            // Handle narrative file removal
            if ($request->input('remove_narrative_file')) {
                if ($appointment->narrative_report_file) {
                    Storage::delete($appointment->narrative_report_file);
                }
                $data['narrative_report_file'] = null;
            }

            $appointment->update($data);

            Log::info("Appointment updated: {$appointment->appointment_id} by user " . Auth::id());

            return redirect()->back()
                ->with('success', 'Appointment updated successfully.');
        } catch (\Exception $e) {
            Log::error("Failed to update appointment: " . $e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Failed to update appointment. Please try again.']);
        }
    }

    /**
     * Export guidance cases to PDF.
     */
    public function exportPdf(Request $request)
    {
        $query = GuidanceCase::with(['enrollment.student', 'assignedStaff']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('case_no', 'like', "%{$search}%")
                    ->orWhere('concern', 'like', "%{$search}%")
                    ->orWhereHas('enrollment.student', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('student_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('case_type')) {
            $query->where('case_type', $request->case_type);
        }

        if ($request->filled('assigned_staff_id')) {
            $query->where('assigned_staff_id', $request->assigned_staff_id);
        }

        $cases = $query->orderBy('created_at', 'desc')->get();

        $headers = ['Case No', 'Student', 'Case Type', 'Concern', 'Status', 'Assigned Staff', 'Created At'];
        $rows = $cases->map(fn($c) => [
            $c->case_no,
            $c->enrollment?->student
            ? $c->enrollment->student->first_name . ' ' . $c->enrollment->student->last_name
            : '—',
            $c->case_type,
            \Illuminate\Support\Str::limit($c->concern, 50),
            $c->status,
            $c->assignedStaff?->full_name ?? '—',
            $c->created_at->format('Y-m-d'),
        ])->toArray();

        // Build human-readable filter labels
        $filterLabels = [];
        if ($request->filled('search')) {
            $filterLabels['Search'] = $request->search;
        }
        if ($request->filled('status')) {
            $filterLabels['Status'] = ucfirst($request->status);
        }
        if ($request->filled('case_type')) {
            $filterLabels['Case Type'] = ucfirst($request->case_type);
        }
        if ($request->filled('assigned_staff_id')) {
            $staff = Employee::find($request->assigned_staff_id);
            $filterLabels['Assigned Staff'] = $staff ? $staff->full_name : $request->assigned_staff_id;
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.pdf-table', [
            'title' => 'Guidance Cases Report',
            'date' => now()->format('F j, Y g:i A'),
            'headers' => $headers,
            'rows' => $rows,
            'filters' => $filterLabels,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('guidance_cases_export_' . date('Y-m-d_His') . '.pdf');
    }

    /**
     * Update only the status of a guidance appointment (from progress bar).
     */
    public function updateAppointmentStatus(Request $request, GuidanceAppointment $appointment)
    {
        $request->validate([
            'status' => 'required|string|in:pending,approved,completed,rejected,cancelled',
        ]);

        $newStatus = $request->input('status');

        if ($appointment->status === $newStatus) {
            return redirect()->back();
        }

        try {
            $updateData = ['status' => $newStatus];

            if ($newStatus === 'approved' && !$appointment->approved_at) {
                $updateData['approved_at'] = now();
                $updateData['approved_by'] = Auth::id();
            }
            if ($newStatus === 'rejected' && !$appointment->rejected_at) {
                $updateData['rejected_at'] = now();
                $updateData['rejected_by'] = Auth::id();
            }

            $appointment->update($updateData);

            Log::info("Appointment {$appointment->appointment_id} status changed to {$newStatus} by user " . Auth::id());

            return redirect()->back()
                ->with('success', "Appointment status updated to {$newStatus}.");
        } catch (\Exception $e) {
            Log::error("Failed to update appointment status: " . $e->getMessage());

            return redirect()->back()
                ->withErrors(['error' => 'Failed to update appointment status.']);
        }
    }

    // ─── Private Helpers ─────────────────────────────────────────

    /**
     * Resolve the display name and ID for an appointment's requester (student or employee).
     *
     * @return array{name: string, id: string}
     */
    private function resolveAppointmentIdentity(GuidanceAppointment $appointment): array
    {
        if ($appointment->student) {
            return ['name' => $appointment->student->full_name, 'id' => $appointment->student->student_number];
        }

        if ($appointment->employee) {
            return ['name' => $appointment->employee->full_name, 'id' => $appointment->employee->employee_number];
        }

        // Fallback: try to get student directly if relationship didn't load
        if ($appointment->student_number) {
            $student = Student::where('student_number', $appointment->student_number)->first();
            if ($student) {
                return ['name' => $student->full_name, 'id' => $student->student_number];
            }
        }

        // Fallback: try to get employee directly if relationship didn't load
        if ($appointment->employee_id) {
            $employee = Employee::where('employee_id', $appointment->employee_id)->first();
            if ($employee) {
                return ['name' => $employee->full_name, 'id' => $employee->employee_number];
            }
        }

        return ['name' => 'Unknown Student', 'id' => 'N/A'];
    }

    /**
     * Resolve the display name for a user (approver or rejector).
     */
    private function resolveUserDisplayName(?User $user): ?string
    {
        if (!$user) {
            return null;
        }

        if ($user->student) {
            return $user->student->full_name;
        }

        $employee = Employee::where('email', $user->email)->first();
        return $employee ? $employee->full_name : $user->email;
    }

    /**
     * Transform a GuidanceAppointment into a list-ready array.
     *
     * @param bool $includeStatusFields Whether to include formatted_status, status_color, etc.
     */
    private function transformAppointmentForList(GuidanceAppointment $appointment, bool $includeStatusFields = false): array
    {
        $identity = $this->resolveAppointmentIdentity($appointment);
        $approverName = $this->resolveUserDisplayName($appointment->approver);
        $rejectorName = $this->resolveUserDisplayName($appointment->rejector);

        $timeValue = $appointment->appointment_time;
        $formattedTime = $timeValue instanceof \DateTimeInterface
            ? $timeValue->format('H:i')
            : substr((string) $timeValue, 0, 5);

        $data = [
            'appointment_id' => $appointment->appointment_id,
            'student_name' => $identity['name'],
            'student_id' => $identity['id'],
            'appointment_date' => $appointment->appointment_date->format('Y-m-d'),
            'appointment_time' => $formattedTime,
            'concern' => $appointment->concern,
            'appointment_type' => $appointment->appointment_type,
            'created_at' => $appointment->created_at->format('Y-m-d H:i'),
            'admin_remarks' => $appointment->admin_remarks,
            'approved_at' => $appointment->approved_at ? $appointment->approved_at->format('Y-m-d H:i') : null,
            'rejected_at' => $appointment->rejected_at ? $appointment->rejected_at->format('Y-m-d H:i') : null,
            'approver_name' => $approverName,
            'rejector_name' => $rejectorName,
        ];

        if ($includeStatusFields) {
            $data['status'] = $appointment->formatted_status;
            $data['status_color'] = $appointment->status_color;
        }

        return $data;
    }
}
