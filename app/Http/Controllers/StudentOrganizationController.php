<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\OrgOfficer;
use App\Models\StudentOrganization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                'org_name' => $organization->org_name,
                'org_code' => $organization->org_code,
                'description' => $organization->description,
                'type' => $organization->type,
                'status' => $organization->status,
                'adviser_name' => $organization->adviser_display_name,
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
        ]);

        $organization->update($validated);

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

