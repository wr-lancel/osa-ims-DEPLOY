<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Discipline;
use App\Models\DisciplineWorkflowStep;
use App\Models\Notification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DisciplineController extends Controller
{
    use AuthorizesRequests;
    /**
     * My violations list (own records only by student_number).
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $studentNumber = $user->student_number;

        $query = Discipline::with(['enrollment.academicCalendar'])
            ->where('student_number', $studentNumber)
            ->orderBy('violation_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->filled('acad_id')) {
            $query->whereHas('enrollment', function ($q) use ($request) {
                $q->where('acad_id', $request->acad_id);
            });
        }

        $violations = $query->paginate($request->input('perPage', 20))
            ->withQueryString()
            ->through(fn($v) => [
                'discipline_id' => $v->discipline_id,
                'violation_date' => $v->violation_date->format('Y-m-d'),
                'violation_type' => $v->violation_type,
                'severity' => $v->severity,
                'status' => $v->status,
                'term_label' => $v->enrollment && $v->enrollment->academicCalendar
                    ? $v->enrollment->academicCalendar->display_label
                    : null,
            ]);

        $unreadCount = Notification::where('user_id', $user->user_id)
            ->where('type', 'discipline')
            ->where('is_read', false)
            ->count();

        $complaintUnreadCount = Notification::where('user_id', $user->user_id)
            ->where('type', 'complaint')
            ->where('is_read', false)
            ->count();

        $terms = \App\Models\AcademicCalendar::orderBy('start_date', 'desc')
            ->get()
            ->map(fn($c) => ['calendar_id' => $c->calendar_id, 'display_label' => $c->display_label]);

        $codeOfConductSections = $this->getCodeOfConductSectionsWithContent();

        return Inertia::render('Student/Discipline/Index', [
            'violations' => $violations,
            'unreadNotificationsCount' => $unreadCount,
            'complaintUnreadCount' => $complaintUnreadCount,
            'filters' => $request->only(['acad_id']),
            'terms' => $terms,
            'codeOfConductSections' => $codeOfConductSections,
        ]);
    }

    /**
     * Violation detail (own only); mark related notification as read when opened.
     */
    public function show(Discipline $discipline): Response|RedirectResponse
    {
        $this->authorize('view', $discipline);

        $user = Auth::user();
        $discipline->load(['enrollment.academicCalendar', 'meetings', 'disciplineHistories.changedBy']);

        $violation = [
            'discipline_id' => $discipline->discipline_id,
            'violation_date' => $discipline->violation_date->format('Y-m-d'),
            'violation_type' => $discipline->violation_type,
            'description' => $discipline->description,
            'sanction' => $discipline->sanction,
            'date_resolved' => $discipline->date_resolved?->format('Y-m-d'),
            'severity' => $discipline->severity,
            'status' => $discipline->status,
            'remarks' => $discipline->remarks,
            'narrative_report' => $discipline->narrative_report,
            'narrative_report_file_url' => $discipline->narrative_report_file
                ? Storage::url($discipline->narrative_report_file)
                : null,
            'narrative_report_file_name' => $discipline->narrative_report_file
                ? basename($discipline->narrative_report_file)
                : null,
            'term_label' => $discipline->enrollment && $discipline->enrollment->academicCalendar
                ? $discipline->enrollment->academicCalendar->display_label
                : null,
        ];

        $meetings = $discipline->meetings->map(fn($m) => [
            'meeting_id' => $m->meeting_id,
            'meeting_date' => $m->meeting_date->format('Y-m-d'),
            'meeting_time' => $m->meeting_time,
            'location' => $m->location,
            'purpose_notes' => $m->purpose_notes,
            'status' => $m->status,
        ]);

        $history = $discipline->disciplineHistories->map(fn($h) => [
            'old_status' => $h->old_status,
            'new_status' => $h->new_status,
            'note' => $h->note,
            'created_at' => $h->created_at?->format('Y-m-d H:i'),
        ]);

        Notification::where('user_id', $user->user_id)
            ->where('type', 'discipline')
            ->where('related_case_id', $discipline->discipline_id)
            ->update(['is_read' => true]);

        return Inertia::render('Student/Discipline/Show', [
            'violation' => $violation,
            'meetings' => $meetings,
            'history' => $history,
            'workflowSteps' => DisciplineWorkflowStep::getStepsForProgressBar(),
            'terminalStatuses' => DisciplineWorkflowStep::getTerminalNames(),
        ]);
    }

    /**
     * Discipline notifications list.
     */
    public function notifications(Request $request): Response
    {
        $user = Auth::user();

        $notifications = Notification::where('user_id', $user->user_id)
            ->where('type', 'discipline')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('perPage', 20))
            ->withQueryString();

        $notifications->getCollection()->transform(function ($n) {
            return [
                'notification_id' => $n->notification_id,
                'title' => $n->title,
                'message' => $n->message,
                'related_case_id' => $n->related_case_id,
                'related_meeting_id' => $n->related_meeting_id,
                'is_read' => $n->is_read,
                'created_at' => $n->created_at?->format('Y-m-d H:i'),
            ];
        });

        $unreadCount = Notification::where('user_id', $user->user_id)
            ->where('type', 'discipline')
            ->where('is_read', false)
            ->count();

        return Inertia::render('Student/Discipline/Notifications', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    /**
     * Mark notification(s) as read.
     */
    public function markNotificationRead(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $id = $request->input('notification_id');
        $ids = $request->input('notification_ids', $id ? [$id] : []);

        if (!empty($ids)) {
            Notification::where('user_id', $user->user_id)
                ->where('type', 'discipline')
                ->whereIn('notification_id', $ids)
                ->update(['is_read' => true]);
        }

        return redirect()->back()->with('success', 'Marked as read.');
    }

    /**
     * Code of Conduct list (static categories).
     */
    public function codeOfConductIndex(): Response
    {
        $sections = [
            [
                'id' => 'academic-integrity',
                'title' => 'Academic Integrity',
                'items' => [
                    ['slug' => 'cheating', 'title' => 'Cheating and Plagiarism'],
                    ['slug' => 'fabrication', 'title' => 'Fabrication of Data'],
                ]
            ],
            [
                'id' => 'prohibited-activities',
                'title' => 'Prohibited Activities',
                'items' => [
                    ['slug' => 'bullying', 'title' => 'Bullying and Harassment'],
                    ['slug' => 'substance', 'title' => 'Substance Use'],
                ]
            ],
            [
                'id' => 'campus-conduct',
                'title' => 'Campus Conduct',
                'items' => [
                    ['slug' => 'attendance', 'title' => 'Attendance and Punctuality'],
                    ['slug' => 'property', 'title' => 'School Property'],
                ]
            ],
            [
                'id' => 'disciplinary-actions',
                'title' => 'Disciplinary Actions',
                'items' => [
                    ['slug' => 'sanctions', 'title' => 'Sanctions and Consequences'],
                    ['slug' => 'appeals', 'title' => 'Appeals Process'],
                ]
            ],
        ];

        return Inertia::render('Student/Discipline/CodeOfConduct/Index', [
            'sections' => $sections,
        ]);
    }

    /**
     * Code of Conduct topic detail (placeholder content).
     */
    public function codeOfConductShow(string $slug): Response
    {
        $topics = $this->getCodeOfConductTopics();
        $topic = $topics[$slug] ?? ['title' => ucfirst(str_replace('-', ' ', $slug)), 'content' => 'This section contains important information about the student code of conduct. Content will be updated by the administration.'];

        return Inertia::render('Student/Discipline/CodeOfConduct/Show', [
            'slug' => $slug,
            'title' => $topic['title'],
            'content' => $topic['content'],
        ]);
    }

    /**
     * Get Code of Conduct topics (slug => title, content). Shared by index and show.
     */
    private function getCodeOfConductTopics(): array
    {
        return [
            'cheating' => ['title' => 'Cheating and Plagiarism', 'content' => 'Students must not engage in cheating, plagiarism, or any form of academic dishonesty. All work submitted must be original and properly attributed.'],
            'fabrication' => ['title' => 'Fabrication of Data', 'content' => 'Fabricating data or sources in assignments or research is strictly prohibited and may result in disciplinary action.'],
            'bullying' => ['title' => 'Bullying and Harassment', 'content' => 'The school maintains a zero-tolerance policy for bullying, harassment, or discrimination. All students have the right to a safe learning environment.'],
            'substance' => ['title' => 'Substance Use', 'content' => 'Use, possession, or distribution of prohibited substances on campus is not allowed and will be handled according to school policy and applicable laws.'],
            'attendance' => ['title' => 'Attendance and Punctuality', 'content' => 'Regular attendance and punctuality are expected. Unexcused absences and chronic tardiness may result in disciplinary measures.'],
            'property' => ['title' => 'School Property', 'content' => 'Students must respect school property and the property of others. Vandalism or theft will be subject to disciplinary action and possible restitution.'],
            'sanctions' => ['title' => 'Sanctions and Consequences', 'content' => 'Disciplinary sanctions may include verbal warning, written warning, parent conference, suspension, or other measures as determined by the administration.'],
            'appeals' => ['title' => 'Appeals Process', 'content' => 'Students and parents may appeal disciplinary decisions according to the process outlined in the student handbook.'],
        ];
    }

    /**
     * Get Code of Conduct sections with full content for inline display.
     */
    private function getCodeOfConductSectionsWithContent(): array
    {
        $topics = $this->getCodeOfConductTopics();

        return [
            [
                'id' => 'academic-integrity',
                'title' => 'Academic Integrity',
                'items' => [
                    ['slug' => 'cheating', 'title' => $topics['cheating']['title'], 'content' => $topics['cheating']['content']],
                    ['slug' => 'fabrication', 'title' => $topics['fabrication']['title'], 'content' => $topics['fabrication']['content']],
                ]
            ],
            [
                'id' => 'prohibited-activities',
                'title' => 'Prohibited Activities',
                'items' => [
                    ['slug' => 'bullying', 'title' => $topics['bullying']['title'], 'content' => $topics['bullying']['content']],
                    ['slug' => 'substance', 'title' => $topics['substance']['title'], 'content' => $topics['substance']['content']],
                ]
            ],
            [
                'id' => 'campus-conduct',
                'title' => 'Campus Conduct',
                'items' => [
                    ['slug' => 'attendance', 'title' => $topics['attendance']['title'], 'content' => $topics['attendance']['content']],
                    ['slug' => 'property', 'title' => $topics['property']['title'], 'content' => $topics['property']['content']],
                ]
            ],
            [
                'id' => 'disciplinary-actions',
                'title' => 'Disciplinary Actions',
                'items' => [
                    ['slug' => 'sanctions', 'title' => $topics['sanctions']['title'], 'content' => $topics['sanctions']['content']],
                    ['slug' => 'appeals', 'title' => $topics['appeals']['title'], 'content' => $topics['appeals']['content']],
                ]
            ],
        ];
    }
}
