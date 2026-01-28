<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEventRequest;
use App\Http\Requests\Admin\UpdateEventRequest;
use App\Models\Event;
use App\Models\StudentOrganization;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /**
     * Display a listing of events.
     */
    public function index(Request $request): Response
    {
        // Build query with search and filters
        $query = Event::with(['organization', 'creator']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('event_name', 'like', "%{$search}%")
                    ->orWhereHas('organization', function ($orgQuery) use ($search) {
                        $orgQuery->where('org_name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by organization
        if ($request->filled('org_id')) {
            $query->where('org_id', $request->org_id);
        }

        // Paginate results
        $events = $query->orderBy('event_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Transform data for frontend
        $events->getCollection()->transform(function ($event) {
            return [
                'event_id' => $event->event_id,
                'event_name' => $event->event_name,
                'description' => $event->description,
                'org_id' => $event->org_id,
                'organization_name' => $event->organization->org_name ?? 'N/A',
                'event_date' => $event->event_date->format('Y-m-d'),
                'start_time' => $event->start_time ? (is_string($event->start_time) ? $event->start_time : $event->start_time->format('H:i')) : null,
                'end_time' => $event->end_time ? (is_string($event->end_time) ? $event->end_time : $event->end_time->format('H:i')) : null,
                'venue' => $event->venue,
                'status' => $event->status,
                'status_color' => $event->status_color,
                'created_by_name' => $event->creator->display_name ?? null,
                'attendees_count' => 0, // Future: from attendance table
            ];
        });

        // Get organizations for filter
        $organizations = StudentOrganization::where('status', 'active')
            ->orderBy('org_name')
            ->get(['org_id', 'org_name']);

        return Inertia::render('Admin/Organizations/Events', [
            'events' => $events,
            'filters' => $request->only(['search', 'status', 'org_id']),
            'organizations' => $organizations,
        ]);
    }

    /**
     * Store a newly created event.
     */
    public function store(StoreEventRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();
        
        Event::create($data);

        return redirect()->route('admin.organizations.events.index')
            ->with('success', 'Event created successfully.');
    }

    /**
     * Display the specified event.
     */
    public function show(Event $event): Response
    {
        $event->load('organization');

        return Inertia::render('Admin/Organizations/EventShow', [
            'event' => [
                'event_id' => $event->event_id,
                'event_name' => $event->event_name,
                'description' => $event->description,
                'org_id' => $event->org_id,
                'organization_name' => $event->organization->org_name ?? 'N/A',
                'event_date' => $event->event_date->format('Y-m-d'),
                'start_time' => $event->start_time ? $event->start_time->format('H:i') : null,
                'end_time' => $event->end_time ? $event->end_time->format('H:i') : null,
                'venue' => $event->venue,
                'status' => $event->status,
            ],
        ]);
    }

    /**
     * Update the specified event.
     */
    public function update(UpdateEventRequest $request, Event $event)
    {
        $event->update($request->validated());

        return redirect()->route('admin.organizations.events.index')
            ->with('success', 'Event updated successfully.');
    }
}

