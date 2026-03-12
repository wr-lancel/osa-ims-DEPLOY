<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicCalendar;
use App\Models\Course;
use App\Models\Discipline;
use App\Models\DisciplineViolationType;
use App\Models\DisciplineWorkflowStep;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Services\ModuleAuthorizationService;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Display the settings management page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $moduleService = app(ModuleAuthorizationService::class);
        $accessibleModules = $moduleService->getAccessibleModules($user);

        // Full admins have access to the 'students' module
        $isFullAdmin = in_array('students', $accessibleModules, true);
        $hasDiscipline = in_array('discipline', $accessibleModules, true);

        // Determine which tabs this user can see
        if ($isFullAdmin) {
            $availableTabs = ['calendars', 'courses', 'roles', 'discipline-workflow', 'violation-types', 'lookup-values'];
        } elseif ($hasDiscipline) {
            $availableTabs = ['discipline-workflow', 'violation-types', 'lookup-values'];
        } else {
            $availableTabs = ['lookup-values'];
        }

        // ── Lookup values (filtered to accessible modules) ──
        $accessibleLookupKeys = SystemSetting::getAccessibleLookupKeys($accessibleModules);
        $lookupValues = [];
        foreach ($accessibleLookupKeys as $key) {
            $lookupValues[$key] = SystemSetting::getList($key);
        }

        // ── Admin-only data ──
        $academicCalendars = ['data' => [], 'links' => [], 'meta' => ['total' => 0]];
        $courses = ['data' => [], 'links' => [], 'meta' => ['total' => 0]];
        $roles = [];

        if ($isFullAdmin) {
            $perPage = $request->input('perPage', 20);

            $academicCalendars = AcademicCalendar::orderBy('start_date', 'desc')
                ->paginate($perPage, ['*'], 'calendars_page')
                ->withQueryString()
                ->through(function ($calendar) {
                    return [
                        'calendar_id' => $calendar->calendar_id,
                        'academic_year' => $calendar->academic_year,
                        'semester' => $calendar->semester,
                        'start_date' => $calendar->start_date->format('Y-m-d'),
                        'end_date' => $calendar->end_date->format('Y-m-d'),
                        'status' => $calendar->status,
                        'display_label' => $calendar->display_label,
                        'enrollments_count' => $calendar->enrolledStudents()->count(),
                    ];
                });

            $courses = Course::withCount('sections')
                ->orderBy('course_name')
                ->paginate($perPage, ['*'], 'courses_page')
                ->withQueryString()
                ->through(function ($course) {
                    return [
                        'course_id' => $course->course_id,
                        'course_code' => $course->course_code,
                        'course_name' => $course->course_name,
                        'description' => $course->description,
                        'sections_count' => $course->sections_count,
                    ];
                });

            $roles = Role::withCount('users')->orderBy('role_name')->get()->map(fn($role) => [
                'role_id' => $role->role_id,
                'role_name' => $role->role_name,
                'users_count' => $role->users_count,
            ]);
        }

        // ── Discipline-specific data ──
        $disciplineWorkflowSteps = [];
        $disciplineViolationTypes = [];

        if ($isFullAdmin || $hasDiscipline) {
            $disciplineWorkflowSteps = DisciplineWorkflowStep::ordered()->get()->map(fn($step) => [
                'id' => $step->id,
                'name' => $step->name,
                'description' => $step->description,
                'sort_order' => $step->sort_order,
                'is_terminal' => $step->is_terminal,
                'cases_count' => Discipline::where('status', $step->name)->count(),
            ]);

            $disciplineViolationTypes = DisciplineViolationType::ordered()->get()->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'severity' => $t->severity,
                'description' => $t->description,
                'default_sanction' => $t->default_sanction,
                'sort_order' => $t->sort_order,
                'cases_count' => Discipline::where('violation_type', $t->name)->where('severity', $t->severity)->count(),
            ]);
        }

        return Inertia::render('Admin/Settings/Index', [
            'availableTabs' => $availableTabs,
            'academicCalendars' => $academicCalendars,
            'courses' => $courses,
            'disciplineWorkflowSteps' => $disciplineWorkflowSteps,
            'disciplineViolationTypes' => $disciplineViolationTypes,
            'roles' => $roles,
            'lookupValues' => $lookupValues,
        ]);
    }

    /**
     * Store a new discipline workflow step.
     */
    public function storeDisciplineStep(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:discipline_workflow_steps,name',
            'description' => 'nullable|string|max:500',
            'is_terminal' => 'boolean',
        ]);

        DisciplineWorkflowStep::create([
            'name' => $request->name,
            'description' => $request->description,
            'sort_order' => DisciplineWorkflowStep::nextSortOrder(),
            'is_terminal' => $request->boolean('is_terminal'),
        ]);

        return redirect()->route('admin.settings')
            ->with('success', 'Workflow step added successfully.');
    }

    /**
     * Update a discipline workflow step.
     */
    public function updateDisciplineStep(Request $request, DisciplineWorkflowStep $step): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('discipline_workflow_steps', 'name')->ignore($step->id)],
            'description' => 'nullable|string|max:500',
            'is_terminal' => 'boolean',
        ]);

        $oldName = $step->name;
        $newName = $request->name;

        $step->update([
            'name' => $newName,
            'description' => $request->description,
            'is_terminal' => $request->boolean('is_terminal'),
        ]);

        // If the name changed, update all discipline records using the old name
        if ($oldName !== $newName) {
            Discipline::where('status', $oldName)->update(['status' => $newName]);
        }

        return redirect()->route('admin.settings')
            ->with('success', 'Workflow step updated successfully.');
    }

    /**
     * Delete a discipline workflow step (blocked if cases exist).
     */
    public function destroyDisciplineStep(DisciplineWorkflowStep $step): RedirectResponse
    {
        $casesCount = Discipline::where('status', $step->name)->count();

        if ($casesCount > 0) {
            return redirect()->route('admin.settings')
                ->withErrors(['workflow' => "Cannot delete \"{$step->name}\" — {$casesCount} case(s) are currently at this status. Reassign them first."]);
        }

        $deletedOrder = $step->sort_order;
        $step->delete();

        // Re-rank remaining steps to close the gap
        DisciplineWorkflowStep::where('sort_order', '>', $deletedOrder)
            ->decrement('sort_order');

        return redirect()->route('admin.settings')
            ->with('success', 'Workflow step deleted successfully.');
    }

    /**
     * Reorder discipline workflow steps.
     */
    public function reorderDisciplineSteps(Request $request): RedirectResponse
    {
        $request->validate([
            'steps' => 'required|array',
            'steps.*.id' => 'required|integer|exists:discipline_workflow_steps,id',
            'steps.*.sort_order' => 'required|integer|min:1',
        ]);

        foreach ($request->steps as $item) {
            DisciplineWorkflowStep::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        return redirect()->route('admin.settings')
            ->with('success', 'Workflow step order updated successfully.');
    }

    // ─── Violation Type CRUD ──────────────────────────────

    /**
     * Store a new violation type.
     */
    public function storeViolationType(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'severity' => 'required|in:Minor,Moderate,Major',
            'description' => 'nullable|string|max:500',
            'default_sanction' => 'nullable|string|max:1000',
        ]);

        // Check uniqueness within severity
        $exists = DisciplineViolationType::where('name', $request->name)
            ->where('severity', $request->severity)
            ->exists();

        if ($exists) {
            return redirect()->route('admin.settings')
                ->withErrors(['violation_type' => "A type named \"{$request->name}\" already exists under {$request->severity}."]);
        }

        DisciplineViolationType::create([
            'name' => $request->name,
            'severity' => $request->severity,
            'description' => $request->description,
            'default_sanction' => $request->default_sanction,
            'sort_order' => DisciplineViolationType::nextSortOrder($request->severity),
        ]);

        return redirect()->route('admin.settings')
            ->with('success', 'Violation type added successfully.');
    }

    /**
     * Update a violation type.
     */
    public function updateViolationType(Request $request, DisciplineViolationType $type): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'severity' => 'required|in:Minor,Moderate,Major',
            'description' => 'nullable|string|max:500',
            'default_sanction' => 'nullable|string|max:1000',
        ]);

        // Check uniqueness (excluding self)
        $exists = DisciplineViolationType::where('name', $request->name)
            ->where('severity', $request->severity)
            ->where('id', '!=', $type->id)
            ->exists();

        if ($exists) {
            return redirect()->route('admin.settings')
                ->withErrors(['violation_type' => "A type named \"{$request->name}\" already exists under {$request->severity}."]);
        }

        $oldName = $type->name;
        $oldSeverity = $type->severity;

        $type->update([
            'name' => $request->name,
            'severity' => $request->severity,
            'description' => $request->description,
            'default_sanction' => $request->default_sanction,
        ]);

        // If name changed, update existing discipline records
        if ($oldName !== $request->name) {
            Discipline::where('violation_type', $oldName)
                ->where('severity', $oldSeverity)
                ->update(['violation_type' => $request->name]);
        }

        return redirect()->route('admin.settings')
            ->with('success', 'Violation type updated successfully.');
    }

    /**
     * Delete a violation type (blocked if cases exist).
     */
    public function destroyViolationType(DisciplineViolationType $type): RedirectResponse
    {
        $casesCount = Discipline::where('violation_type', $type->name)
            ->where('severity', $type->severity)
            ->count();

        if ($casesCount > 0) {
            return redirect()->route('admin.settings')
                ->withErrors(['violation_type' => "Cannot delete \"{$type->name}\" — {$casesCount} case(s) use this type. Reassign them first."]);
        }

        $type->delete();

        return redirect()->route('admin.settings')
            ->with('success', 'Violation type deleted successfully.');
    }

    // ─── Lookup Values CRUD ──────────────────────────────

    /**
     * Update a lookup value list.
     */
    public function updateLookupValues(Request $request): RedirectResponse
    {
        $user = $request->user();
        $moduleService = app(ModuleAuthorizationService::class);
        $accessibleModules = $moduleService->getAccessibleModules($user);
        $allowedKeys = SystemSetting::getAccessibleLookupKeys($accessibleModules);

        $request->validate([
            'key' => ['required', 'string', 'in:' . implode(',', $allowedKeys)],
            'values' => ['required', 'array', 'min:1'],
            'values.*' => ['required', 'string', 'max:255'],
        ]);

        // Ensure no duplicate values
        $values = array_unique(array_map('trim', $request->values));

        SystemSetting::setList($request->key, $values);

        return redirect()->route('admin.settings')
            ->with('success', 'Lookup values updated successfully.');
    }
}
