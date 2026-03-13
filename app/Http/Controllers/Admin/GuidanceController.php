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

        $sortBy = in_array($request->input('sort_by'), ['appointment_date', 'appointment_time', 'type', 'status']) ? $request->input('sort_by') : 'appointment_date';
        $sortDir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';

        $appointments = $appointmentsQuery
            ->orderBy($sortBy, $sortDir)
            ->paginate($request->input('perPage', 20), ['*'], 'appointments_page')
            ->withQueryString();

        // Transform appointments data
        $appointments->getCollection()->transform(fn($a) => $this->transformAppointmentForList($a, true));

        return Inertia::render('Admin/Guidance/Index', [
            'cases' => GuidanceCase::with(['enrollment.student', 'assignedStaff'])->paginate($request->input('perPage', 20)),
            'appointments' => $appointments,
            'pendingAppointments' => $pendingAppointments,
            'filters' => $request->only(['appointment_search', 'appointment_status', 'appointment_type', 'sort_by', 'sort_dir']),
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
    public function updateAppointmentStatus(Request $request, GuidanceAppointment $appointment)
    {
        $request->validate([
            'status' => 'required|string|in:pending,approved,completed,rejected,cancelled',
        ]);

        $newStatus = strtolower($request->input('status'));

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
