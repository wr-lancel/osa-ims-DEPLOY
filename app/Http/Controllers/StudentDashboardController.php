<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\OrgOfficer;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class StudentDashboardController extends Controller
{
    /**
     * Display the student dashboard.
     */
    public function index(): Response
    {
        $user = Auth::user();
        $studentNumber = $user->student_number;

        // Get upcoming events (next 30 days)
        $upcomingEvents = Event::with(['organization'])
            ->where('event_date', '>=', now()->toDateString())
            ->where('event_date', '<=', now()->addDays(30)->toDateString())
            ->whereIn('status', ['Upcoming', 'Planning'])
            ->orderBy('event_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($event) {
                return [
                    'event_id' => $event->event_id,
                    'event_name' => $event->event_name,
                    'description' => $event->description,
                    'organization_name' => $event->organization->org_name ?? 'General',
                    'event_date' => $event->event_date->format('Y-m-d'),
                    'event_date_display' => $event->event_date->format('M d, Y'),
                    'start_time' => $event->start_time,
                    'end_time' => $event->end_time,
                    'venue' => $event->venue,
                    'status' => $event->status,
                    'days_until' => now()->startOfDay()->diffInDays($event->event_date, false),
                ];
            });

        // Get organizations where the student is an officer
        $officerOrganizations = [];
        if ($studentNumber) {
            $officerOrganizations = OrgOfficer::with('organization')
                ->where('student_number', $studentNumber)
                ->where('status', 'active')
                ->get()
                ->map(function ($officer) {
                    return [
                        'org_id' => $officer->organization->org_id,
                        'org_name' => $officer->organization->org_name,
                        'org_code' => $officer->organization->org_code,
                        'position' => $officer->position,
                    ];
                });
        }

        return Inertia::render('Student/Dashboard', [
            'upcomingEvents' => $upcomingEvents,
            'officerOrganizations' => $officerOrganizations,
        ]);
    }
}
