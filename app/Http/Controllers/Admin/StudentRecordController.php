<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudentRequest;
use App\Http\Requests\Admin\UpdateStudentRequest;
use App\Http\Requests\Admin\ImportStudentsRequest;
use App\Models\Student;
use App\Models\EnrolledStudent;
use App\Models\Course;
use App\Models\Section;
use App\Models\AcademicCalendar;
use App\Services\StudentImportService;
use App\Services\StudentAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentRecordController extends Controller
{
    protected StudentImportService $importService;
    protected StudentAccountService $accountService;

    public function __construct(StudentImportService $importService, StudentAccountService $accountService)
    {
        $this->importService = $importService;
        $this->accountService = $accountService;
    }

    /**
     * Display a listing of student records.
     */
    public function index(Request $request): Response
    {
        // Only use the ACTIVE academic calendar (locked term)
        $activeAcademicCalendar = AcademicCalendar::active()->first();

        if (!$activeAcademicCalendar) {
            return Inertia::render('Admin/Students/Index', [
                'students' => [],
                'filters' => $request->only(['search', 'year_level', 'course_id', 'status']),
                'courses' => Course::orderBy('course_name')->get()->map(fn($c) => [
                    'course_id' => $c->course_id,
                    'course_code' => $c->course_code,
                    'course_name' => $c->course_name,
                ]),
                'activeTerm' => null,
                'dashboardStats' => [],
                'error' => 'No active academic term. Please set an active term in Settings.',
            ]);
        }

        $query = EnrolledStudent::with(['student.user', 'course', 'section'])
            ->where('acad_id', $activeAcademicCalendar->calendar_id);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('student_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('middle_name', 'like', "%{$search}%");
            });
        }

        // Course filter
        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('enrollment_status', $request->status);
        }

        // Year level filter (from enrolled_students table)
        if ($request->filled('year_level')) {
            $query->where('year_level', $request->year_level);
        }

        $students = $query->orderBy('enrollment_id', 'desc')
            ->paginate(15)
            ->through(function ($enrollment) {
                $student = $enrollment->student;
                return [
                    'enrollment_id' => $enrollment->enrollment_id,
                    'student_number' => $enrollment->student_number,
                    'name' => $student?->full_name ?? 'N/A',
                    'year_level' => $enrollment->year_level ?? 'N/A',
                    'section_name' => $enrollment->section?->section_name ?? 'N/A',
                    'course_name' => $enrollment->course->course_name,
                    'status' => $enrollment->enrollment_status,
                    'has_account' => $student?->hasAccount() ?? false,
                    'account_email' => $student?->user?->email ?? null,
                ];
            });

        // Get dashboard statistics for the selected term
        $totalStudents = EnrolledStudent::where('acad_id', $activeAcademicCalendar->calendar_id)->count();
        $activeStudents = EnrolledStudent::where('acad_id', $activeAcademicCalendar->calendar_id)
            ->where('enrollment_status', 'active')
            ->count();
        $inactiveStudents = EnrolledStudent::where('acad_id', $activeAcademicCalendar->calendar_id)
            ->where('enrollment_status', 'inactive')
            ->count();

        return Inertia::render('Admin/Students/Index', [
            'students' => $students,
            'filters' => $request->only(['search', 'year_level', 'course_id', 'status']),
            'courses' => Course::orderBy('course_name')->get()->map(fn($c) => [
                'course_id' => $c->course_id,
                'course_code' => $c->course_code,
                'course_name' => $c->course_name,
            ]),
            'activeTerm' => [
                'calendar_id' => $activeAcademicCalendar->calendar_id,
                'academic_year' => $activeAcademicCalendar->academic_year,
                'semester' => $activeAcademicCalendar->semester,
                'display_label' => $activeAcademicCalendar->display_label,
            ],
            'dashboardStats' => [
                [
                    'title' => 'Total Students',
                    'value' => $totalStudents,
                    'color' => 'blue',
                ],
                [
                    'title' => 'Active Students',
                    'value' => $activeStudents,
                    'color' => 'green',
                ],
                [
                    'title' => 'Inactive Students',
                    'value' => $inactiveStudents,
                    'color' => 'gray',
                ],
            ],
        ]);
    }

    /**
     * Store a newly created student and enrollment.
     */
    public function store(StoreStudentRequest $request)
    {
        $academicCalendar = AcademicCalendar::find($request->acad_id);

        if (!$academicCalendar) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid academic calendar selected.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Check if student already exists by student_number
            $student = Student::where('student_number', $request->student_number)->first();

            if (!$student) {
                // Create new student
                $student = Student::create([
                    'student_number' => $request->student_number,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'middle_name' => $request->middle_name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'birth_date' => $request->birth_date,
                    'address' => $request->address,
                    'status' => 'active',
                ]);
            } else {
                // Update existing student's basic info
                $student->update([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'middle_name' => $request->middle_name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'birth_date' => $request->birth_date,
                    'address' => $request->address,
                ]);
            }

            // Check if enrollment already exists for this student and term
            $existingEnrollment = EnrolledStudent::where('student_number', $student->student_number)
                ->where('acad_id', $academicCalendar->calendar_id)
                ->first();

            if ($existingEnrollment) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Student is already enrolled in this term.',
                ], 422);
            }

            // Create enrollment
            $enrollment = EnrolledStudent::create([
                'student_number' => $student->student_number,
                'acad_id' => $academicCalendar->calendar_id,
                'course_id' => $request->course_id,
                'section_id' => $request->section_id,
                'year_level' => $request->year_level,
                'academic_year' => $academicCalendar->academic_year,
                'enrollment_status' => 'active',
                'enrollment_date' => now(),
            ]);

            DB::commit();

            Log::info("Student created/enrolled: {$student->student_number} for term {$academicCalendar->calendar_id} by user {$request->user()->user_id}");

            // Optionally auto-create account if enabled
            $accountCreated = false;
            if (config('app.auto_create_student_accounts', false)) {
                try {
                    $this->accountService->createAccountFromEnrollment($enrollment);
                    $accountCreated = true;
                } catch (\Exception $e) {
                    Log::warning("Auto-create account failed for student {$student->student_number}: " . $e->getMessage());
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Student enrolled successfully.' . ($accountCreated ? ' Account created automatically.' : ''),
                'student' => $student->load(['enrollments', 'user']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create/enroll student: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to enroll student: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to enroll student. Please try again.',
            ], 500);
        }
    }

    /**
     * Update the specified student.
     */
    public function update(UpdateStudentRequest $request, Student $student)
    {
        try {
            $student->update($request->validated());

            Log::info("Student updated: {$student->student_number} by user {$request->user()->user_id}");

            return response()->json([
                'success' => true,
                'message' => 'Student updated successfully.',
                'student' => $student->load(['enrollments']),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update student: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update student. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to update student. Please try again.',
            ], 500);
        }
    }

    /**
     * Update enrollment status.
     */
    public function updateStatus(Request $request, EnrolledStudent $enrollment)
    {
        $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        try {
            $enrollment->update([
                'enrollment_status' => $request->status,
            ]);

            Log::info("Enrollment status updated: enrollment_id {$enrollment->enrollment_id} to {$request->status} by user {$request->user()->user_id}");

            return redirect()->back()
                ->with('success', 'Enrollment status updated successfully.');
        } catch (\Exception $e) {
            Log::error("Failed to update enrollment status: " . $e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Failed to update enrollment status. Please try again.',
            ]);
        }
    }

    /**
     * Handle student import.
     */
    public function import(ImportStudentsRequest $request)
    {
        $academicCalendar = AcademicCalendar::find($request->acad_id);

        if (!$academicCalendar) {
            return redirect()->back()->withErrors([
                'error' => 'Invalid academic calendar selected.',
            ]);
        }

        try {
            $file = $request->file('file');
            $filePath = $file->storeAs('imports', 'students_' . time() . '.' . $file->getClientOriginalExtension());

            $result = $this->importService->import(
                storage_path('app/' . $filePath),
                $academicCalendar->calendar_id
            );

            Log::info("Student import completed by user {$request->user()->user_id} for term {$academicCalendar->calendar_id}");

            // Clean up file
            if (file_exists(storage_path('app/' . $filePath))) {
                unlink(storage_path('app/' . $filePath));
            }

            return redirect()->route('admin.students.index', ['acad_id' => $academicCalendar->calendar_id])
                ->with('import_result', $result);
        } catch (\Exception $e) {
            Log::error("Student import failed: " . $e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Import failed: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Export students to CSV.
     */
    public function export(Request $request): StreamedResponse
    {
        // Export only from the active term (locked)
        $academicCalendar = AcademicCalendar::active()->first();

        if (!$academicCalendar) {
            abort(404, 'No active academic calendar found.');
        }

        $query = EnrolledStudent::with(['student', 'course', 'section'])
            ->where('acad_id', $academicCalendar->calendar_id);

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('student_number', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('status')) {
            $query->where('enrollment_status', $request->status);
        }

        if ($request->filled('year_level')) {
            $query->where('year_level', $request->year_level);
        }

        $enrollments = $query->get();

        $filename = 'students_export_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($enrollments) {
            $file = fopen('php://output', 'w');

            // Header
            fputcsv($file, ['Student ID', 'Name', 'Year Level', 'Section', 'Course', 'Status']);

            // Data
            foreach ($enrollments as $enrollment) {
                fputcsv($file, [
                    $enrollment->student->student_number,
                    $enrollment->student->full_name,
                    $enrollment->year_level ?? 'N/A',
                    $enrollment->section?->section_name ?? 'N/A',
                    $enrollment->course->course_name,
                    $enrollment->enrollment_status,
                ]);
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Get student details (for edit modal).
     */
    public function show(Student $student)
    {
        $student->load(['enrollments.academicCalendar', 'enrollments.course', 'enrollments.section']);

        return response()->json([
            'student' => $student,
            'current_enrollment' => $student->currentEnrollment(),
        ]);
    }

    /**
     * Display the student profile page.
     */
    public function profile(Student $student): Response
    {
        $student->load([
            'enrollments.academicCalendar',
            'enrollments.course',
            'enrollments.section',
            'profile',
            'educationalBackground',
            'familyInfo',
            'emergencyContact',
            'user.roles',
        ]);

        // Transform enrollments for display
        $enrollmentHistory = $student->enrollments->map(function ($enrollment) {
            return [
                'enrollment_id' => $enrollment->enrollment_id,
                'academic_year' => $enrollment->academicCalendar?->academic_year ?? $enrollment->academic_year,
                'semester' => $enrollment->academicCalendar?->semester ?? $enrollment->semester,
                'term_label' => $enrollment->academicCalendar?->display_label ?? 'Unknown Term',
                'course_name' => $enrollment->course?->course_name ?? 'N/A',
                'course_code' => $enrollment->course?->course_code ?? 'N/A',
                'section_name' => $enrollment->section?->section_name ?? 'N/A',
                'year_level' => $enrollment->year_level ?? 'N/A',
                'enrollment_status' => $enrollment->enrollment_status,
                'enrollment_date' => $enrollment->enrollment_date?->format('M d, Y'),
            ];
        })->sortByDesc('enrollment_id')->values();

        // Get profile data
        $profile = $student->profile;
        $educationalBackground = $student->educationalBackground;
        $familyInfo = $student->familyInfo;
        $emergencyContact = $student->emergencyContact;
        $userAccount = $student->user;

        return Inertia::render('Admin/Students/Profile', [
            'student' => [
                'student_number' => $student->student_number,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'middle_name' => $student->middle_name,
                'full_name' => $student->full_name,
                'email' => $student->email,
                'phone' => $student->phone,
                'birth_date' => $student->birth_date?->format('Y-m-d'),
                'address' => $student->address,
                'status' => $student->status,
                'created_at' => $student->created_at?->format('M d, Y'),
                'has_account' => $student->hasAccount(),
            ],
            'account' => $userAccount ? [
                'email' => $userAccount->email,
                'status' => $userAccount->status,
                'created_at' => $userAccount->created_at?->format('M d, Y g:i A'),
                'roles' => $userAccount->roles->pluck('role_name')->toArray(),
            ] : null,
            'profile' => $profile ? [
                'birth_place' => $profile->birth_place,
                'gender' => $profile->gender,
                'citizenship' => $profile->citizenship,
                'civil_status' => $profile->civil_status,
                'spouse_name' => $profile->spouse_name,
                'is_single_parent' => $profile->is_single_parent,
                'has_disability' => $profile->has_disability,
                'disability_details' => $profile->disability_details,
                'is_employed' => $profile->is_employed,
                'company_name' => $profile->company_name,
                'profile_status' => $profile->profile_status,
                'submitted_at' => $profile->submitted_at?->format('M d, Y g:i A'),
                'reviewed_at' => $profile->reviewed_at?->format('M d, Y g:i A'),
            ] : null,
            'educationalBackground' => $educationalBackground ? [
                'elementary_school' => $educationalBackground->elementary_school,
                'elementary_address' => $educationalBackground->elementary_address,
                'elementary_graduated' => $educationalBackground->elementary_graduated?->format('Y-m-d'),
                'senior_high_school' => $educationalBackground->senior_high_school,
                'senior_high_strand' => $educationalBackground->senior_high_strand,
                'senior_high_address' => $educationalBackground->senior_high_address,
                'senior_high_graduated' => $educationalBackground->senior_high_graduated?->format('Y-m-d'),
                'honors_received' => $educationalBackground->honors_received,
            ] : null,
            'familyInfo' => $familyInfo ? [
                'father_last_name' => $familyInfo->father_last_name,
                'father_first_name' => $familyInfo->father_first_name,
                'father_middle_initial' => $familyInfo->father_middle_initial,
                'father_occupation' => $familyInfo->father_occupation,
                'father_full_name' => $familyInfo->father_full_name,
                'mother_maiden_last_name' => $familyInfo->mother_maiden_last_name,
                'mother_first_name' => $familyInfo->mother_first_name,
                'mother_middle_initial' => $familyInfo->mother_middle_initial,
                'mother_occupation' => $familyInfo->mother_occupation,
                'mother_full_name' => $familyInfo->mother_full_name,
            ] : null,
            'emergencyContact' => $emergencyContact ? [
                'contact_name' => $emergencyContact->contact_name,
                'relationship' => $emergencyContact->relationship,
                'contact_number' => $emergencyContact->contact_number,
                'contact_address' => $emergencyContact->contact_address,
            ] : null,
            'enrollmentHistory' => $enrollmentHistory,
        ]);
    }

    /**
     * Get sections for a course (AJAX).
     */
    public function getSections(Request $request)
    {
        $request->validate([
            'course_id' => ['required', 'exists:courses,course_id'],
            'year_level' => ['nullable', 'string'],
        ]);

        $query = Section::where('course_id', $request->course_id);

        // Optionally filter by year_level if provided
        if ($request->filled('year_level')) {
            $query->where('year_level', $request->year_level);
        }

        $sections = $query->orderBy('section_name')
            ->get()
            ->map(fn($s) => [
                'section_id' => $s->section_id,
                'section_name' => $s->section_name,
                'section_code' => $s->section_code,
                'year_level' => $s->year_level,
            ]);

        return response()->json($sections);
    }

    /**
     * Get academic calendars list (AJAX).
     */
    public function getAcademicCalendars()
    {
        $calendars = AcademicCalendar::orderBy('start_date', 'desc')
            ->get()
            ->map(fn($c) => [
                'calendar_id' => $c->calendar_id,
                'academic_year' => $c->academic_year,
                'semester' => $c->semester,
                'status' => $c->status,
                'display_label' => $c->display_label,
            ]);

        return response()->json($calendars);
    }

    /**
     * Create user account for a student.
     */
    public function createAccount(Request $request, Student $student)
    {
        $request->validate([
            'email' => ['nullable', 'email', 'unique:users,email'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        try {
            $email = $request->input('email');
            $password = $request->input('password');

            $user = $this->accountService->createAccount($student, $email, $password);

            // Get generated email if not provided
            if (!$email) {
                $email = $this->accountService->generateEmail($student->student_number);
            }

            return response()->json([
                'success' => true,
                'message' => 'Account created successfully.',
                'account' => [
                    'email' => $user->email,
                    'password' => $password ?? $this->accountService->generateDefaultPassword($student->student_number),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Bulk create accounts for multiple students.
     */
    public function bulkCreateAccounts(Request $request)
    {
        $request->validate([
            'student_numbers' => ['required', 'array'],
            'student_numbers.*' => ['required', 'exists:students,student_number'],
        ]);

        try {
            $result = $this->accountService->bulkCreateAccounts($request->student_numbers);

            return response()->json([
                'success' => true,
                'message' => "Bulk account creation completed. Created: {$result['created']}, Skipped: {$result['skipped']}, Failed: {$result['failed']}",
                'result' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk account creation failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a student's user account.
     */
    public function deleteAccount(Request $request, Student $student)
    {
        try {
            $this->accountService->deleteAccount($student);

            return response()->json([
                'success' => true,
                'message' => 'Account deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account: ' . $e->getMessage(),
            ], 500);
        }
    }
}
