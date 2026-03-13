<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Requests\Admin\UpdateStaffRequest;
use App\Models\Employee;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    /**
     * Display a listing of employees.
     */
    public function index(Request $request): Response
    {
        $query = Employee::with(['user.roles']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('employee_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Department filter
        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        // Position filter
        if ($request->filled('position')) {
            $query->where('position', $request->position);
        }

        // Role filter
        if ($request->filled('role_id')) {
            $query->whereHas('user.roles', function ($q) use ($request) {
                $q->where('roles.role_id', $request->role_id);
            });
        }

        // Status filter (from users table)
        if ($request->filled('status')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        $sortBy = in_array($request->input('sort_by'), ['last_name', 'first_name', 'department', 'position']) ? $request->input('sort_by') : 'employee_id';
        $sortDir = $request->input('sort_dir') === 'asc' ? 'asc' : 'desc';

        $employees = $query->orderBy($sortBy, $sortDir)
            ->paginate($request->input('perPage', 20))
            ->withQueryString()
            ->through(function ($employee) {
                return [
                    'employee_id' => $employee->employee_id,
                    'employee_number' => $employee->employee_number,
                    'first_name' => $employee->first_name,
                    'last_name' => $employee->last_name,
                    'full_name' => $employee->full_name,
                    'email' => $employee->email,
                    'phone' => $employee->phone,
                    'department' => $employee->department,
                    'position' => $employee->position,
                    'roles' => $employee->user ? $employee->user->roles->map(fn($r) => [
                        'role_id' => $r->role_id,
                        'role_name' => $r->role_name,
                    ]) : collect([]),
                    'status' => $employee->user ? $employee->user->status : null,
                ];
            });

        // Get unique departments and positions for filter dropdowns
        $departments = Employee::whereNotNull('department')
            ->distinct()
            ->pluck('department')
            ->sort()
            ->values();

        $positions = Employee::whereNotNull('position')
            ->distinct()
            ->pluck('position')
            ->sort()
            ->values();

        // Get all roles for filter dropdown
        $roles = Role::orderBy('role_name')->get()->map(fn($r) => [
            'role_id' => $r->role_id,
            'role_name' => $r->role_name,
        ]);

        return Inertia::render('Admin/Staff/Index', [
            'employees' => $employees,
            'filters' => $request->only(['search', 'department', 'position', 'role_id', 'status', 'sort_by', 'sort_dir']),
            'departments' => $departments,
            'positions' => $positions,
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created employee with user account and role.
     */
    public function store(StoreStaffRequest $request)
    {
        try {
            DB::beginTransaction();

            // Create employee
            $employee = Employee::create([
                'employee_number' => $request->employee_number,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'department' => $request->department,
                'position' => $request->position,
            ]);

            // Create user account
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'status' => 'active',
                'employee_id' => $employee->employee_id,
            ]);

            // Assign role
            if ($request->filled('role_id')) {
                $role = Role::find($request->role_id);
                if ($role) {
                    $user->roles()->attach($role->role_id);
                }
            }

            DB::commit();

            $userId = Auth::check() ? Auth::user()->user_id : 'unknown';
            Log::info("Employee created: {$employee->employee_number} by user {$userId}");

            return response()->json([
                'success' => true,
                'message' => 'Staff member created successfully.',
                'employee' => $employee->load(['user.roles']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to create employee: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create staff member: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to create staff member. Please try again.',
            ], 500);
        }
    }

    /**
     * Update the specified employee and user account.
     */
    public function update(UpdateStaffRequest $request, Employee $employee)
    {
        try {
            DB::beginTransaction();

            // Update employee
            $employee->update([
                'employee_number' => $request->employee_number,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'department' => $request->department,
                'position' => $request->position,
            ]);

            // Update user account if exists
            if ($employee->user) {
                $userData = [
                    'email' => $request->email,
                ];

                // Update password if provided
                if ($request->filled('password')) {
                    $userData['password'] = Hash::make($request->password);
                }

                $employee->user->update($userData);

                // Update role assignment
                if ($request->filled('role_id')) {
                    // Remove all existing roles
                    $employee->user->roles()->detach();
                    // Attach new role
                    $role = Role::find($request->role_id);
                    if ($role) {
                        $employee->user->roles()->attach($role->role_id);
                    }
                }
            }

            DB::commit();

            $userId = Auth::check() ? Auth::user()->user_id : 'unknown';
            Log::info("Employee updated: {$employee->employee_number} by user {$userId}");

            return response()->json([
                'success' => true,
                'message' => 'Staff member updated successfully.',
                'employee' => $employee->load(['user.roles']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update employee: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update staff member: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to update staff member. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove the specified employee.
     */
    public function destroy(Employee $employee)
    {
        try {
            DB::beginTransaction();

            // Delete user account if exists (this will cascade delete user_roles)
            if ($employee->user) {
                $employee->user->delete();
            }

            // Delete employee
            $employee->delete();

            DB::commit();

            $userId = Auth::check() ? Auth::user()->user_id : 'unknown';
            Log::info("Employee deleted: {$employee->employee_number} by user {$userId}");

            return response()->json([
                'success' => true,
                'message' => 'Staff member deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete employee: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete staff member. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to delete staff member. Please try again.',
            ], 500);
        }
    }

    /**
     * Export staff members to PDF.
     */
    public function exportPdf(Request $request)
    {
        $query = Employee::with(['user.roles']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('employee_number', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('position')) {
            $query->where('position', $request->position);
        }

        if ($request->filled('role_id')) {
            $query->whereHas('user.roles', function ($q) use ($request) {
                $q->where('roles.role_id', $request->role_id);
            });
        }

        if ($request->filled('status')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('status', $request->status);
            });
        }

        $employees = $query->orderBy('employee_id', 'desc')->get();

        $headers = ['Employee No', 'Name', 'Email', 'Department', 'Position', 'Role', 'Status'];
        $rows = $employees->map(fn($e) => [
            $e->employee_number,
            $e->full_name,
            $e->email,
            $e->department ?? '—',
            $e->position ?? '—',
            $e->user ? $e->user->roles->pluck('role_name')->join(', ') : '—',
            $e->user?->status ?? '—',
        ])->toArray();

        // Build human-readable filter labels
        $filterLabels = [];
        if ($request->filled('search')) {
            $filterLabels['Search'] = $request->search;
        }
        if ($request->filled('department')) {
            $filterLabels['Department'] = $request->department;
        }
        if ($request->filled('position')) {
            $filterLabels['Position'] = $request->position;
        }
        if ($request->filled('role_id')) {
            $role = Role::find($request->role_id);
            $filterLabels['Role'] = $role ? $role->role_name : $request->role_id;
        }
        if ($request->filled('status')) {
            $filterLabels['Status'] = ucfirst($request->status);
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.pdf-table', [
            'title' => 'Staff Members Report',
            'date' => now()->format('F j, Y g:i A'),
            'headers' => $headers,
            'rows' => $rows,
            'filters' => $filterLabels,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('staff_export_' . date('Y-m-d_His') . '.pdf');
    }
}
