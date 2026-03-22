<?php

namespace App\Http\Controllers;

use App\Http\Requests\Student\StoreCandidacyRequest;
use App\Models\AcademicCalendar;
use App\Models\CandidacyApplication;
use App\Models\EnrolledStudent;
use App\Models\OrgMember;
use App\Models\OrgPosition;
use App\Models\Student;
use App\Models\StudentOrganization;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StudentCandidacyController extends Controller
{
    /**
     * Show the form for submitting a candidacy.
     */
    public function create(Request $request): Response
    {
        $user = Auth::user();
        $studentNumber = $user->student_number;

        $organizations = StudentOrganization::where('status', 'active')
            ->orderBy('org_name')
            ->get(['org_id', 'org_name', 'org_code']);

        $positions = OrgPosition::where('is_active', true)
            ->orderBy('position_name')
            ->get(['position_id', 'org_id', 'position_name'])
            ->groupBy('org_id');

        $academicTerms = AcademicCalendar::orderBy('start_date', 'desc')
            ->get()
            ->map(fn($c) => [
                'calendar_id' => $c->calendar_id,
                'academic_year' => $c->academic_year,
                'semester' => $c->semester,
                'display_label' => $c->display_label,
            ]);

        $activeTerm = AcademicCalendar::active()->first();
        $defaultAcadId = $activeTerm?->calendar_id;

        $preSelectedOrgId = $request->query('organization');
        if ($preSelectedOrgId && !$organizations->contains('org_id', (int) $preSelectedOrgId)) {
            $preSelectedOrgId = null;
        }

        $student = Student::with(['enrollments.course'])
            ->where('student_number', $studentNumber)
            ->first();

        $activeEnrollment = $student?->enrollments
            ->where('acad_id', $defaultAcadId)
            ->first();

        $studentInfo = [
            'name' => $student?->full_name,
            'first_name' => $student?->first_name,
            'last_name' => $student?->last_name,
            'middle_name' => $student?->middle_name,
            'student_number' => $studentNumber,
            'age' => $student?->birth_date ? $student->birth_date->age : null,
            'course' => $activeEnrollment?->course?->course_name,
            'course_code' => $activeEnrollment?->course?->course_code,
            'year_level' => $activeEnrollment?->year_level,
            'address' => $student?->address,
            'phone' => $student?->phone,
        ];

        return Inertia::render('Student/Organizations/CandidacyCreate', [
            'organizations' => $organizations,
            'positionsByOrg' => $positions->toArray(),
            'academicTerms' => $academicTerms,
            'defaultAcadId' => $defaultAcadId,
            'preSelectedOrgId' => $preSelectedOrgId ? (int) $preSelectedOrgId : null,
            'candidacyOpen' => SystemSetting::isCandidacyOpen(),
            'studentInfo' => $studentInfo,
        ]);
    }

    /**
     * Store a new candidacy application.
     */
    public function store(StoreCandidacyRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $studentNumber = $user->student_number;

        $enrollment = EnrolledStudent::where('student_number', $studentNumber)
            ->where('acad_id', $request->acad_id)
            ->first();

        if (!$enrollment) {
            return redirect()->back()->withErrors([
                'acad_id' => 'You do not have an enrollment for the selected term.',
            ])->withInput();
        }

        // Check if candidacy submissions are globally open
        if (!SystemSetting::isCandidacyOpen()) {
            return redirect()->back()->withErrors([
                'org_id' => 'Candidacy submissions are currently closed.',
            ])->withInput();
        }

        $isMember = OrgMember::where('org_id', $request->org_id)
            ->where('student_number', $studentNumber)
            ->where('status', 'active')
            ->exists();

        if (!$isMember) {
            return redirect()->back()->withErrors([
                'org_id' => 'You must be a member of the selected organization to run for a position.',
            ])->withInput();
        }

        $exists = CandidacyApplication::where('org_id', $request->org_id)
            ->where('enrollment_id', $enrollment->enrollment_id)
            ->where('position_id', $request->position_id)
            ->where('acad_id', $request->acad_id)
            ->exists();

        if ($exists) {
            return redirect()->back()->withErrors([
                'position_id' => 'You have already submitted a candidacy for this position and term.',
            ])->withInput();
        }

        $position = OrgPosition::find($request->position_id);
        if (!$position || $position->org_id != $request->org_id) {
            return redirect()->back()->withErrors([
                'position_id' => 'The selected position does not belong to this organization.',
            ])->withInput();
        }

        DB::transaction(function () use ($request, $enrollment) {
            CandidacyApplication::create([
                'org_id' => $request->org_id,
                'enrollment_id' => $enrollment->enrollment_id,
                'position_id' => $request->position_id,
                'acad_id' => $request->acad_id,
                'party_affiliation' => $request->party_affiliation,
                'unit_load' => $request->unit_load,
                'platform_statement' => $request->platform_statement,
                'motivation' => $request->motivation,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);
        });

        return redirect()->route('student.organizations.candidacies.index')
            ->with('success', 'Your candidacy has been submitted successfully.');
    }

    /**
     * List the current user's candidacy applications.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $studentNumber = $user->student_number;

        $enrollmentIds = EnrolledStudent::where('student_number', $studentNumber)->pluck('enrollment_id');

        $candidacies = CandidacyApplication::with(['organization:id,org_id,org_name,org_code', 'position:id,position_id,position_name', 'academicCalendar:calendar_id,academic_year,semester'])
            ->whereIn('enrollment_id', $enrollmentIds)
            ->orderBy('submitted_at', 'desc')
            ->get()
            ->map(fn($app) => [
                'application_id' => $app->application_id,
                'org_name' => $app->organization?->org_name,
                'org_code' => $app->organization?->org_code,
                'position_name' => $app->position?->position_name,
                'term_label' => $app->academicCalendar ? ($app->academicCalendar->academic_year . ($app->academicCalendar->semester ? ' - ' . $app->academicCalendar->semester : '')) : null,
                'status' => $app->status,
                'submitted_at' => $app->submitted_at?->format('Y-m-d H:i'),
            ]);

        $candidacyOpen = SystemSetting::isCandidacyOpen();

        return Inertia::render('Student/Organizations/CandidaciesIndex', [
            'candidacies' => $candidacies,
            'candidacyOpen' => (bool) $candidacyOpen,
        ]);
    }

    /**
     * Show a single candidacy application (own only).
     */
    public function show(CandidacyApplication $application): Response
    {
        $user = Auth::user();
        $application->load(['organization:id,org_id,org_name,org_code', 'position:id,position_id,position_name', 'academicCalendar:calendar_id,academic_year,semester', 'enrollment.student']);

        if ($application->enrollment->student_number !== $user->student_number) {
            abort(403, 'You can only view your own candidacy applications.');
        }

        return Inertia::render('Student/Organizations/CandidacyShow', [
            'application' => [
                'application_id' => $application->application_id,
                'org_name' => $application->organization?->org_name,
                'org_code' => $application->organization?->org_code,
                'position_name' => $application->position?->position_name,
                'term_label' => $application->academicCalendar ? ($application->academicCalendar->academic_year . ($application->academicCalendar->semester ? ' - ' . $application->academicCalendar->semester : '')) : null,
                'platform_statement' => $application->platform_statement,
                'motivation' => $application->motivation,
                'status' => $application->status,
                'submitted_at' => $application->submitted_at?->format('Y-m-d H:i'),
                'reviewed_at' => $application->reviewed_at?->format('Y-m-d H:i'),
                'review_remarks' => $application->review_remarks,
            ],
        ]);
    }

    /**
     * Withdraw a candidacy (only if submitted or under_review).
     */
    public function withdraw(CandidacyApplication $application): RedirectResponse
    {
        $user = Auth::user();
        $application->load('enrollment');

        if ($application->enrollment->student_number !== $user->student_number) {
            abort(403, 'You can only withdraw your own candidacy applications.');
        }

        if (!in_array($application->status, ['submitted', 'under_review'], true)) {
            return redirect()->back()->withErrors([
                'status' => 'Only submitted or under-review applications can be withdrawn.',
            ]);
        }

        $application->update(['status' => 'withdrawn']);

        return redirect()->route('student.organizations.candidacies.index')
            ->with('success', 'Your candidacy has been withdrawn.');
    }
}
