<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDisciplineRequest;
use App\Http\Requests\Admin\UpdateDisciplineRequest;
use App\Models\Discipline;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DisciplineController extends Controller
{
    /**
     * Display a listing of violations.
     */
    public function index(Request $request): Response
    {
        // Get dashboard statistics
        $totalViolations = Discipline::count();
        $pendingCases = Discipline::where('status', 'Pending')->count();
        $resolvedCases = Discipline::where('status', 'Resolved')->count();
        $majorCases = Discipline::where('severity', 'Major')->count();

        // Build query with search and filters
        $query = Discipline::with(['student', 'reportedBy']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('student', function ($studentQuery) use ($search) {
                    $studentQuery->where('student_number', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('violation_type', 'like', "%{$search}%");
            });
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Paginate results
        $violations = $query->orderBy('violation_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Transform data for frontend
        $violations->getCollection()->transform(function ($violation) {
            return [
                'discipline_id' => $violation->discipline_id,
                'student_number' => $violation->student->student_number ?? '',
                'student_name' => $violation->student->full_name ?? '',
                'violation_date' => $violation->violation_date->format('Y-m-d'),
                'violation_type' => $violation->violation_type,
                'description' => $violation->description,
                'severity' => $violation->severity,
                'status' => $violation->status,
                'reported_by' => $violation->reportedBy->email ?? null,
                'severity_color' => $violation->severity_color,
                'status_color' => $violation->status_color,
            ];
        });

        // Get students list for dropdown (only active students with enrollments)
        $students = Student::whereHas('enrollments', function ($query) {
            $query->where('enrollment_status', 'active');
        })
        //->with('currentEnrollment')
        ->get()
        ->map(function ($student) {
            return [
                'student_number' => $student->student_number,
                'full_name' => $student->full_name,
            ];
        });

        return Inertia::render('Admin/Discipline/Index', [
            'violations' => $violations,
            'filters' => $request->only(['search', 'severity', 'status']),
            'students' => $students,
            'dashboardStats' => [
                [
                    'title' => 'Total Violations',
                    'value' => $totalViolations,
                    'color' => 'blue',
                ],
                [
                    'title' => 'Pending Cases',
                    'value' => $pendingCases,
                    'color' => 'yellow',
                ],
                [
                    'title' => 'Resolved Cases',
                    'value' => $resolvedCases,
                    'color' => 'green',
                ],
                [
                    'title' => 'Major Cases',
                    'value' => $majorCases,
                    'color' => 'red',
                ],
            ],
        ]);
    }

    /**
     * Store a newly created violation.
     */
    public function store(StoreDisciplineRequest $request)
    {
        $data = $request->validated();
        
        // Set reported_by to current user if not provided
        if (!isset($data['reported_by'])) {
            $data['reported_by'] = Auth::id();
        }

        Discipline::create($data);

        return redirect()->route('admin.discipline.index')
            ->with('success', 'Violation record created successfully.');
    }

    /**
     * Display the specified violation.
     */
    public function show(Discipline $discipline): Response
    {
        $discipline->load(['student', 'reportedBy']);

        return Inertia::render('Admin/Discipline/Show', [
            'violation' => [
                'discipline_id' => $discipline->discipline_id,
                'student' => [
                    'student_number' => $discipline->student->student_number,
                    'full_name' => $discipline->student->full_name,
                ],
                'violation_date' => $discipline->violation_date->format('Y-m-d'),
                'violation_type' => $discipline->violation_type,
                'description' => $discipline->description,
                'severity' => $discipline->severity,
                'status' => $discipline->status,
                'reported_by' => $discipline->reportedBy ? [
                    'user_id' => $discipline->reportedBy->user_id,
                    'email' => $discipline->reportedBy->email,
                ] : null,
                'created_at' => $discipline->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $discipline->updated_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Update the specified violation.
     */
    public function update(UpdateDisciplineRequest $request, Discipline $discipline)
    {
        $data = $request->validated();
        
        // Set reported_by to current user if not provided
        if (!isset($data['reported_by'])) {
            $data['reported_by'] = Auth::id();
        }

        $discipline->update($data);

        return redirect()->route('admin.discipline.index')
            ->with('success', 'Violation record updated successfully.');
    }
}

