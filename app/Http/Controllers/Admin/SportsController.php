<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveSportsBorrowingRequest;
use App\Http\Requests\Admin\RejectSportsBorrowingRequest;
use App\Http\Requests\Admin\StoreSportsBorrowingRequest;
use App\Http\Requests\Admin\UpdateSportsBorrowingRequest;
use App\Models\SportsBorrowing;
use App\Models\Employee;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SportsController extends Controller
{
    /**
     * Display sports dashboard with officers and equipment borrowing.
     */
    public function index(Request $request): Response
    {
        // Get dashboard statistics
        // Sports Officers count - employees with position related to sports
        $sportsOfficersCount = Employee::where('position', 'like', '%sport%')
            ->orWhere('department', 'like', '%sport%')
            ->count();
        
        $equipmentBorrowed = SportsBorrowing::where('status', 'borrowed')->count();
        $overdueItems = SportsBorrowing::overdue()->count();
        $pendingRequests = SportsBorrowing::where('status', 'pending')->count();
        
        // Events this month (if events table has org_id for sports)
        $eventsThisMonth = 0; // Placeholder - can be enhanced later

        // Get officers list
        $officersQuery = Employee::query();
        
        if ($request->filled('officer_search')) {
            $search = $request->officer_search;
            $officersQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            });
        }

        $officers = $officersQuery
            ->where(function ($q) {
                $q->where('position', 'like', '%sport%')
                    ->orWhere('department', 'like', '%sport%');
            })
            ->orderBy('first_name')
            ->paginate(15, ['*'], 'officers_page')
            ->withQueryString();

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
            ->paginate(15, ['*'], 'borrowings_page')
            ->withQueryString();

        // Transform officers data
        $officers->getCollection()->transform(function ($officer) {
            return [
                'employee_id' => $officer->employee_id,
                'employee_number' => $officer->employee_number,
                'name' => $officer->full_name,
                'position' => $officer->position,
                'department' => $officer->department,
                'contact' => $officer->email ?? $officer->phone,
            ];
        });

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
        $students = Student::whereHas('enrollments', function ($query) {
            $query->where('enrollment_status', 'active');
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
            'officers' => $officers,
            'borrowings' => $borrowings,
            'pendingBorrowings' => $pendingBorrowings,
            'filters' => $request->only(['officer_search', 'borrowing_search', 'borrowing_status']),
            'students' => $students,
            'employees' => $employees,
            'dashboardStats' => [
                [
                    'title' => 'Pending Requests',
                    'value' => $pendingRequests,
                    'color' => 'yellow',
                ],
                [
                    'title' => 'Sports Officers',
                    'value' => $sportsOfficersCount,
                    'color' => 'blue',
                ],
                [
                    'title' => 'Equipment Borrowed',
                    'value' => $equipmentBorrowed,
                    'color' => 'green',
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
}

