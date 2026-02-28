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
        $activeAcademicCalendar = AcademicCalendar::active()->first();
        $statusFilter = $request->input('status', 'enrolled'); // default to enrolled

        if (!$activeAcademicCalendar && $statusFilter === 'enrolled') {
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

        // For "enrolled" status, show only active term enrollments
        // For "graduated", "dropped", or "all" — show across all terms (most recent enrollment)
        $isActiveTermOnly = ($statusFilter === 'enrolled');

        if ($isActiveTermOnly && $activeAcademicCalendar) {
            // Current behavior: show enrollments from active term
            $query = EnrolledStudent::with(['student.user', 'course', 'section'])
                ->where('acad_id', $activeAcademicCalendar->calendar_id);

            // Filter by student global status
            $query->whereHas('student', function ($q) {
                $q->where('status', 'enrolled');
            });
        } else {
            // Cross-term: get the most recent enrollment per student
            // Subquery to get the latest enrollment_id per student
            $latestEnrollmentIds = EnrolledStudent::selectRaw('MAX(enrollment_id) as enrollment_id')
                ->groupBy('student_number')
                ->pluck('enrollment_id');

            $query = EnrolledStudent::with(['student.user', 'course', 'section', 'academicCalendar'])
                ->whereIn('enrollment_id', $latestEnrollmentIds);

            // Filter by student global status (graduated, dropped, or all)
            if ($statusFilter && $statusFilter !== 'all') {
                $query->whereHas('student', function ($q) use ($statusFilter) {
                    $q->where('status', $statusFilter);
                });
            }
        }

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

        // Year level filter
        if ($request->filled('year_level')) {
            $query->where('year_level', $request->year_level);
        }

        $students = $query->orderBy('enrollment_id', 'desc')
            ->paginate($request->input('perPage', 20))
            ->through(function ($enrollment) use ($isActiveTermOnly) {
                $student = $enrollment->student;
                $data = [
                    'enrollment_id' => $enrollment->enrollment_id,
                    'student_number' => $enrollment->student_number,
                    'name' => $student?->full_name ?? 'N/A',
                    'year_level' => $enrollment->year_level ?? 'N/A',
                    'section_name' => $enrollment->section?->section_name ?? 'N/A',
                    'course_name' => $enrollment->course?->course_name ?? 'N/A',
                    'status' => $student?->status ?? 'enrolled',
                    'has_account' => $student?->hasAccount() ?? false,
                    'account_email' => $student?->user?->email ?? null,
                ];

                // For cross-term views, show which term the enrollment is from
                if (!$isActiveTermOnly) {
                    $data['term_label'] = $enrollment->academicCalendar?->display_label ?? 'N/A';
                }

                return $data;
            });

        // Dashboard statistics — only for active term
        if ($activeAcademicCalendar) {
            $termEnrollments = EnrolledStudent::where('acad_id', $activeAcademicCalendar->calendar_id);
            $totalStudents = (clone $termEnrollments)->count();
            $enrolledStudents = (clone $termEnrollments)->whereHas('student', fn($q) => $q->where('status', 'enrolled'))->count();
            $graduatedStudents = (clone $termEnrollments)->whereHas('student', fn($q) => $q->where('status', 'graduated'))->count();
            $droppedStudents = (clone $termEnrollments)->whereHas('student', fn($q) => $q->where('status', 'dropped'))->count();
        } else {
            $totalStudents = 0;
            $enrolledStudents = 0;
            $graduatedStudents = 0;
            $droppedStudents = 0;
        }

        return Inertia::render('Admin/Students/Index', [
            'students' => $students,
            'filters' => $request->only(['search', 'year_level', 'course_id', 'status']),
            'courses' => Course::orderBy('course_name')->get()->map(fn($c) => [
                'course_id' => $c->course_id,
                'course_code' => $c->course_code,
                'course_name' => $c->course_name,
            ]),
            'activeTerm' => $activeAcademicCalendar ? [
                'calendar_id' => $activeAcademicCalendar->calendar_id,
                'academic_year' => $activeAcademicCalendar->academic_year,
                'semester' => $activeAcademicCalendar->semester,
                'display_label' => $activeAcademicCalendar->display_label,
            ] : null,
            'dashboardStats' => [
                [
                    'title' => 'Total Students',
                    'value' => $totalStudents,
                    'color' => 'blue',
                ],
                [
                    'title' => 'Enrolled',
                    'value' => $enrolledStudents,
                    'color' => 'green',
                ],
                [
                    'title' => 'Graduated',
                    'value' => $graduatedStudents,
                    'color' => 'indigo',
                ],
                [
                    'title' => 'Dropped',
                    'value' => $droppedStudents,
                    'color' => 'red',
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
                    'status' => 'enrolled',
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

            // Resolve section from text input
            $sectionId = null;
            if ($request->filled('section') && $request->filled('course_id')) {
                $section = Section::firstOrCreate(
                    [
                        'section_name' => strtoupper(trim($request->section)),
                        'course_id' => $request->course_id,
                    ],
                    [
                        'section_code' => strtoupper(trim($request->section)),
                        'year_level' => $request->year_level,
                    ]
                );
                $sectionId = $section->section_id;
            }

            // Create enrollment
            $enrollment = EnrolledStudent::create([
                'student_number' => $student->student_number,
                'acad_id' => $academicCalendar->calendar_id,
                'course_id' => $request->course_id,
                'section_id' => $sectionId,
                'year_level' => $request->year_level,
                'academic_year' => $academicCalendar->academic_year,
                'enrollment_status' => 'enrolled',
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
     * Update student global status.
     */
    public function updateStatus(Request $request, Student $student)
    {
        $request->validate([
            'status' => ['required', 'in:enrolled,graduated,dropped'],
        ]);

        try {
            $student->update([
                'status' => $request->status,
            ]);

            Log::info("Student status updated: {$student->student_number} to {$request->status} by user {$request->user()->user_id}");

            return redirect()->back()
                ->with('success', 'Student status updated successfully.');
        } catch (\Exception $e) {
            Log::error("Failed to update student status: " . $e->getMessage());

            return redirect()->back()->withErrors([
                'error' => 'Failed to update student status. Please try again.',
            ]);
        }
    }

    /**
     * Bulk update student status.
     */
    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'student_numbers' => ['required', 'array'],
            'student_numbers.*' => ['required', 'exists:students,student_number'],
            'status' => ['required', 'in:enrolled,graduated,dropped'],
        ]);

        try {
            $count = Student::whereIn('student_number', $request->student_numbers)
                ->update(['status' => $request->status]);

            Log::info("Bulk student status update: {$count} students to {$request->status} by user {$request->user()->user_id}");

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$count} student(s) to {$request->status}.",
                'count' => $count,
            ]);
        } catch (\Exception $e) {
            Log::error("Bulk student status update failed: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update student statuses. Please try again.',
            ], 500);
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

            $absolutePath = \Illuminate\Support\Facades\Storage::path($filePath);

            $result = $this->importService->import(
                $absolutePath,
                $academicCalendar->calendar_id
            );

            Log::info("Student import completed by user {$request->user()->user_id} for term {$academicCalendar->calendar_id}");

            // Clean up file
            \Illuminate\Support\Facades\Storage::delete($filePath);

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
        $statusFilter = $request->input('status', 'enrolled');
        $academicCalendar = AcademicCalendar::active()->first();
        $isActiveTermOnly = ($statusFilter === 'enrolled');

        if ($isActiveTermOnly) {
            if (!$academicCalendar) {
                abort(404, 'No active academic calendar found.');
            }
            $query = EnrolledStudent::with(['student', 'course', 'section'])
                ->where('acad_id', $academicCalendar->calendar_id);
            $query->whereHas('student', function ($q) {
                $q->where('status', 'enrolled');
            });
        } else {
            $latestEnrollmentIds = EnrolledStudent::selectRaw('MAX(enrollment_id) as enrollment_id')
                ->groupBy('student_number')
                ->pluck('enrollment_id');
            $query = EnrolledStudent::with(['student', 'course', 'section'])
                ->whereIn('enrollment_id', $latestEnrollmentIds);
            if ($statusFilter && $statusFilter !== 'all') {
                $query->whereHas('student', function ($q) use ($statusFilter) {
                    $q->where('status', $statusFilter);
                });
            }
        }

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

        if ($request->filled('year_level')) {
            $query->where('year_level', $request->year_level);
        }

        $enrollments = $query->get();

        $filename = 'students_export_' . date('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($enrollments) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Student ID', 'Name', 'Year Level', 'Section', 'Course', 'Status']);
            foreach ($enrollments as $enrollment) {
                fputcsv($file, [
                    $enrollment->student?->student_number ?? 'N/A',
                    $enrollment->student?->full_name ?? 'N/A',
                    $enrollment->year_level ?? 'N/A',
                    $enrollment->section?->section_name ?? 'N/A',
                    $enrollment->course?->course_name ?? 'N/A',
                    $enrollment->student?->status ?? 'N/A',
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

    /**
     * Export students to PDF.
     */
    public function exportPdf(Request $request)
    {
        $statusFilter = $request->input('status', 'enrolled');
        $academicCalendar = AcademicCalendar::active()->first();
        $isActiveTermOnly = ($statusFilter === 'enrolled');

        if ($isActiveTermOnly) {
            if (!$academicCalendar) {
                abort(404, 'No active academic calendar found.');
            }
            $query = EnrolledStudent::with(['student', 'course', 'section'])
                ->where('acad_id', $academicCalendar->calendar_id);
            $query->whereHas('student', function ($q) {
                $q->where('status', 'enrolled');
            });
        } else {
            $latestEnrollmentIds = EnrolledStudent::selectRaw('MAX(enrollment_id) as enrollment_id')
                ->groupBy('student_number')
                ->pluck('enrollment_id');
            $query = EnrolledStudent::with(['student', 'course', 'section'])
                ->whereIn('enrollment_id', $latestEnrollmentIds);
            if ($statusFilter && $statusFilter !== 'all') {
                $query->whereHas('student', function ($q) use ($statusFilter) {
                    $q->where('status', $statusFilter);
                });
            }
        }

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

        if ($request->filled('year_level')) {
            $query->where('year_level', $request->year_level);
        }

        $enrollments = $query->get();

        $headers = ['Student ID', 'Name', 'Year Level', 'Section', 'Course', 'Status'];
        $rows = $enrollments->map(fn($e) => [
            $e->student?->student_number ?? 'N/A',
            $e->student?->full_name ?? 'N/A',
            $e->year_level ?? 'N/A',
            $e->section?->section_name ?? 'N/A',
            $e->course?->course_name ?? 'N/A',
            $e->student?->status ?? 'N/A',
        ])->toArray();

        $titleSuffix = $isActiveTermOnly && $academicCalendar
            ? $academicCalendar->display_label
            : ucfirst($statusFilter) . ' Students';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.pdf-table', [
            'title' => 'Student Records — ' . $titleSuffix,
            'date' => now()->format('F j, Y g:i A'),
            'headers' => $headers,
            'rows' => $rows,
            'filters' => $request->only(['search', 'course_id', 'status', 'year_level']),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('students_export_' . date('Y-m-d_His') . '.pdf');
    }
}
