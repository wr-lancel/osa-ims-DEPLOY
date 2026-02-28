<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveSportsBorrowingRequest;
use App\Http\Requests\Admin\RejectSportsBorrowingRequest;
use App\Http\Requests\Admin\StoreSportsBorrowingRequest;
use App\Http\Requests\Admin\UpdateSportsBorrowingRequest;
use App\Models\SportsBorrowing;
use App\Models\Sport;
use App\Models\SportAthlete;
use App\Models\Employee;
use App\Models\Student;
use App\Models\AcademicCalendar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SportsController extends Controller
{
    /**
     * Display sports dashboard with equipment borrowing.
     */
    public function index(Request $request): Response
    {
        // Get dashboard statistics
        $totalAthletes = SportAthlete::count();
        $totalSports = Sport::active()->count();
        $equipmentBorrowed = SportsBorrowing::where('status', 'borrowed')->count();
        $overdueItems = SportsBorrowing::overdue()->count();
        $pendingRequests = SportsBorrowing::where('status', 'pending')->count();

        // Get pending requests separately for prominent display
        $pendingBorrowings = SportsBorrowing::with(['student', 'employee', 'approver', 'rejector'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($borrowing) {
                $borrowerName = '';
                $borrowerId = '';

                if ($borrowing->student) {
                    $borrowerName = $borrowing->student->full_name;
                    $borrowerId = $borrowing->student->student_number;
                } elseif ($borrowing->employee) {
                    $borrowerName = $borrowing->employee->full_name;
                    $borrowerId = $borrowing->employee->employee_number;
                } else {
                    // Fallback: try to get student directly if relationship didn't load
                    if ($borrowing->student_number) {
                        $student = Student::where('student_number', $borrowing->student_number)->first();
                        if ($student) {
                            $borrowerName = $student->full_name;
                            $borrowerId = $student->student_number;
                        }
                    }
                    // Fallback: try to get employee directly if relationship didn't load
                    if (empty($borrowerName) && $borrowing->employee_id) {
                        $employee = Employee::where('employee_id', $borrowing->employee_id)->first();
                        if ($employee) {
                            $borrowerName = $employee->full_name;
                            $borrowerId = $employee->employee_number;
                        }
                    }
                    // Additional fallback: if student_number is null, show Unknown Borrower
                    if (empty($borrowerName) && !$borrowing->student_number && !$borrowing->employee_id) {
                        $borrowerName = 'Unknown Borrower';
                        $borrowerId = 'N/A';
                    }
                }

                // Get approver name (for pending, this will be null, but include for consistency)
                $approverName = null;
                if ($borrowing->approver) {
                    if ($borrowing->approver->student) {
                        $approverName = $borrowing->approver->student->full_name;
                    } else {
                        $employee = Employee::where('email', $borrowing->approver->email)->first();
                        $approverName = $employee ? $employee->full_name : $borrowing->approver->email;
                    }
                }

                // Get rejector name (for pending, this will be null, but include for consistency)
                $rejectorName = null;
                if ($borrowing->rejector) {
                    if ($borrowing->rejector->student) {
                        $rejectorName = $borrowing->rejector->student->full_name;
                    } else {
                        $employee = Employee::where('email', $borrowing->rejector->email)->first();
                        $rejectorName = $employee ? $employee->full_name : $borrowing->rejector->email;
                    }
                }

                return [
                    'borrowing_id' => $borrowing->borrowing_id,
                    'borrower_name' => $borrowerName,
                    'borrower_id' => $borrowerId,
                    'item_name' => $borrowing->item_name,
                    'description' => $borrowing->description,
                    'borrow_date' => $borrowing->borrow_date->format('Y-m-d'),
                    'expected_return_date' => $borrowing->expected_return_date->format('Y-m-d'),
                    'notes' => $borrowing->notes,
                    'created_at' => $borrowing->created_at->format('Y-m-d H:i'),
                    'admin_remarks' => $borrowing->admin_remarks,
                    'approved_at' => $borrowing->approved_at ? $borrowing->approved_at->format('Y-m-d H:i') : null,
                    'rejected_at' => $borrowing->rejected_at ? $borrowing->rejected_at->format('Y-m-d H:i') : null,
                    'approver_name' => $approverName,
                    'rejector_name' => $rejectorName,
                ];
            });

        // Get borrowing records
        $borrowingsQuery = SportsBorrowing::with(['student', 'employee', 'approver', 'rejector']);

        if ($request->filled('borrowing_search')) {
            $search = $request->borrowing_search;
            $borrowingsQuery->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery->where('student_number', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('employee', function ($employeeQuery) use ($search) {
                        $employeeQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('borrowing_status')) {
            if ($request->borrowing_status === 'overdue') {
                $borrowingsQuery->overdue();
            } else {
                $borrowingsQuery->where('status', $request->borrowing_status);
            }
        }

        $borrowings = $borrowingsQuery
            ->orderBy('borrow_date', 'desc')
            ->paginate($request->input('perPage', 20), ['*'], 'borrowings_page')
            ->withQueryString();



        // Transform borrowings data
        $borrowings->getCollection()->transform(function ($borrowing) {
            $borrowerName = '';
            $borrowerId = '';

            if ($borrowing->student) {
                $borrowerName = $borrowing->student->full_name;
                $borrowerId = $borrowing->student->student_number;
            } elseif ($borrowing->employee) {
                $borrowerName = $borrowing->employee->full_name;
                $borrowerId = $borrowing->employee->employee_number;
            } else {
                // Fallback: try to get student directly if relationship didn't load
                if ($borrowing->student_number) {
                    $student = Student::where('student_number', $borrowing->student_number)->first();
                    if ($student) {
                        $borrowerName = $student->full_name;
                        $borrowerId = $student->student_number;
                    }
                }
                // Fallback: try to get employee directly if relationship didn't load
                if (empty($borrowerName) && $borrowing->employee_id) {
                    $employee = Employee::where('employee_id', $borrowing->employee_id)->first();
                    if ($employee) {
                        $borrowerName = $employee->full_name;
                        $borrowerId = $employee->employee_number;
                    }
                }
                // Additional fallback: if student_number is null, show Unknown Borrower
                if (empty($borrowerName) && !$borrowing->student_number && !$borrowing->employee_id) {
                    $borrowerName = 'Unknown Borrower';
                    $borrowerId = 'N/A';
                }
            }

            // Get approver name
            $approverName = null;
            if ($borrowing->approver) {
                // Check if approver is a student
                if ($borrowing->approver->student) {
                    $approverName = $borrowing->approver->student->full_name;
                } else {
                    // Try to find employee by email (admin users are typically employees)
                    $employee = Employee::where('email', $borrowing->approver->email)->first();
                    if ($employee) {
                        $approverName = $employee->full_name;
                    } else {
                        // Fallback to email if no employee found
                        $approverName = $borrowing->approver->email;
                    }
                }
            }

            // Get rejector name
            $rejectorName = null;
            if ($borrowing->rejector) {
                // Check if rejector is a student
                if ($borrowing->rejector->student) {
                    $rejectorName = $borrowing->rejector->student->full_name;
                } else {
                    // Try to find employee by email (admin users are typically employees)
                    $employee = Employee::where('email', $borrowing->rejector->email)->first();
                    if ($employee) {
                        $rejectorName = $employee->full_name;
                    } else {
                        // Fallback to email if no employee found
                        $rejectorName = $borrowing->rejector->email;
                    }
                }
            }

            return [
                'borrowing_id' => $borrowing->borrowing_id,
                'borrower_name' => $borrowerName,
                'borrower_id' => $borrowerId,
                'item_name' => $borrowing->item_name,
                'borrow_date' => $borrowing->borrow_date->format('Y-m-d'),
                'expected_return_date' => $borrowing->expected_return_date->format('Y-m-d'),
                'return_date' => $borrowing->return_date ? $borrowing->return_date->format('Y-m-d') : null,
                'status' => $borrowing->formatted_status,
                'is_overdue' => $borrowing->is_overdue,
                'status_color' => $borrowing->status_color,
                'admin_remarks' => $borrowing->admin_remarks,
                'approved_at' => $borrowing->approved_at ? $borrowing->approved_at->format('Y-m-d H:i') : null,
                'rejected_at' => $borrowing->rejected_at ? $borrowing->rejected_at->format('Y-m-d H:i') : null,
                'approver_name' => $approverName,
                'rejector_name' => $rejectorName,
            ];
        });

        // Get students and employees for dropdowns
        $activeCalendar = AcademicCalendar::active()->first();
        $students = Student::whereHas('enrollments', function ($query) use ($activeCalendar) {
            $query->where('enrollment_status', 'enrolled')
                ->when($activeCalendar, fn($q) => $q->where('acad_id', $activeCalendar->calendar_id));
        })
            ->get(['student_number', 'first_name', 'last_name', 'middle_name'])
            ->map(function ($student) {
                return [
                    'student_number' => $student->student_number,
                    'full_name' => $student->full_name,
                ];
            });

        $employees = Employee::get(['employee_id', 'employee_number', 'first_name', 'last_name'])
            ->map(function ($employee) {
                return [
                    'employee_id' => $employee->employee_id,
                    'employee_number' => $employee->employee_number,
                    'full_name' => $employee->full_name,
                ];
            });

        return Inertia::render('Admin/Sports/Index', [
            'borrowings' => $borrowings,
            'pendingBorrowings' => $pendingBorrowings,
            'filters' => $request->only(['borrowing_search', 'borrowing_status']),
            'students' => $students,
            'employees' => $employees,
            'dashboardStats' => [
                [
                    'title' => 'Pending Requests',
                    'value' => $pendingRequests,
                    'color' => 'yellow',
                ],
                [
                    'title' => 'Total Sports',
                    'value' => $totalSports,
                    'color' => 'blue',
                ],
                [
                    'title' => 'Total Athletes',
                    'value' => $totalAthletes,
                    'color' => 'green',
                ],
                [
                    'title' => 'Equipment Borrowed',
                    'value' => $equipmentBorrowed,
                    'color' => 'indigo',
                ],
                [
                    'title' => 'Overdue Items',
                    'value' => $overdueItems,
                    'color' => 'red',
                ],
            ],
        ]);
    }

    /**
     * Store a newly created borrowing record.
     */
    public function storeBorrowing(StoreSportsBorrowingRequest $request)
    {
        SportsBorrowing::create($request->validated());

        return redirect()->route('admin.sports.index')
            ->with('success', 'Equipment borrowing record created successfully.');
    }

    /**
     * Update the specified borrowing record.
     */
    public function updateBorrowing(UpdateSportsBorrowingRequest $request, SportsBorrowing $borrowing)
    {
        $data = $request->validated();

        // If marking as returned, set return_date if not provided
        if ($data['status'] === 'returned' && !isset($data['return_date'])) {
            $data['return_date'] = now()->toDateString();
        }

        $borrowing->update($data);

        return redirect()->route('admin.sports.index')
            ->with('success', 'Borrowing record updated successfully.');
    }

    /**
     * Approve a borrowing request.
     */
    public function approveBorrowing(ApproveSportsBorrowingRequest $request, SportsBorrowing $borrowing)
    {
        $data = $request->validated();

        $borrowing->update([
            'status' => $data['status'] ?? 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'admin_remarks' => $data['admin_remarks'] ?? null,
        ]);

        return redirect()->route('admin.sports.index')
            ->with('success', 'Borrowing request approved successfully.');
    }

    /**
     * Reject a borrowing request.
     */
    public function rejectBorrowing(RejectSportsBorrowingRequest $request, SportsBorrowing $borrowing)
    {
        $data = $request->validated();

        $borrowing->update([
            'status' => 'rejected',
            'rejected_by' => Auth::id(),
            'rejected_at' => now(),
            'admin_remarks' => $data['admin_remarks'],
        ]);

        return redirect()->route('admin.sports.index')
            ->with('success', 'Borrowing request rejected successfully.');
    }

    /**
     * Display the specified borrowing record.
     */
    public function showBorrowing(SportsBorrowing $borrowing): Response
    {
        $borrowing->load(['student', 'employee', 'approver', 'rejector']);

        $borrower = null;
        if ($borrowing->student) {
            $borrower = [
                'type' => 'student',
                'number' => $borrowing->student->student_number,
                'name' => $borrowing->student->full_name,
            ];
        } elseif ($borrowing->employee) {
            $borrower = [
                'type' => 'employee',
                'id' => $borrowing->employee->employee_id,
                'number' => $borrowing->employee->employee_number,
                'name' => $borrowing->employee->full_name,
            ];
        }

        return Inertia::render('Admin/Sports/ShowBorrowing', [
            'borrowing' => [
                'borrowing_id' => $borrowing->borrowing_id,
                'borrower' => $borrower,
                'item_name' => $borrowing->item_name,
                'description' => $borrowing->description,
                'borrow_date' => $borrowing->borrow_date->format('Y-m-d'),
                'expected_return_date' => $borrowing->expected_return_date->format('Y-m-d'),
                'return_date' => $borrowing->return_date ? $borrowing->return_date->format('Y-m-d') : null,
                'status' => $borrowing->formatted_status,
                'is_overdue' => $borrowing->is_overdue,
                'status_color' => $borrowing->status_color,
                'notes' => $borrowing->notes,
                'admin_remarks' => $borrowing->admin_remarks,
                'approved_at' => $borrowing->approved_at ? $borrowing->approved_at->format('Y-m-d H:i') : null,
                'rejected_at' => $borrowing->rejected_at ? $borrowing->rejected_at->format('Y-m-d H:i') : null,
                'approver_name' => $borrowing->approver ? $borrowing->approver->email : null,
                'rejector_name' => $borrowing->rejector ? $borrowing->rejector->email : null,
            ],
        ]);
    }

    /**
     * Display the athletes page — grid of sport cards.
     */
    public function athletes(Request $request): Response
    {
        $sports = Sport::withCount('athletes')
            ->orderBy('name')
            ->get()
            ->map(function ($sport) {
                return [
                    'sport_id' => $sport->sport_id,
                    'name' => $sport->name,
                    'description' => $sport->description,
                    'status' => $sport->status,
                    'athletes_count' => $sport->athletes_count,
                ];
            });

        return Inertia::render('Admin/Sports/Athletes', [
            'sports' => $sports,
        ]);
    }

    /**
     * Store a new sport.
     */
    public function storeSport(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:sports,name',
            'description' => 'nullable|string|max:1000',
        ]);

        Sport::create($request->only('name', 'description'));

        return redirect()->route('admin.sports.athletes')
            ->with('success', 'Sport created successfully.');
    }

    /**
     * Update an existing sport.
     */
    public function updateSport(Request $request, Sport $sport)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:sports,name,' . $sport->sport_id . ',sport_id',
            'description' => 'nullable|string|max:1000',
            'status' => 'nullable|in:active,inactive',
        ]);

        $sport->update($request->only('name', 'description', 'status'));

        return redirect()->route('admin.sports.athletes')
            ->with('success', 'Sport updated successfully.');
    }

    /**
     * Display sport detail — paginated list of athletes.
     */
    public function showSport(Request $request, Sport $sport): Response
    {
        $query = SportAthlete::with(['student.enrollments.section', 'student.enrollments.course'])
            ->where('sport_id', $sport->sport_id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('student_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $athletes = $query
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('perPage', 20))
            ->withQueryString();

        // Get the active academic calendar for section lookup
        $activeCalendar = AcademicCalendar::orderBy('start_date', 'desc')->first();

        $athletes->getCollection()->transform(function ($athlete) use ($activeCalendar) {
            $student = $athlete->student;
            $section = null;
            $course = null;

            if ($student && $activeCalendar) {
                $enrollment = $student->enrollments
                    ->where('acad_id', $activeCalendar->calendar_id)
                    ->first();
                if ($enrollment) {
                    $section = $enrollment->section?->section_name ?? null;
                    $course = $enrollment->course?->course_code ?? null;
                }
            }

            return [
                'id' => $athlete->id,
                'student_number' => $student->student_number ?? '',
                'name' => $student->full_name ?? 'Unknown',
                'section' => $section,
                'course' => $course,
                'added_at' => $athlete->created_at->format('Y-m-d'),
            ];
        });

        // Students available for adding (active enrollments, not already in this sport)
        $existingStudentNumbers = SportAthlete::where('sport_id', $sport->sport_id)
            ->pluck('student_number');

        $activeCalendar = AcademicCalendar::active()->first();
        $availableStudents = Student::whereHas('enrollments', function ($q) use ($activeCalendar) {
            $q->where('enrollment_status', 'enrolled')
                ->when($activeCalendar, fn($q2) => $q2->where('acad_id', $activeCalendar->calendar_id));
        })
            ->whereNotIn('student_number', $existingStudentNumbers)
            ->get(['student_number', 'first_name', 'last_name', 'middle_name'])
            ->map(function ($student) {
                return [
                    'student_number' => $student->student_number,
                    'full_name' => $student->full_name,
                ];
            });

        return Inertia::render('Admin/Sports/ShowSport', [
            'sport' => [
                'sport_id' => $sport->sport_id,
                'name' => $sport->name,
                'description' => $sport->description,
                'status' => $sport->status,
            ],
            'athletes' => $athletes,
            'availableStudents' => $availableStudents,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Add a student athlete to a sport.
     */
    public function storeAthlete(Request $request, Sport $sport)
    {
        $request->validate([
            'student_number' => 'required|exists:students,student_number',
        ]);

        // Check for duplicate
        $exists = SportAthlete::where('sport_id', $sport->sport_id)
            ->where('student_number', $request->student_number)
            ->exists();

        if ($exists) {
            return back()->withErrors(['student_number' => 'This student is already in this sport.']);
        }

        SportAthlete::create([
            'sport_id' => $sport->sport_id,
            'student_number' => $request->student_number,
        ]);

        return redirect()->route('admin.sports.sports.show', $sport)
            ->with('success', 'Athlete added successfully.');
    }

    /**
     * Remove an athlete from a sport.
     */
    public function removeAthlete(Sport $sport, Student $student)
    {
        SportAthlete::where('sport_id', $sport->sport_id)
            ->where('student_number', $student->student_number)
            ->delete();

        return redirect()->route('admin.sports.sports.show', $sport)
            ->with('success', 'Athlete removed successfully.');
    }

    /**
     * Export sports borrowings to PDF.
     */
    public function exportPdf(Request $request)
    {
        $query = SportsBorrowing::with(['student', 'employee']);

        if ($request->filled('borrowing_search')) {
            $search = $request->borrowing_search;
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                    ->orWhereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery->where('student_number', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('employee', function ($employeeQuery) use ($search) {
                        $employeeQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('borrowing_status')) {
            if ($request->borrowing_status === 'overdue') {
                $query->overdue();
            } else {
                $query->where('status', $request->borrowing_status);
            }
        }

        $borrowings = $query->orderBy('borrow_date', 'desc')->get();

        $headers = ['ID', 'Borrower', 'Item', 'Borrow Date', 'Expected Return', 'Return Date', 'Status'];
        $rows = $borrowings->map(function ($b) {
            $borrowerName = '';
            if ($b->student) {
                $borrowerName = $b->student->full_name;
            } elseif ($b->employee) {
                $borrowerName = $b->employee->full_name;
            }
            return [
                $b->borrowing_id,
                $borrowerName ?: 'Unknown',
                $b->item_name,
                $b->borrow_date->format('Y-m-d'),
                $b->expected_return_date->format('Y-m-d'),
                $b->return_date ? $b->return_date->format('Y-m-d') : '—',
                $b->formatted_status,
            ];
        })->toArray();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.pdf-table', [
            'title' => 'Sports Equipment Borrowings Report',
            'date' => now()->format('F j, Y g:i A'),
            'headers' => $headers,
            'rows' => $rows,
            'filters' => $request->only(['borrowing_search', 'borrowing_status']),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('sports_borrowings_export_' . date('Y-m-d_His') . '.pdf');
    }

    /**
     * Update only the status of a borrowing record (from progress bar).
     */
    public function updateBorrowingStatus(Request $request, SportsBorrowing $borrowing)
    {
        $request->validate([
            'status' => 'required|string|in:pending,approved,borrowed,returned,rejected,overdue',
        ]);

        $newStatus = $request->input('status');

        if ($borrowing->status === $newStatus) {
            return redirect()->back();
        }

        $updateData = ['status' => $newStatus];

        if ($newStatus === 'returned' && !$borrowing->return_date) {
            $updateData['return_date'] = now()->toDateString();
        }
        if ($newStatus === 'approved' && !$borrowing->approved_at) {
            $updateData['approved_at'] = now();
            $updateData['approved_by'] = Auth::id();
        }
        if ($newStatus === 'rejected' && !$borrowing->rejected_at) {
            $updateData['rejected_at'] = now();
            $updateData['rejected_by'] = Auth::id();
        }

        $borrowing->update($updateData);

        return redirect()->route('admin.sports.borrowings.show', $borrowing)
            ->with('success', "Borrowing status updated to {$newStatus}.");
    }
}
