<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index()
    {
        $roles = Role::orderBy('role_name')->get();
        return response()->json($roles);
    }

    /**
     * Store a newly created role.
     */
    public function store(StoreRoleRequest $request)
    {
        try {
            $role = Role::create([
                'role_name' => $request->role_name,
            ]);

            $userId = Auth::check() ? Auth::user()->user_id : 'unknown';
            Log::info("Role created: {$role->role_name} by user {$userId}");

            return response()->json([
                'success' => true,
                'message' => 'Role created successfully.',
                'role' => $role,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create role: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create role: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to create role. Please try again.',
            ], 500);
        }
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        try {
            $role->update([
                'role_name' => $request->role_name,
            ]);

            $userId = Auth::check() ? Auth::user()->user_id : 'unknown';
            Log::info("Role updated: {$role->role_name} by user {$userId}");

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully.',
                'role' => $role,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update role: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update role. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to update role. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role)
    {
        try {
            // Check if role is in use
            $usersWithRole = $role->users()->count();
            if ($usersWithRole > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete role. It is assigned to {$usersWithRole} user(s).",
                ], 422);
            }

            $roleName = $role->role_name;
            $role->delete();

            $userId = Auth::check() ? Auth::user()->user_id : 'unknown';
            Log::info("Role deleted: {$roleName} by user {$userId}");

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to delete role: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Failed to delete role. Please try again.',
            ], 500);
        }
    }
}
