<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\OrgOfficer;
use App\Models\GuidanceAppointment;
use App\Models\PublicationArticle;
use App\Models\PublicationNewspaper;
use App\Models\SportsBorrowing;
use App\Models\Complaint;
use App\Models\OrgMeeting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
        $officerOrgIds = [];
        if ($studentNumber) {
            $officerOrganizations = OrgOfficer::with('organization')
                ->where('student_number', $studentNumber)
                ->where('status', 'active')
                ->get()
                ->map(function ($officer) use (&$officerOrgIds) {
                    $officerOrgIds[] = $officer->organization->org_id;
                    return [
                        'org_id' => $officer->organization->org_id,
                        'org_name' => $officer->organization->org_name,
                        'org_code' => $officer->organization->org_code,
                        'position' => $officer->position,
                    ];
                });
        }

        // Get personal appointments (Guidance)
        $appointments = GuidanceAppointment::where('student_number', $studentNumber)
            ->where('appointment_date', '>=', now()->toDateString())
            ->orderBy('appointment_date', 'asc')
            ->orderBy('appointment_time', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($app) {
                return [
                    'id' => $app->appointment_id,
                    'type' => $app->appointment_type ?? 'Guidance Counseling',
                    'date' => $app->appointment_date->format('M d, Y') . ($app->appointment_time ? ' ' . \Carbon\Carbon::parse($app->appointment_time)->format('h:i A') : ''),
                    'status' => $app->status,
                    'statusColor' => match(strtolower($app->status)) {
                        'approved' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                        'pending' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                        'completed' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                        default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400'
                    }
                ];
            });

        // Get active sports borrowings (Not Returned/Not Rejected)
        $activeBorrowings = SportsBorrowing::with('sport')
            ->where('student_number', $studentNumber)
            ->whereIn('status', ['pending', 'approved', 'borrowed', 'overdue'])
            ->orderBy('borrow_date', 'asc')
            ->limit(3)
            ->get()
            ->map(function ($borrowing) {
               $isDueSoon = false;
               if (in_array(strtolower($borrowing->status), ['borrowed', 'approved']) && $borrowing->expected_return_date) {
                    $dueDate = \Carbon\Carbon::parse($borrowing->expected_return_date->format('Y-m-d'));
                    if (now()->diffInDays($dueDate, false) <= 1 && now()->diffInDays($dueDate, false) >= -1) {
                        $isDueSoon = true;
                    }
               }

                return [
                    'id' => $borrowing->borrowing_id,
                    'item' => $borrowing->item_name . ' (' . ($borrowing->sport->sport_name ?? 'Sports') . ')',
                    'dueDate' => $borrowing->expected_return_date ? $borrowing->expected_return_date->format('M d, Y') : ($borrowing->borrow_date ? $borrowing->borrow_date->format('M d, Y') : ''),
                    'status' => ucfirst($borrowing->status),
                    'isDueSoon' => $isDueSoon,
                ];
            });

        // Get recent complaints submitted by the student
        $complaints = Complaint::whereHas('complainantEnrollment', function ($q) use ($studentNumber) {
                $q->where('student_number', $studentNumber);
            })
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($complaint) {
                return [
                    'id' => $complaint->complaint_id,
                    'subject' => $complaint->subject,
                    'status' => ucfirst(str_replace('_', ' ', $complaint->status)),
                    'date' => $complaint->created_at->format('M d, Y'),
                ];
            });

        // Get recent unread notifications
        $notifications = \App\Models\Notification::where('user_id', $user->user_id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->notification_id,
                    'title' => $notification->title ?? 'Notification',
                    'message' => $notification->message ?? 'You have a new notification.',
                    'time' => $notification->created_at->diffForHumans(),
                    'read_at' => $notification->is_read ? clone $notification->created_at : null,
                    'color' => $notification->is_read ? 'text-gray-500 bg-gray-100 dark:bg-gray-800' : 'text-indigo-500 bg-indigo-100 dark:bg-indigo-900/30',
                    'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
                ];
            });

        // Get Officer Activities if they are an officer
        $officerActivities = [];
        if (!empty($officerOrgIds)) {
            // Get events for their orgs
            $orgEvents = Event::whereIn('org_id', $officerOrgIds)
                ->where('event_date', '>=', now()->toDateString())
                ->orderBy('event_date', 'asc')
                ->limit(3)
                ->get()
                ->map(function($ev) {
                    return [
                        'id' => 'evt_'.$ev->event_id,
                        'title' => $ev->event_name,
                        'date' => $ev->event_date->format('M d, Y') . ' ' . \Carbon\Carbon::parse($ev->start_time)->format('h:i A'),
                        'venue' => $ev->venue,
                        'type' => 'Event',
                        'color' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400'
                    ];
                });

            // Get meetings for their orgs
            $orgMeetings = OrgMeeting::whereIn('org_id', $officerOrgIds)
                ->where('meeting_date', '>=', now()->toDateString())
                ->orderBy('meeting_date', 'asc')
                ->limit(3)
                ->get()
                ->map(function($mtg) {
                    return [
                        'id' => 'mtg_'.$mtg->meeting_id,
                        'title' => $mtg->agenda ?? 'Officer Meeting',
                        'date' => $mtg->meeting_date->format('M d, Y') . ' ' . \Carbon\Carbon::parse($mtg->start_time)->format('h:i A'),
                        'venue' => $mtg->venue,
                        'type' => 'Meeting',
                        'color' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400'
                    ];
                });

            // Combine and sort
            $officerActivities = collect($orgEvents)->merge($orgMeetings)
                ->sortBy(function($act) {
                    return \Carbon\Carbon::parse(str_replace(' Tomorrow', '', $act['date']))->timestamp;
                })->take(4)->values()->all();
        }

        // Latest publication teaser
        $latestPublication = PublicationArticle::published()
            ->orderBy('published_at', 'desc')
            ->first();

        $latestNewspaper = PublicationNewspaper::published()
            ->orderBy('published_at', 'desc')
            ->first();

        return Inertia::render('Student/Dashboard', [
            'upcomingEvents' => $upcomingEvents,
            'officerOrganizations' => $officerOrganizations,
            'appointments' => $appointments,
            'activeBorrowings' => $activeBorrowings,
            'complaints' => $complaints,
            'notifications' => $notifications,
            'officerActivities' => $officerActivities,
            'latestPublication' => $latestPublication ? [
                'title' => $latestPublication->title,
                'slug' => $latestPublication->slug,
                'cover_image' => $latestPublication->cover_image ? Storage::disk('public')->url($latestPublication->cover_image) : null,
                'published_at' => $latestPublication->published_at?->format('M d, Y'),
            ] : null,
            'latestNewspaper' => $latestNewspaper ? [
                'title' => $latestNewspaper->title,
                'slug' => $latestNewspaper->slug,
                'issue_number' => $latestNewspaper->issue_number,
                'published_at' => $latestNewspaper->published_at?->format('M d, Y'),
            ] : null,
        ]);
    }
}
