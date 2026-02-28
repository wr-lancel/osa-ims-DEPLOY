<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCandidacyStatusRequest;
use App\Models\CandidacyApplication;
use App\Models\StudentOrganization;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CandidacyController extends Controller
{
    /**
     * List ALL candidacy applications across all organizations (with filters).
     */
    public function index(Request $request): Response
    {
        $query = CandidacyApplication::with(['enrollment.student', 'position', 'academicCalendar', 'organization']);

        if ($request->filled('org_id')) {
            $query->where('org_id', $request->org_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('position_id')) {
            $query->where('position_id', $request->position_id);
        }
        if ($request->filled('acad_id')) {
            $query->where('acad_id', $request->acad_id);
        }

        $applications = $query->orderBy('submitted_at', 'desc')
            ->paginate($request->input('perPage', 20))
            ->withQueryString();

        $stats = [
            'submitted' => CandidacyApplication::where('status', 'submitted')->count(),
            'under_review' => CandidacyApplication::where('status', 'under_review')->count(),
            'approved' => CandidacyApplication::where('status', 'approved')->count(),
            'rejected' => CandidacyApplication::where('status', 'rejected')->count(),
        ];

        $organizations = StudentOrganization::where('status', 'active')
            ->orderBy('org_name')
            ->get(['org_id', 'org_name', 'org_code']);

        $terms = \App\Models\AcademicCalendar::orderBy('start_date', 'desc')
            ->get()
            ->map(fn($c) => ['calendar_id' => $c->calendar_id, 'display_label' => $c->display_label]);

        $applications->getCollection()->transform(function ($app) {
            return [
                'application_id' => $app->application_id,
                'applicant_name' => $app->enrollment?->student?->full_name ?? '—',
                'student_number' => $app->enrollment?->student?->student_number ?? '—',
                'org_name' => $app->organization?->org_name ?? '—',
                'org_code' => $app->organization?->org_code ?? '—',
                'position_name' => $app->position?->position_name,
                'term_label' => $app->academicCalendar ? ($app->academicCalendar->academic_year . ($app->academicCalendar->semester ? ' - ' . $app->academicCalendar->semester : '')) : null,
                'submitted_at' => $app->submitted_at?->format('Y-m-d H:i'),
                'status' => $app->status,
            ];
        });

        return Inertia::render('Admin/Organizations/CandidaciesIndex', [
            'applications' => $applications,
            'stats' => $stats,
            'filters' => $request->only(['status', 'org_id', 'acad_id']),
            'organizations' => $organizations,
            'terms' => $terms,
            'candidacyOpen' => SystemSetting::isCandidacyOpen(),
        ]);
    }

    /**
     * Show a single candidacy application.
     */
    public function show(CandidacyApplication $application): Response
    {
        $application->load(['enrollment.student', 'position', 'academicCalendar', 'organization']);

        $data = [
            'application_id' => $application->application_id,
            'applicant_name' => $application->enrollment?->student?->full_name ?? '—',
            'student_number' => $application->enrollment?->student?->student_number ?? '—',
            'org_name' => $application->organization?->org_name ?? '—',
            'org_code' => $application->organization?->org_code ?? '—',
            'position_name' => $application->position?->position_name,
            'term_label' => $application->academicCalendar ? ($application->academicCalendar->academic_year . ($application->academicCalendar->semester ? ' - ' . $application->academicCalendar->semester : '')) : null,
            'platform_statement' => $application->platform_statement,
            'motivation' => $application->motivation,
            'status' => $application->status,
            'submitted_at' => $application->submitted_at?->format('Y-m-d H:i'),
            'reviewed_at' => $application->reviewed_at?->format('Y-m-d H:i'),
            'review_remarks' => $application->review_remarks,
        ];

        return Inertia::render('Admin/Organizations/CandidacyShow', [
            'application' => $data,
        ]);
    }

    /**
     * Update application status (under_review, approved, rejected).
     */
    public function updateStatus(UpdateCandidacyStatusRequest $request, CandidacyApplication $application): RedirectResponse
    {
        $application->update([
            'status' => $request->status,
            'review_remarks' => $request->review_remarks,
            'reviewed_at' => in_array($request->status, ['approved', 'rejected'], true) ? now() : $application->reviewed_at,
        ]);

        return redirect()->back()->with('success', 'Application status updated.');
    }

    /**
     * Toggle candidacy submissions open/closed globally.
     */
    public function toggleCandidacy(): RedirectResponse
    {
        $currentlyOpen = SystemSetting::isCandidacyOpen();
        SystemSetting::setValue('candidacy_submissions_open', $currentlyOpen ? '0' : '1');

        $status = !$currentlyOpen ? 'opened' : 'closed';
        return redirect()->back()->with('success', "Candidacy submissions {$status} globally.");
    }
}
