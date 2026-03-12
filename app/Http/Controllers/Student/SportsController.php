<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreSportsBorrowingRequest;
use App\Models\SportsBorrowing;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SportsController extends Controller
{
    /**
     * Display the sports borrowing form and student's borrowing history.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $studentNumber = $user->student_number;

        // Get student's borrowing history
        $borrowingsQuery = SportsBorrowing::with(['approver', 'rejector'])
            ->where('student_number', $studentNumber);

        // Filter by status if provided
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'overdue') {
                $borrowingsQuery->overdue();
            } else {
                $borrowingsQuery->where('status', $status);
            }
        }

        $borrowings = $borrowingsQuery
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('perPage', 20))
            ->withQueryString();

        // Transform borrowings data
        $borrowings->getCollection()->transform(function ($borrowing) {
            return [
                'borrowing_id' => $borrowing->borrowing_id,
                'item_name' => $borrowing->item_name,
                'description' => $borrowing->description,
                'borrow_date' => $borrowing->borrow_date->format('Y-m-d'),
                'expected_return_date' => $borrowing->expected_return_date->format('Y-m-d'),
                'return_date' => $borrowing->return_date ? $borrowing->return_date->format('Y-m-d') : null,
                'status' => $borrowing->formatted_status,
                'status_color' => $borrowing->status_color,
                'is_overdue' => $borrowing->is_overdue,
                'admin_remarks' => $borrowing->admin_remarks,
                'approved_at' => $borrowing->approved_at ? $borrowing->approved_at->format('Y-m-d H:i') : null,
                'rejected_at' => $borrowing->rejected_at ? $borrowing->rejected_at->format('Y-m-d H:i') : null,
                'approver_name' => $borrowing->approver ? $borrowing->approver->email : null,
                'rejector_name' => $borrowing->rejector ? $borrowing->rejector->email : null,
                'created_at' => $borrowing->created_at->format('Y-m-d H:i'),
            ];
        });

        return Inertia::render('Student/Sports/Index', [
            'borrowings' => $borrowings,
            'filters' => $request->only(['status']),
            'equipmentList' => SystemSetting::getList('sports_equipment'),
        ]);
    }

    /**
     * Store a new borrowing request.
     */
    public function store(StoreSportsBorrowingRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();

        // Get student_number from user, or try to find student by email
        $studentNumber = $user->student_number;
        if (!$studentNumber) {
            // Try to find student by matching user's email
            $student = \App\Models\Student::where('email', $user->email)->first();
            if ($student) {
                $studentNumber = $student->student_number;
                // Update the user record with the student_number
                $user->update(['student_number' => $studentNumber]);
            }
        }

        // Validate that we have a student_number
        if (!$studentNumber) {
            return redirect()->back()
                ->withErrors(['error' => 'Unable to identify your student account. Please contact the administrator to link your account to a student record.']);
        }

        $data['student_number'] = $studentNumber;
        $data['status'] = 'pending';

        // Set borrow_date to today if not provided
        if (!isset($data['borrow_date'])) {
            $data['borrow_date'] = now()->toDateString();
        }

        SportsBorrowing::create($data);

        return redirect()->route('student.sports.index')
            ->with('success', 'Borrowing request submitted successfully. Waiting for admin approval.');
    }

    /**
     * Display the specified borrowing request details.
     */
    public function show(SportsBorrowing $borrowing): Response
    {
        $user = Auth::user();

        // Ensure the borrowing belongs to the authenticated student
        if ($borrowing->student_number !== $user->student_number) {
            abort(403, 'Unauthorized access.');
        }

        $borrowing->load(['approver', 'rejector', 'student']);

        return Inertia::render('Student/Sports/Show', [
            'borrowing' => [
                'borrowing_id' => $borrowing->borrowing_id,
                'item_name' => $borrowing->item_name,
                'description' => $borrowing->description,
                'borrow_date' => $borrowing->borrow_date->format('Y-m-d'),
                'expected_return_date' => $borrowing->expected_return_date->format('Y-m-d'),
                'return_date' => $borrowing->return_date ? $borrowing->return_date->format('Y-m-d') : null,
                'status' => $borrowing->formatted_status,
                'status_color' => $borrowing->status_color,
                'is_overdue' => $borrowing->is_overdue,
                'notes' => $borrowing->notes,
                'admin_remarks' => $borrowing->admin_remarks,
                'approved_at' => $borrowing->approved_at ? $borrowing->approved_at->format('Y-m-d H:i') : null,
                'rejected_at' => $borrowing->rejected_at ? $borrowing->rejected_at->format('Y-m-d H:i') : null,
                'approver_name' => $borrowing->approver ? $borrowing->approver->email : null,
                'rejector_name' => $borrowing->rejector ? $borrowing->rejector->email : null,
                'created_at' => $borrowing->created_at->format('Y-m-d H:i'),
                'updated_at' => $borrowing->updated_at->format('Y-m-d H:i'),
            ],
        ]);
    }
}
