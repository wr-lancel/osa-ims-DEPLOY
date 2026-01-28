<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrganizationRequest;
use App\Http\Requests\Admin\UpdateOrganizationRequest;
use App\Models\AcademicCalendar;
use App\Models\EnrolledStudent;
use App\Models\OrgOfficer;
use App\Models\StudentOrganization;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    /**
     * Display a listing of organizations.
     */
    public function index(Request $request): Response
    {
        // Get dashboard statistics
        $totalOrganizations = StudentOrganization::count();
        $activeOrganizations = StudentOrganization::where('status', 'active')->count();
        $totalMembers = \App\Models\OrgMember::where('status', 'active')->count();
        
        // Events this month
        $eventsThisMonth = Event::whereMonth('event_date', now()->month)
            ->whereYear('event_date', now()->year)
            ->count();

        // Build query with search and filters
        $query = StudentOrganization::with(['president.student', 'currentAdviser.employee']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('org_name', 'like', "%{$search}%")
                    ->orWhere('org_code', 'like', "%{$search}%")
                    ->orWhereHas('president.student', function ($studentQuery) use ($search) {
                        $studentQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Paginate results
        $organizations = $query->orderBy('org_name', 'asc')
            ->paginate(15)
            ->withQueryString();

        // Transform data for frontend
        $organizations->getCollection()->transform(function ($org) {
            return [
                'org_id' => $org->org_id,
                'org_name' => $org->org_name,
                'org_code' => $org->org_code,
                'type' => $org->type,
                'status' => $org->status,
                'president_name' => $org->president_name,
                'adviser_name' => $org->adviser_display_name,
                'members_count' => $org->members_count,
                'established_date' => $org->created_at->format('Y-m-d'),
            ];
        });

        return Inertia::render('Admin/Organizations/Index', [
            'organizations' => $organizations,
            'filters' => $request->only(['search', 'type', 'status']),
            'dashboardStats' => [
                [
                    'title' => 'Total Organizations',
                    'value' => $totalOrganizations,
                    'color' => 'blue',
                ],
                [
                    'title' => 'Active Organizations',
                    'value' => $activeOrganizations,
                    'color' => 'green',
                ],
                [
                    'title' => 'Total Members',
                    'value' => $totalMembers,
                    'color' => 'blue',
                ],
                [
                    'title' => 'Events This Month',
                    'value' => $eventsThisMonth,
                    'color' => 'yellow',
                ],
            ],
        ]);
    }

    /**
     * Store a newly created organization.
     */
    public function store(StoreOrganizationRequest $request): RedirectResponse
    {
        StudentOrganization::create($request->validated());

        return redirect()->route('admin.organizations.index')
            ->with('success', 'Organization created successfully.');
    }

    /**
     * Display the specified organization.
     */
    public function show(StudentOrganization $organization): Response
    {
        $organization->load([
            'officers.student',
            'members.student',
            'advisers.employee',
            'events.creator',
        ]);

        // Get enrolled students for officer assignment dropdown (from active academic calendar)
        $activeCalendar = AcademicCalendar::active()->first();
        $enrolledStudents = EnrolledStudent::with(['student', 'course'])
            ->where('enrollment_status', 'active')
            ->when($activeCalendar, fn($q) => $q->where('acad_id', $activeCalendar->calendar_id))
            ->get()
            ->map(function ($enrollment) {
                return [
                    'student_number' => $enrollment->student_number,
                    'full_name' => $enrollment->student->full_name ?? '',
                    'course_code' => $enrollment->course->course_code ?? '',
                    'year_level' => $enrollment->year_level,
                ];
            })
            ->unique('student_number')
            ->values();

        return Inertia::render('Admin/Organizations/Show', [
            'organization' => [
                'org_id' => $organization->org_id,
                'org_name' => $organization->org_name,
                'org_code' => $organization->org_code,
                'description' => $organization->description,
                'type' => $organization->type,
                'status' => $organization->status,
                'adviser_name' => $organization->adviser_name,
                'officers' => $organization->officers->map(function ($officer) {
                    return [
                        'officer_id' => $officer->officer_id,
                        'student_number' => $officer->student->student_number ?? '',
                        'student_name' => $officer->student->full_name ?? '',
                        'position' => $officer->position,
                        'start_date' => $officer->start_date->format('Y-m-d'),
                    ];
                }),
                'members' => $organization->members->map(function ($member) {
                    return [
                        'member_id' => $member->member_id,
                        'student_number' => $member->student->student_number ?? '',
                        'student_name' => $member->student->full_name ?? '',
                        'join_date' => $member->join_date->format('Y-m-d'),
                    ];
                }),
                'events' => $organization->events->map(function ($event) {
                    return [
                        'event_id' => $event->event_id,
                        'event_name' => $event->event_name,
                        'description' => $event->description,
                        'event_date' => $event->event_date->format('Y-m-d'),
                        'start_time' => $event->start_time,
                        'end_time' => $event->end_time,
                        'venue' => $event->venue,
                        'status' => $event->status,
                        'status_color' => $event->status_color,
                        'created_by_name' => $event->creator->display_name ?? null,
                    ];
                }),
            ],
            'enrolledStudents' => $enrolledStudents,
        ]);
    }

    /**
     * Update the specified organization.
     */
    public function update(UpdateOrganizationRequest $request, StudentOrganization $organization): RedirectResponse
    {
        $organization->update($request->validated());

        return redirect()->route('admin.organizations.show', $organization)
            ->with('success', 'Organization updated successfully.');
    }

    /**
     * Add an officer to the organization.
     */
    public function addOfficer(Request $request, StudentOrganization $organization): RedirectResponse
    {
        $validated = $request->validate([
            'student_number' => 'required|exists:students,student_number',
            'position' => 'required|string|max:100',
            'start_date' => 'required|date',
        ]);

        // Check if student is already an officer of this organization
        $existingOfficer = OrgOfficer::where('org_id', $organization->org_id)
            ->where('student_number', $validated['student_number'])
            ->where('status', 'active')
            ->first();

        if ($existingOfficer) {
            return redirect()->back()
                ->with('error', 'This student is already an officer of this organization.');
        }

        OrgOfficer::create([
            'org_id' => $organization->org_id,
            'student_number' => $validated['student_number'],
            'position' => $validated['position'],
            'start_date' => $validated['start_date'],
            'status' => 'active',
        ]);

        return redirect()->route('admin.organizations.show', $organization)
            ->with('success', 'Officer added successfully.');
    }

    /**
     * Remove an officer from the organization.
     */
    public function removeOfficer(StudentOrganization $organization, OrgOfficer $officer): RedirectResponse
    {
        // Verify the officer belongs to this organization
        if ($officer->org_id !== $organization->org_id) {
            return redirect()->back()
                ->with('error', 'Officer not found in this organization.');
        }

        $officer->update([
            'status' => 'inactive',
            'end_date' => now(),
        ]);

        return redirect()->route('admin.organizations.show', $organization)
            ->with('success', 'Officer removed successfully.');
    }

    /**
     * Update the adviser name for the organization.
     */
    public function updateAdviser(Request $request, StudentOrganization $organization): RedirectResponse
    {
        $validated = $request->validate([
            'adviser_name' => 'nullable|string|max:255',
        ]);

        $organization->update([
            'adviser_name' => $validated['adviser_name'],
        ]);

        return redirect()->route('admin.organizations.show', $organization)
            ->with('success', 'Adviser updated successfully.');
    }
}
