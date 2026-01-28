<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreGuidanceAppointmentRequest;
use App\Models\GuidanceAppointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class GuidanceController extends Controller
{
    /**
     * Display the appointment request form and student's appointment history.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $studentNumber = $user->student_number;

        // Get student's appointment history
        $appointmentsQuery = GuidanceAppointment::with(['approver', 'rejector'])
            ->where('student_number', $studentNumber);

        // Filter by status if provided
        if ($request->filled('status')) {
            $appointmentsQuery->where('status', $request->status);
        }

        $appointments = $appointmentsQuery
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        // Transform appointments data
        $appointments->getCollection()->transform(function ($appointment) {
            return [
                'appointment_id' => $appointment->appointment_id,
                'appointment_date' => $appointment->appointment_date->format('Y-m-d'),
                'appointment_time' => substr($appointment->appointment_time, 0, 5), // Extract HH:mm from TIME format
                'concern' => $appointment->concern,
                'appointment_type' => $appointment->appointment_type,
                'status' => $appointment->formatted_status,
                'status_color' => $appointment->status_color,
                'admin_remarks' => $appointment->admin_remarks,
                'approved_at' => $appointment->approved_at ? $appointment->approved_at->format('Y-m-d H:i') : null,
                'rejected_at' => $appointment->rejected_at ? $appointment->rejected_at->format('Y-m-d H:i') : null,
                'approver_name' => $appointment->approver ? $appointment->approver->email : null,
                'rejector_name' => $appointment->rejector ? $appointment->rejector->email : null,
                'created_at' => $appointment->created_at->format('Y-m-d H:i'),
            ];
        });

        return Inertia::render('Student/Guidance/Index', [
            'appointments' => $appointments,
            'filters' => $request->only(['status']),
        ]);
    }

    /**
     * Store a new appointment request.
     */
    public function store(StoreGuidanceAppointmentRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();
        
        // Get student_number from user, or try to find student by email
        $studentNumber = $user->student_number;
        if (!$studentNumber) {
            // Try to find student by matching user's email
            $student = \App\Models\Student::where('email', $user->email)->first();
            if ($student) {
                $studentNumber = $student->student_number;
                // Update the user record with the student_number
                $user->update(['student_number' => $studentNumber]);
            }
        }
        
        // Validate that we have a student_number
        if (!$studentNumber) {
            return redirect()->back()
                ->withErrors(['error' => 'Unable to identify your student account. Please contact the administrator to link your account to a student record.']);
        }
        
        $data['student_number'] = $studentNumber;
        $data['status'] = 'pending';

        GuidanceAppointment::create($data);

        return redirect()->route('student.guidance.index')
            ->with('success', 'Appointment request submitted successfully. Waiting for admin approval.');
    }

    /**
     * Display the specified appointment details.
     */
    public function show(GuidanceAppointment $appointment): Response
    {
        $user = Auth::user();
        
        // Ensure the appointment belongs to the authenticated student
        if ($appointment->student_number !== $user->student_number) {
            abort(403, 'Unauthorized access.');
        }

        $appointment->load(['approver', 'rejector', 'student']);

        return Inertia::render('Student/Guidance/Show', [
            'appointment' => [
                'appointment_id' => $appointment->appointment_id,
                'appointment_date' => $appointment->appointment_date->format('Y-m-d'),
                'appointment_time' => substr($appointment->appointment_time, 0, 5), // Extract HH:mm from TIME format
                'concern' => $appointment->concern,
                'appointment_type' => $appointment->appointment_type,
                'status' => $appointment->formatted_status,
                'status_color' => $appointment->status_color,
                'notes' => $appointment->notes,
                'admin_remarks' => $appointment->admin_remarks,
                'approved_at' => $appointment->approved_at ? $appointment->approved_at->format('Y-m-d H:i') : null,
                'rejected_at' => $appointment->rejected_at ? $appointment->rejected_at->format('Y-m-d H:i') : null,
                'approver_name' => $appointment->approver ? $appointment->approver->email : null,
                'rejector_name' => $appointment->rejector ? $appointment->rejector->email : null,
                'created_at' => $appointment->created_at->format('Y-m-d H:i'),
                'updated_at' => $appointment->updated_at->format('Y-m-d H:i'),
            ],
        ]);
    }
}
