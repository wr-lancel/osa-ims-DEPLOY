<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateComplaintRequest;
use App\Models\Complaint;
use App\Services\ComplaintService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ComplaintController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected ComplaintService $complaintService
    ) {
    }

    /**
     * Complaints inbox: list all complaints with search and filters.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Complaint::class);

        $query = Complaint::with(['complainantEnrollment.student', 'complainantEnrollment.academicCalendar']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('complainantEnrollment.student', function ($studentQuery) use ($search) {
                    $studentQuery->where('student_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                    ->orWhere('subject', 'like', "%{$search}%");
                if (is_numeric($search)) {
                    $q->orWhere('complaint_id', (int) $search);
                }
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $complaints = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('perPage', 20))
            ->withQueryString();

        $complaints->getCollection()->transform(function ($complaint) {
            $complainant = $complaint->complainantEnrollment?->student;
            return [
                'complaint_id' => $complaint->complaint_id,
                'complainant' => $complainant
                    ? ($complainant->full_name ?? $complainant->student_number) . ' (' . $complainant->student_number . ')'
                    : '—',
                'category' => $complaint->category,
                'subject' => $complaint->subject,
                'date_submitted' => $complaint->created_at->format('Y-m-d'),
                'status' => $complaint->status,
            ];
        });

        $categories = [
            ['value' => '', 'label' => 'All Categories'],
            ['value' => 'Academic Integrity', 'label' => 'Academic Integrity'],
            ['value' => 'Campus Conduct', 'label' => 'Campus Conduct'],
            ['value' => 'Prohibited Activities', 'label' => 'Prohibited Activities'],
            ['value' => 'Other', 'label' => 'Other'],
        ];
        $statusOptions = [
            ['value' => '', 'label' => 'All Statuses'],
            ['value' => 'submitted', 'label' => 'Submitted'],
            ['value' => 'under_review', 'label' => 'Under Review'],
            ['value' => 'escalated', 'label' => 'Escalated'],
            ['value' => 'dismissed', 'label' => 'Dismissed'],
            ['value' => 'resolved', 'label' => 'Resolved'],
        ];

        return Inertia::render('Admin/Complaint/Index', [
            'complaints' => $complaints,
            'filters' => $request->only(['search', 'category', 'status']),
            'categories' => $categories,
            'statusOptions' => $statusOptions,
        ]);
    }

    /**
     * Show complaint detail with history; admin can update status and remarks.
     */
    public function show(Complaint $complaint): Response
    {
        $this->authorize('view', $complaint);

        $complaint->load([
            'complainantEnrollment.student',
            'complainantEnrollment.academicCalendar',
            'respondentEnrollment.student',
            'complaintHistories.changedBy',
        ]);

        $complainant = $complaint->complainantEnrollment?->student;
        $respondent = $complaint->respondentEnrollment?->student;

        $data = [
            'complaint_id' => $complaint->complaint_id,
            'complainant' => $complainant ? [
                'student_number' => $complainant->student_number,
                'full_name' => $complainant->full_name ?? ($complainant->first_name . ' ' . $complainant->last_name),
            ] : null,
            'respondent' => $respondent ? [
                'student_number' => $respondent->student_number,
                'full_name' => $respondent->full_name ?? ($respondent->first_name . ' ' . $respondent->last_name),
            ] : null,
            'subject' => $complaint->subject,
            'category' => $complaint->category,
            'description' => $complaint->description,
            'incident_date' => $complaint->incident_date->format('Y-m-d'),
            'location' => $complaint->location,
            'status' => $complaint->status,
            'date_submitted' => $complaint->created_at->format('Y-m-d H:i'),
        ];

        $history = $complaint->complaintHistories->map(fn($h) => [
            'history_id' => $h->history_id,
            'old_status' => $h->old_status,
            'new_status' => $h->new_status,
            'remarks' => $h->remarks,
            'created_at' => $h->created_at?->format('Y-m-d H:i'),
            'changed_by' => $h->changedBy->email ?? null,
        ]);

        $statusOptions = [
            ['value' => 'submitted', 'label' => 'Submitted'],
            ['value' => 'under_review', 'label' => 'Under Review'],
            ['value' => 'escalated', 'label' => 'Escalated'],
            ['value' => 'dismissed', 'label' => 'Dismissed'],
            ['value' => 'resolved', 'label' => 'Resolved'],
        ];

        return Inertia::render('Admin/Complaint/Show', [
            'complaint' => $data,
            'history' => $history,
            'statusOptions' => $statusOptions,
        ]);
    }

    /**
     * Update complaint status and remarks; record history and notify student.
     */
    public function update(UpdateComplaintRequest $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('update', $complaint);

        $oldStatus = $complaint->status;
        $data = $request->validated();

        DB::transaction(function () use ($complaint, $data, $oldStatus) {
            $complaint->update([
                'status' => $data['status'],
            ]);

            \App\Models\ComplaintHistory::create([
                'complaint_id' => $complaint->complaint_id,
                'changed_by_user_id' => Auth::id(),
                'old_status' => $oldStatus,
                'new_status' => $data['status'],
                'remarks' => $data['remarks'] ?? null,
            ]);

            $title = $data['status'] === 'escalated'
                ? 'Complaint escalated'
                : 'Complaint status updated';
            $statusLabel = str_replace('_', ' ', $data['status']);
            $message = 'Your complaint has been updated. New status: ' . $statusLabel . "\n\n" . notification_contact_footer('complaint');
            $this->complaintService->notifyStudentStatusUpdated($complaint, $title, $message);
        });

        return redirect()->route('admin.discipline.complaints.show', $complaint)
            ->with('success', 'Complaint updated successfully.');
    }

    /**
     * Export complaints to PDF.
     */
    public function exportPdf(Request $request)
    {
        $query = Complaint::with(['complainantEnrollment.student']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('complainantEnrollment.student', function ($studentQuery) use ($search) {
                    $studentQuery->where('student_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                    ->orWhere('subject', 'like', "%{$search}%");
                if (is_numeric($search)) {
                    $q->orWhere('complaint_id', (int) $search);
                }
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $complaints = $query->orderBy('created_at', 'desc')->get();

        $headers = ['ID', 'Complainant', 'Category', 'Subject', 'Date Submitted', 'Status'];
        $rows = $complaints->map(function ($complaint) {
            $complainant = $complaint->complainantEnrollment?->student;
            return [
                $complaint->complaint_id,
                $complainant
                ? ($complainant->full_name ?? $complainant->student_number) . ' (' . $complainant->student_number . ')'
                : '—',
                $complaint->category,
                $complaint->subject,
                $complaint->created_at->format('Y-m-d'),
                $complaint->status,
            ];
        })->toArray();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.pdf-table', [
            'title' => 'Complaints Report',
            'date' => now()->format('F j, Y g:i A'),
            'headers' => $headers,
            'rows' => $rows,
            'filters' => $request->only(['search', 'category', 'status']),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('complaints_export_' . date('Y-m-d_His') . '.pdf');
    }
}
