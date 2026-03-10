<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Notification;
use App\Models\OrgMeeting;
use App\Models\OrgOfficer;
use App\Models\StudentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StudentOrganizationController extends Controller
{
    /**
     * Display a listing of organizations for the student.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $studentNumber = $user->student_number;

        // Get all active organizations
        $organizations = StudentOrganization::with(['president.student'])
            ->where('status', 'active')
            ->orderBy('org_name')
            ->get()
            ->map(function ($org) use ($studentNumber) {
                // Check if current student is an officer
                $officerRole = null;
                if ($studentNumber) {
                    $officer = $org->officers()
                        ->where('student_number', $studentNumber)
                        ->where('status', 'active')
                        ->first();
                    if ($officer) {
                        $officerRole = $officer->position;
                    }
                }

                return [
                    'org_id' => $org->org_id,
                    'logo_url' => $org->logo_path ? asset('storage/' . $org->logo_path) : null,
                    'org_name' => $org->org_name,
                    'org_code' => $org->org_code,
                    'description' => $org->description,
                    'type' => $org->type,
                    'president_name' => $org->president_name,
                    'adviser_name' => $org->adviser_display_name,
                    'members_count' => $org->members_count,
                    'is_officer' => !is_null($officerRole),
                    'officer_role' => $officerRole,
                ];
            });

        // Get organizations where the student is an officer
        $officerOrganizations = $organizations->filter(fn($org) => $org['is_officer'])->values();

        return Inertia::render('Student/Organizations/Index', [
            'organizations' => $organizations,
            'officerOrganizations' => $officerOrganizations,
        ]);
    }

    /**
     * Display the specified organization details.
     */
    public function show(StudentOrganization $organization): Response
    {
        $user = Auth::user();
        $studentNumber = $user->student_number;

        // Load relationships
        $organization->load([
            'officers.student',
            'members.student',
            'events' => function ($query) {
                $query->orderBy('event_date', 'desc')->limit(10);
            },
            'events.creator',
            'meetings' => function ($query) {
                $query->orderBy('meeting_date', 'desc')->limit(10);
            },
            'meetings.caller',
        ]);

        // Check if current student is an officer
        $isOfficer = false;
        $officerRole = null;
        if ($studentNumber) {
            $officer = $organization->officers()
                ->where('student_number', $studentNumber)
                ->where('status', 'active')
                ->first();
            if ($officer) {
                $isOfficer = true;
                $officerRole = $officer->position;
            }
        }

        return Inertia::render('Student/Organizations/Show', [
            'organization' => [
                'org_id' => $organization->org_id,
                'logo_url' => $organization->logo_path ? asset('storage/' . $organization->logo_path) : null,
                'org_name' => $organization->org_name,
                'org_code' => $organization->org_code,
                'description' => $organization->description,
                'type' => $organization->type,
                'status' => $organization->status,
                'adviser_name' => $organization->adviser_display_name,
                'mission' => $organization->mission,
                'mission_file_url' => $organization->mission_file
                    ? asset('storage/' . $organization->mission_file) : null,
                'mission_file_name' => $organization->mission_file
                    ? basename($organization->mission_file) : null,
                'vision' => $organization->vision,
                'vision_file_url' => $organization->vision_file
                    ? asset('storage/' . $organization->vision_file) : null,
                'vision_file_name' => $organization->vision_file
                    ? basename($organization->vision_file) : null,
                'goals' => $organization->goals,
                'goals_file_url' => $organization->goals_file
                    ? asset('storage/' . $organization->goals_file) : null,
                'goals_file_name' => $organization->goals_file
                    ? basename($organization->goals_file) : null,
                'constitution_bylaws' => $organization->constitution_bylaws,
                'constitution_bylaws_file_url' => $organization->constitution_bylaws_file
                    ? asset('storage/' . $organization->constitution_bylaws_file) : null,
                'constitution_bylaws_file_name' => $organization->constitution_bylaws_file
                    ? basename($organization->constitution_bylaws_file) : null,
                'officers' => $organization->officers->map(function ($officer) {
                    return [
                        'officer_id' => $officer->officer_id,
                        'student_name' => $officer->student->full_name ?? '',
                        'student_number' => $officer->student->student_number ?? '',
                        'position' => $officer->position,
                        'start_date' => $officer->start_date->format('Y-m-d'),
                    ];
                }),
                'members' => $organization->members->map(function ($member) {
                    return [
                        'member_id' => $member->member_id,
                        'student_name' => $member->student->full_name ?? '',
                        'student_number' => $member->student->student_number ?? '',
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
                        'created_by_name' => $event->creator->display_name ?? null,
                    ];
                }),
                'meetings' => $organization->meetings->map(function ($meeting) {
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
                        'called_by_name' => $meeting->caller->display_name ?? null,
                    ];
                }),
            ],
            'isOfficer' => $isOfficer,
            'officerRole' => $officerRole,
        ]);
    }

    /**
     * Update organization details (officers only).
     */
    public function update(Request $request, StudentOrganization $organization): RedirectResponse
    {
        // Verify the user is an officer
        $user = Auth::user();
        if (!$this->isOfficer($user->student_number, $organization->org_id)) {
            abort(403, 'You are not authorized to edit this organization.');
        }

        $validated = $request->validate([
            'description' => 'nullable|string',
            'adviser_name' => 'nullable|string|max:255',
            'mission' => 'nullable|string',
            'mission_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'remove_mission_file' => 'nullable|boolean',
            'vision' => 'nullable|string',
            'vision_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'remove_vision_file' => 'nullable|boolean',
            'goals' => 'nullable|string',
            'goals_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'remove_goals_file' => 'nullable|boolean',
            'constitution_bylaws' => 'nullable|string',
            'constitution_bylaws_file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'remove_constitution_bylaws_file' => 'nullable|boolean',
        ]);

        $updateData = [
            'description' => $validated['description'] ?? null,
            'adviser_name' => $validated['adviser_name'] ?? null,
        ];

        // Handle each document type
        $docTypes = [
            'mission' => 'mission_file',
            'vision' => 'vision_file',
            'goals' => 'goals_file',
            'constitution_bylaws' => 'constitution_bylaws_file',
        ];

        foreach ($docTypes as $textField => $fileField) {
            if ($request->has($textField)) {
                $updateData[$textField] = $validated[$textField] ?? null;
            }

            if ($request->hasFile($fileField)) {
                // Delete old file
                if ($organization->$fileField) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($organization->$fileField);
                }
                $updateData[$fileField] = $request->file($fileField)
                    ->store('organizations/documents', 'public');
            } elseif ($request->boolean("remove_{$fileField}") && $organization->$fileField) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($organization->$fileField);
                $updateData[$fileField] = null;
            }
        }

        $organization->update($updateData);

        return redirect()->route('student.organizations.show', $organization)
            ->with('success', 'Organization updated successfully.');
    }

    /**
     * Store a new event for the organization (officers only).
     */
    public function storeEvent(Request $request, StudentOrganization $organization): RedirectResponse
    {
        // Verify the user is an officer
        $user = Auth::user();
        if (!$this->isOfficer($user->student_number, $organization->org_id)) {
            abort(403, 'You are not authorized to create events for this organization.');
        }

        $validated = $request->validate([
            'event_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'venue' => 'nullable|string|max:255',
            'status' => 'required|in:Planning,Upcoming,Completed',
        ]);

        if (! $request->boolean('confirm_date_conflict')) {
            $conflictingOrgs = Event::otherOrgsOnDate($validated['event_date'], null, (int) $organization->org_id);
            if (! empty($conflictingOrgs)) {
                $names = implode(', ', $conflictingOrgs);
                throw ValidationException::withMessages([
                    'date_conflict' => [
                        "Another organization already has an event on this date ({$names}). Do you want to continue? Ask your adviser or org admin first.",
                    ],
                ]);
            }
        }

        Event::create([
            'org_id' => $organization->org_id,
            'event_name' => $validated['event_name'],
            'description' => $validated['description'],
            'event_date' => $validated['event_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'venue' => $validated['venue'],
            'status' => $validated['status'],
            'created_by' => $user->user_id,
        ]);

        return redirect()->route('student.organizations.show', $organization)
            ->with('success', 'Event created successfully.');
    }

    /**
     * Store a new meeting for the organization (officers only).
     */
    public function storeMeeting(Request $request, StudentOrganization $organization): RedirectResponse
    {
        // Verify the user is an officer
        $user = Auth::user();
        if (!$this->isOfficer($user->student_number, $organization->org_id)) {
            abort(403, 'You are not authorized to call meetings for this organization.');
        }

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
            'called_by' => $user->user_id,
            'status' => 'scheduled',
        ]);

        // Send notifications to targeted audience
        $this->sendMeetingNotifications($organization, $meeting);

        return redirect()->route('student.organizations.show', $organization)
            ->with('success', 'Meeting scheduled and notifications sent successfully.');
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
     * Check if a student is an officer of an organization.
     */
    private function isOfficer(?string $studentNumber, int $orgId): bool
    {
        if (!$studentNumber) {
            return false;
        }

        return OrgOfficer::where('org_id', $orgId)
            ->where('student_number', $studentNumber)
            ->where('status', 'active')
            ->exists();
    }
}

