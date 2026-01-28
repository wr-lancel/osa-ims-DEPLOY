<?php

use App\Http\Controllers\Admin\AcademicCalendarController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DisciplineController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\GuidanceController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SportsController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StudentRecordController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\SportsController as StudentSportsController;
use App\Http\Controllers\Student\GuidanceController as StudentGuidanceController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentOrganizationController;
use App\Models\User;
use App\Services\ModuleAuthorizationService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public Routes
Route::get('/', fn() => Inertia::render('Welcome', [
    'canLogin' => Route::has('login'),
    'canRegister' => Route::has('register'),
    'laravelVersion' => Application::VERSION,
    'phpVersion' => PHP_VERSION,
]));

// Authentication Routes
require __DIR__.'/auth.php';

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Legacy Dashboard Redirect
    Route::get('/dashboard', function () {
        /** @var User $user */
        $user = Auth::user();
        $user->load('roles');

        // Check if user has access to admin dashboard (any admin role)
        $moduleAuth = app(ModuleAuthorizationService::class);
        $hasAdminAccess = $moduleAuth->hasAccess($user, ModuleAuthorizationService::MODULE_DASHBOARD);

        return match (true) {
            $hasAdminAccess => redirect()->route('admin.dashboard'),
            $user->hasRole('student') => redirect()->route('student.dashboard'),
            default => redirect()->route('admin.dashboard'),
        };
    })->middleware('verified')->name('dashboard');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Admin Routes - Allow admin, super_admin, staff, and module admins
    Route::prefix('admin')->name('admin.')->middleware([
        'role:admin,super_admin,staff,sports_admin,organization_admin,discipline_admin,guidance_admin'
    ])->group(function () {
        // Dashboard - accessible to all admin roles
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->middleware('module:dashboard')
            ->name('dashboard');

        // Student Records (Admin/Super Admin only)
        Route::prefix('students')->name('students.')
            ->middleware(['role:admin,super_admin', 'module:students'])
            ->group(function () {
                Route::post('/import', [StudentRecordController::class, 'import'])->name('import');
                Route::get('/export/csv', [StudentRecordController::class, 'export'])->name('export');
                Route::get('/sections/list', [StudentRecordController::class, 'getSections'])->name('sections.list');
                Route::get('/academic-calendars', [StudentRecordController::class, 'getAcademicCalendars'])->name('academic-calendars');
                Route::patch('/enrollments/{enrollment}/status', [StudentRecordController::class, 'updateStatus'])
                    ->name('enrollments.updateStatus');
                
                // Account management routes
                Route::post('/accounts/bulk-create', [StudentRecordController::class, 'bulkCreateAccounts'])->name('accounts.bulk-create');
                Route::post('/{student}/account', [StudentRecordController::class, 'createAccount'])->name('account.create');
                Route::delete('/{student}/account', [StudentRecordController::class, 'deleteAccount'])->name('account.delete');
                
                Route::get('/', [StudentRecordController::class, 'index'])->name('index');
                Route::post('/', [StudentRecordController::class, 'store'])->name('store');
                Route::get('/{student}/profile', [StudentRecordController::class, 'profile'])->name('profile');
                Route::get('/{student}', [StudentRecordController::class, 'show'])->name('show');
                Route::put('/{student}', [StudentRecordController::class, 'update'])->name('update');
            });

        // Resource Routes (Admin/Super Admin only)
        Route::middleware(['role:admin,super_admin', 'module:staff,roles,courses,sections,academic-calendars'])->group(function () {
            Route::resource('staff', StaffController::class)
                ->except(['show', 'create', 'edit'])
                ->parameters(['staff' => 'employee']);
            Route::resource('roles', RoleController::class)->except(['show', 'create', 'edit']);
            Route::resource('courses', CourseController::class)->except(['show', 'create', 'edit']);
            Route::resource('sections', SectionController::class)->except(['show', 'create', 'edit']);
            Route::resource('academic-calendars', AcademicCalendarController::class)
                ->except(['show', 'create', 'edit'])
                ->parameters(['academic-calendars' => 'calendar']);
        });

        // Guidance Module Routes
        Route::prefix('guidance')->name('guidance.')
            ->middleware('module:guidance')
            ->group(function () {
                Route::get('/enrollments/list', [GuidanceController::class, 'getEnrollments'])->name('enrollments.list');
                Route::post('/actions', [GuidanceController::class, 'addAction'])->name('actions.store');
                Route::get('/', [GuidanceController::class, 'index'])->name('index');
                Route::post('/', [GuidanceController::class, 'store'])->name('store');
                Route::get('/{case:guidance_case_id}', [GuidanceController::class, 'show'])->name('show');
                Route::put('/{case:guidance_case_id}', [GuidanceController::class, 'update'])->name('update');
                Route::delete('/{case:guidance_case_id}', [GuidanceController::class, 'destroy'])->name('destroy');
                
                // Appointment routes
                Route::get('/appointments', [GuidanceController::class, 'appointments'])->name('appointments.index');
                Route::post('/appointments/{appointment}/approve', [GuidanceController::class, 'approveAppointment'])->name('appointments.approve');
                Route::post('/appointments/{appointment}/reject', [GuidanceController::class, 'rejectAppointment'])->name('appointments.reject');
                Route::get('/appointments/{appointment}', [GuidanceController::class, 'showAppointment'])->name('appointments.show');
                Route::put('/appointments/{appointment}', [GuidanceController::class, 'updateAppointment'])->name('appointments.update');
            });

        // Discipline Module Routes
        Route::prefix('discipline')->name('discipline.')
            ->middleware(['role:admin,super_admin,discipline_admin', 'module:discipline'])
            ->group(function () {
                Route::get('/', [DisciplineController::class, 'index'])->name('index');
                Route::post('/', [DisciplineController::class, 'store'])->name('store');
                Route::get('/{discipline}', [DisciplineController::class, 'show'])->name('show');
                Route::put('/{discipline}', [DisciplineController::class, 'update'])->name('update');
            });

        // Organization Module Routes
        Route::prefix('organizations')->name('organizations.')
            ->middleware(['role:admin,super_admin,organization_admin', 'module:organizations'])
            ->group(function () {
                // Events (must be before dynamic {organization} routes)
                Route::prefix('events')->name('events.')->group(function () {
                    Route::get('/', [EventController::class, 'index'])->name('index');
                    Route::post('/', [EventController::class, 'store'])->name('store');
                    Route::get('/{event}', [EventController::class, 'show'])->name('show');
                    Route::put('/{event}', [EventController::class, 'update'])->name('update');
                });

                // Organizations
                Route::get('/', [OrganizationController::class, 'index'])->name('index');
                Route::post('/', [OrganizationController::class, 'store'])->name('store');
                Route::get('/{organization}', [OrganizationController::class, 'show'])->name('show');
                Route::put('/{organization}', [OrganizationController::class, 'update'])->name('update');
                
                // Officer Management
                Route::post('/{organization}/officers', [OrganizationController::class, 'addOfficer'])->name('officers.add');
                Route::delete('/{organization}/officers/{officer}', [OrganizationController::class, 'removeOfficer'])->name('officers.remove');
                
                // Adviser Management
                Route::put('/{organization}/adviser', [OrganizationController::class, 'updateAdviser'])->name('adviser.update');
            });

        // Sports Module Routes
        Route::prefix('sports')->name('sports.')
            ->middleware(['role:admin,super_admin,sports_admin', 'module:sports'])
            ->group(function () {
                Route::get('/', [SportsController::class, 'index'])->name('index');
                Route::post('/borrowings', [SportsController::class, 'storeBorrowing'])->name('borrowings.store');
                Route::post('/borrowings/{borrowing}/approve', [SportsController::class, 'approveBorrowing'])->name('borrowings.approve');
                Route::post('/borrowings/{borrowing}/reject', [SportsController::class, 'rejectBorrowing'])->name('borrowings.reject');
                Route::put('/borrowings/{borrowing}', [SportsController::class, 'updateBorrowing'])->name('borrowings.update');
                Route::get('/borrowings/{borrowing}', [SportsController::class, 'showBorrowing'])->name('borrowings.show');
            });

        // Settings (Admin/Super Admin only)
        Route::get('/settings', [SettingsController::class, 'index'])
            ->middleware('role:admin,super_admin')
            ->name('settings');
    });

    // Student Routes
    Route::prefix('student')->name('student.')->middleware('role:student')->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', fn() => Inertia::render('Student/Profile'))->name('profile');
        Route::get('/requests', fn() => Inertia::render('Student/Requests/Index'))->name('requests.index');
        Route::get('/settings', fn() => Inertia::render('Student/Settings'))->name('settings');
        
        // Student Unit Modules
        Route::get('/discipline', fn() => Inertia::render('Student/Discipline/Index'))->name('discipline.index');
        
        // Student Guidance Module
        Route::prefix('guidance')->name('guidance.')->group(function () {
            Route::get('/', [StudentGuidanceController::class, 'index'])->name('index');
            Route::post('/appointments', [StudentGuidanceController::class, 'store'])->name('appointments.store');
            Route::get('/appointments/{appointment}', [StudentGuidanceController::class, 'show'])->name('appointments.show');
        });
        
        // Student Sports Module
        Route::prefix('sports')->name('sports.')->group(function () {
            Route::get('/', [StudentSportsController::class, 'index'])->name('index');
            Route::post('/borrowings', [StudentSportsController::class, 'store'])->name('borrowings.store');
            Route::get('/borrowings/{borrowing}', [StudentSportsController::class, 'show'])->name('borrowings.show');
        });
        
        // Student Organizations
        Route::prefix('organizations')->name('organizations.')->group(function () {
            Route::get('/', [StudentOrganizationController::class, 'index'])->name('index');
            Route::get('/{organization}', [StudentOrganizationController::class, 'show'])->name('show');
            Route::put('/{organization}', [StudentOrganizationController::class, 'update'])->name('update');
            Route::post('/{organization}/events', [StudentOrganizationController::class, 'storeEvent'])->name('events.store');
        });
    });
});
