<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOrganizationRequest;
use App\Http\Requests\Admin\UpdateOrganizationRequest;
use App\Models\AcademicCalendar;
use App\Models\EnrolledStudent;
use App\Models\Notification;
use App\Models\OrgMeeting;
use App\Models\OrgOfficer;
use App\Models\StudentOrganization;
use App\Models\SystemSetting;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            ->paginate($request->input('perPage', 20))
            ->withQueryString();

        // Transform data for frontend
        $organizations->getCollection()->transform(function ($org) {
            return [
                'org_id' => $org->org_id,
                'logo_url' => $org->logo_path ? Storage::url($org->logo_path) : null,
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
            'organizationTypes' => SystemSetting::getList('organization_types'),
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
        $data = $request->validated();
        
        if ($request->hasFile('logo')) {
            try {
                $data['logo_path'] = $request->file('logo')->store('organizations/logos', 'public');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Failed to store organization logo: ' . $e->getMessage());
            }
        }

        // Remove the file object from data - only logo_path should be saved to DB
        unset($data['logo']);

        StudentOrganization::create($data);

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
            'meetings.caller',
        ]);

        // Get enrolled students for officer assignment dropdown (from active academic calendar)
        $activeCalendar = AcademicCalendar::active()->first();
        $enrolledStudents = EnrolledStudent::with(['student', 'course'])
            ->where('enrollment_status', 'enrolled')
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
                'logo_url' => $organization->logo_path ? Storage::url($organization->logo_path) : null,
                'logo_path' => $organization->logo_path,
                'org_name' => $organization->org_name,
                'org_code' => $organization->org_code,
                'description' => $organization->description,
                'type' => $organization->type,
                'status' => $organization->status,
                'adviser_name' => $organization->adviser_name,
                'mission' => $organization->mission,
                'mission_file' => $organization->mission_file,
                'mission_file_url' => $organization->mission_file ? Storage::url($organization->mission_file) : null,
                'mission_file_name' => $organization->mission_file ? basename($organization->mission_file) : null,
                'vision' => $organization->vision,
                'vision_file' => $organization->vision_file,
                'vision_file_url' => $organization->vision_file ? Storage::url($organization->vision_file) : null,
                'vision_file_name' => $organization->vision_file ? basename($organization->vision_file) : null,
                'goals' => $organization->goals,
                'goals_file' => $organization->goals_file,
                'goals_file_url' => $organization->goals_file ? Storage::url($organization->goals_file) : null,
                'goals_file_name' => $organization->goals_file ? basename($organization->goals_file) : null,
                'constitution_bylaws' => $organization->constitution_bylaws,
                'constitution_bylaws_file' => $organization->constitution_bylaws_file,
                'constitution_bylaws_file_url' => $organization->constitution_bylaws_file ? Storage::url($organization->constitution_bylaws_file) : null,
                'constitution_bylaws_file_name' => $organization->constitution_bylaws_file ? basename($organization->constitution_bylaws_file) : null,
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
                'meetings' => $organization->meetings->sortByDesc('meeting_date')->values()->map(function ($meeting) {
                    return [
                        'meeting_id' => $meeting->meeting_id,
                        'title' => $meeting->title,
                        'description' => $meeting->description,
                        'meeting_date' => $meeting->meeting_date->format('Y-m-d'),
                        'start_time' => $meeting->start_time,
                        'end_time' => $meeting->end_time,
                        'venue' => $meeting->venue,
                        'target_audience' => $meeting->target_audience,
                        'status' => $meeting->status,
                        'called_by_name' => $meeting->caller->display_name ?? 'Unknown',
                        'created_at' => $meeting->created_at->format('Y-m-d H:i'),
                    ];
                }),
            ],
            'enrolledStudents' => $enrolledStudents,
            'organizationTypes' => SystemSetting::getList('organization_types'),
        ]);
    }

    /**
     * Update the specified organization.
     */
    public function update(UpdateOrganizationRequest $request, StudentOrganization $organization): RedirectResponse
    {
        $data = $request->validated();

        // Handle file uploads for each document type and logo
        $fileFields = [
            'logo' => 'organizations/logos',
            'mission_file' => 'organizations/documents',
            'vision_file' => 'organizations/documents',
            'goals_file' => 'organizations/documents',
            'constitution_bylaws_file' => 'organizations/documents',
        ];

        foreach ($fileFields as $field => $storagePath) {
            $removeFlag = 'remove_' . $field;
            $dbField = $field === 'logo' ? 'logo_path' : $field;

            if ($request->hasFile($field)) {
                try {
                    // Delete old file if exists
                    if ($organization->$dbField) {
                        Storage::disk('public')->delete($organization->$dbField);
                    }
                    $data[$dbField] = $request->file($field)->store($storagePath, 'public');
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to store {$field}: " . $e->getMessage());
                }
            } elseif ($request->boolean($removeFlag) && $organization->$dbField) {
                try {
                    Storage::disk('public')->delete($organization->$dbField);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error("Failed to delete {$field}: " . $e->getMessage());
                }
                $data[$dbField] = null;
            } else {
                unset($data[$dbField]);
            }
            
            // Unset form fields from data array to avoid SQL errors
            unset($data[$field]);
            unset($data[$removeFlag]);
        }

        $organization->update($data);

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

    /**
     * Store a new meeting for the organization.
     */
    public function storeMeeting(Request $request, StudentOrganization $organization): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'meeting_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'nullable',
            'venue' => 'nullable|string|max:255',
            'target_audience' => 'required|in:officers,members,all',
        ]);

        $meeting = OrgMeeting::create([
            'org_id' => $organization->org_id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'meeting_date' => $validated['meeting_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'] ?? null,
            'venue' => $validated['venue'] ?? null,
            'target_audience' => $validated['target_audience'],
            'called_by' => auth()->user()->user_id,
            'status' => 'scheduled',
        ]);

        // Send notifications to targeted audience
        $this->sendMeetingNotifications($organization, $meeting);

        return redirect()->route('admin.organizations.show', $organization)
            ->with('success', 'Meeting scheduled and notifications sent successfully.');
    }

    /**
     * Update the status of a meeting.
     */
    public function updateMeetingStatus(Request $request, StudentOrganization $organization, OrgMeeting $meeting): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:scheduled,completed,cancelled',
        ]);

        $meeting->update(['status' => $validated['status']]);

        return redirect()->route('admin.organizations.show', $organization)
            ->with('success', 'Meeting status updated successfully.');
    }

    /**
     * Send notifications to the targeted audience for a meeting.
     */
    private function sendMeetingNotifications(StudentOrganization $organization, OrgMeeting $meeting): void
    {
        $studentNumbers = collect();

        if (in_array($meeting->target_audience, ['officers', 'all'])) {
            $officerNumbers = $organization->officers()->pluck('student_number');
            $studentNumbers = $studentNumbers->merge($officerNumbers);
        }

        if (in_array($meeting->target_audience, ['members', 'all'])) {
            $memberNumbers = $organization->members()->pluck('student_number');
            $studentNumbers = $studentNumbers->merge($memberNumbers);
        }

        $studentNumbers = $studentNumbers->unique();

        // Find user accounts for these students
        $users = \App\Models\User::whereIn('student_number', $studentNumbers)->get();

        $timeStr = $meeting->start_time;
        if ($meeting->end_time) {
            $timeStr .= ' - ' . $meeting->end_time;
        }

        $body = 'You are notified of the following meeting. ' . $organization->org_name . ' has scheduled a meeting on ' . $meeting->meeting_date->format('M d, Y') . ' at ' . $timeStr . '.' . ($meeting->venue ? ' Venue: ' . $meeting->venue . '.' : '') . ($meeting->description ? ' Agenda: ' . $meeting->description : '') . "\n\n" . notification_contact_footer('org_meeting');
        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->user_id,
                'type' => 'org_meeting',
                'title' => "Meeting Called: {$meeting->title}",
                'message' => $body,
            ]);
        }
    }

    /**
     * Export organizations to PDF.
     */
    public function exportPdf(Request $request)
    {
        $query = StudentOrganization::with(['president.student', 'currentAdviser.employee']);

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

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $organizations = $query->orderBy('org_name', 'asc')->get();

        $headers = ['Name', 'Code', 'Type', 'Status', 'President', 'Adviser', 'Members'];
        $rows = $organizations->map(fn($org) => [
            $org->org_name,
            $org->org_code,
            $org->type,
            $org->status,
            $org->president_name,
            $org->adviser_display_name,
            $org->members_count,
        ])->toArray();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.pdf-table', [
            'title' => 'Organizations Report',
            'date' => now()->format('F j, Y g:i A'),
            'headers' => $headers,
            'rows' => $rows,
            'filters' => $request->only(['search', 'type', 'status']),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('organizations_export_' . date('Y-m-d_His') . '.pdf');
    }
}
