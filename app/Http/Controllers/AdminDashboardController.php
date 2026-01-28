<?php

namespace App\Http\Controllers;

use App\Models\AcademicCalendar;
use App\Models\Event;
use App\Models\EnrolledStudent;
use App\Models\StudentOrganization;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): Response
    {
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

        // Get dashboard statistics (from active academic calendar)
        $activeCalendar = AcademicCalendar::active()->first();
        $totalStudents = $activeCalendar 
            ? EnrolledStudent::where('enrollment_status', 'active')
                ->where('acad_id', $activeCalendar->calendar_id)
                ->count()
            : 0;

        $stats = [
            'total_students' => $totalStudents,
            'total_organizations' => StudentOrganization::where('status', 'active')->count(),
            'upcoming_events' => Event::where('event_date', '>=', now()->toDateString())
                ->whereIn('status', ['Upcoming', 'Planning'])
                ->count(),
            'events_this_month' => Event::whereMonth('event_date', now()->month)
                ->whereYear('event_date', now()->year)
                ->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'upcomingEvents' => $upcomingEvents,
            'stats' => $stats,
        ]);
    }
}
