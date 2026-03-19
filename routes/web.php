<?php

use App\Http\Controllers\Admin\AcademicCalendarController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\ComplaintController as AdminComplaintController;
use App\Http\Controllers\Admin\DisciplineController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\GuidanceController;
use App\Http\Controllers\Admin\CandidacyController as AdminCandidacyController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PublicationController as AdminPublicationController;
use App\Http\Controllers\Admin\RoleController;

use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SportsController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StudentRecordController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicPublicationController;
use App\Http\Controllers\Student\ComplaintController as StudentComplaintController;
use App\Http\Controllers\Student\DisciplineController as StudentDisciplineController;
use App\Http\Controllers\Student\NotificationController as StudentNotificationController;
use App\Http\Controllers\Student\PublicationController as StudentPublicationController;
use App\Http\Controllers\Student\SportsController as StudentSportsController;
use App\Http\Controllers\Student\GuidanceController as StudentGuidanceController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentCandidacyController;
use App\Http\Controllers\StudentOrganizationController;
use App\Http\Controllers\Admin\GoodMoralRequestController as AdminGoodMoralController;
use App\Http\Controllers\GoodMoralRequestController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\WelcomeController;
use App\Models\User;
use App\Services\ModuleAuthorizationService;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public Routes
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');
Route::get('/good-moral-request', [GoodMoralRequestController::class, 'create'])->name('good-moral.create');
Route::post('/good-moral-request', [GoodMoralRequestController::class, 'store'])->name('good-moral.store');

// Public Publication Routes
Route::prefix('publications')->name('publications.')->group(function () {
    Route::get('/', [PublicPublicationController::class, 'index'])->name('index');
    Route::get('/articles/{article:slug}', [PublicPublicationController::class, 'showArticle'])->name('articles.show');
    Route::get('/newspapers/{newspaper:slug}', [PublicPublicationController::class, 'showNewspaper'])->name('newspapers.show');
    Route::get('/galleries/{gallery:slug}', [PublicPublicationController::class, 'showGallery'])->name('galleries.show');
});

// Authentication Routes
require __DIR__ . '/auth.php';

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Onboarding Routes (no onboarding middleware — these ARE the onboarding pages)
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/change-password', [OnboardingController::class, 'showChangePassword'])->name('change-password');
        Route::post('/change-password', [OnboardingController::class, 'changePassword'])->name('change-password.update');
        Route::get('/complete-profile', [OnboardingController::class, 'showCompleteProfile'])->middleware('role:student')->name('complete-profile');
        Route::post('/complete-profile', [OnboardingController::class, 'completeProfile'])->middleware('role:student')->name('complete-profile.update');
    });

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
        'role:admin,super_admin,staff,sports_admin,organization_admin,discipline_admin,guidance_admin,publication_admin',
        'onboarding',
    ])->group(function () {
        // Dashboard - accessible to all admin roles
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->middleware('module:dashboard')
            ->name('dashboard');

        // Term summary report (all admin roles)
        Route::get('/reports/term-summary', [AdminDashboardController::class, 'termSummaryReport'])
            ->middleware('module:dashboard')
            ->name('reports.term-summary');
        Route::get('/reports/term-summary/pdf', [AdminDashboardController::class, 'termSummaryPdf'])
            ->middleware('module:dashboard')
            ->name('reports.term-summary.pdf');

        // Student Records (Admin/Super Admin only)
        Route::prefix('students')->name('students.')
            ->middleware(['role:admin,super_admin', 'module:students'])
            ->group(function () {
                Route::post('/import', [StudentRecordController::class, 'import'])->name('import');
                Route::get('/export/pdf', [StudentRecordController::class, 'exportPdf'])->name('export.pdf');
                Route::get('/sections/list', [StudentRecordController::class, 'getSections'])->name('sections.list');
                Route::get('/academic-calendars', [StudentRecordController::class, 'getAcademicCalendars'])->name('academic-calendars');
                Route::patch('/{student}/status', [StudentRecordController::class, 'updateStatus'])
                    ->name('updateStatus');
                Route::post('/bulk-status', [StudentRecordController::class, 'bulkUpdateStatus'])->name('bulk-status');

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
        Route::middleware(['role:admin,super_admin', 'module:staff,roles,courses,academic-calendars'])->group(function () {
            Route::get('staff/export/pdf', [StaffController::class, 'exportPdf'])->name('staff.export.pdf');
            Route::resource('staff', StaffController::class)
                ->except(['show', 'create', 'edit'])
                ->parameters(['staff' => 'employee']);
            Route::resource('roles', RoleController::class)->except(['show', 'create', 'edit']);
            Route::resource('courses', CourseController::class)->except(['show', 'create', 'edit']);

            Route::resource('academic-calendars', AcademicCalendarController::class)
                ->except(['show', 'create', 'edit'])
                ->parameters(['academic-calendars' => 'calendar']);
        });

        // Guidance Module Routes
        Route::prefix('guidance')->name('guidance.')
            ->middleware('module:guidance')
            ->group(function () {
                Route::get('/export/pdf', [GuidanceController::class, 'exportPdf'])->name('export.pdf');
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
                Route::put('/appointments/{appointment}/status', [GuidanceController::class, 'updateAppointmentStatus'])->name('appointments.updateStatus');
            });

        // Discipline Module Routes
        Route::prefix('discipline')->name('discipline.')
            ->middleware(['role:admin,super_admin,discipline_admin', 'module:discipline'])
            ->group(function () {
                // Complaints (student-submitted) — before {discipline} to avoid route conflict
                Route::get('/complaints/export/pdf', [AdminComplaintController::class, 'exportPdf'])->name('complaints.export.pdf');
                Route::get('/complaints', [AdminComplaintController::class, 'index'])->name('complaints.index');
                Route::get('/complaints/{complaint}', [AdminComplaintController::class, 'show'])->name('complaints.show');
                Route::put('/complaints/{complaint}', [AdminComplaintController::class, 'update'])->name('complaints.update');

                Route::get('/export/pdf', [DisciplineController::class, 'exportPdf'])->name('export.pdf');
                Route::post('/risk/compute-all', [DisciplineController::class, 'computeRiskAll'])->name('risk.compute-all');
                Route::post('/risk/compute/{student}', [DisciplineController::class, 'computeRiskOne'])->name('risk.compute-one');
                Route::get('/', [DisciplineController::class, 'index'])->name('index');
                Route::post('/', [DisciplineController::class, 'store'])->name('store');
                Route::post('/{discipline}/meetings', [DisciplineController::class, 'storeMeeting'])->name('meetings.store');
                Route::put('/{discipline}/meetings/{meeting}', [DisciplineController::class, 'updateMeeting'])->name('meetings.update');
                Route::get('/{discipline}', [DisciplineController::class, 'show'])->name('show');
                Route::put('/{discipline}', [DisciplineController::class, 'update'])->name('update');
                Route::put('/{discipline}/status', [DisciplineController::class, 'updateStatus'])->name('updateStatus');
                Route::post('/{discipline}/void', [DisciplineController::class, 'void'])->name('void');
                Route::delete('/{discipline}', [DisciplineController::class, 'destroy'])->name('destroy');
            });

        // Organization Module Routes
        Route::prefix('organizations')->name('organizations.')
            ->middleware(['role:admin,super_admin,organization_admin', 'module:organizations'])
            ->group(function () {
                // Events (must be before dynamic {organization} routes)
                Route::prefix('events')->name('events.')->group(function () {
                    Route::get('/export/pdf', [EventController::class, 'exportPdf'])->name('export.pdf');
                    Route::get('/', [EventController::class, 'index'])->name('index');
                    Route::post('/', [EventController::class, 'store'])->name('store');
                    Route::get('/{event}', [EventController::class, 'show'])->name('show');
                    Route::put('/{event}', [EventController::class, 'update'])->name('update');
                });

                // Organizations
                Route::get('/export/pdf', [OrganizationController::class, 'exportPdf'])->name('export.pdf');
                Route::get('/', [OrganizationController::class, 'index'])->name('index');
                Route::post('/', [OrganizationController::class, 'store'])->name('store');
                // Candidacy applications (global - not org-scoped)
                Route::get('/candidacies/export/pdf', [AdminCandidacyController::class, 'exportPdf'])->name('candidacies.export.pdf');
                Route::get('/candidacies', [AdminCandidacyController::class, 'index'])->name('candidacies.index');
                Route::get('/candidacies/{application}', [AdminCandidacyController::class, 'show'])->name('candidacies.show');
                Route::post('/candidacies/{application}/status', [AdminCandidacyController::class, 'updateStatus'])->name('candidacies.updateStatus');
                Route::post('/candidacies/toggle', [AdminCandidacyController::class, 'toggleCandidacy'])->name('candidacies.toggle');
                Route::get('/{organization}', [OrganizationController::class, 'show'])->name('show');
                Route::put('/{organization}', [OrganizationController::class, 'update'])->name('update');

                // Officer Management
                Route::post('/{organization}/officers', [OrganizationController::class, 'addOfficer'])->name('officers.add');
                Route::delete('/{organization}/officers/{officer}', [OrganizationController::class, 'removeOfficer'])->name('officers.remove');

                // Adviser Management
                Route::put('/{organization}/adviser', [OrganizationController::class, 'updateAdviser'])->name('adviser.update');

                // Meeting Management
                Route::post('/{organization}/meetings', [OrganizationController::class, 'storeMeeting'])->name('meetings.store');
                Route::put('/{organization}/meetings/{meeting}', [OrganizationController::class, 'updateMeetingStatus'])->name('meetings.updateStatus');
            });

        // Sports Module Routes
        Route::prefix('sports')->name('sports.')
            ->middleware(['role:admin,super_admin,sports_admin', 'module:sports'])
            ->group(function () {
                Route::get('/borrowings/export/pdf', [SportsController::class, 'exportPdf'])->name('borrowings.export.pdf');
                Route::get('/', [SportsController::class, 'index'])->name('index');
                Route::post('/borrowings', [SportsController::class, 'storeBorrowing'])->name('borrowings.store');
                Route::post('/borrowings/{borrowing}/approve', [SportsController::class, 'approveBorrowing'])->name('borrowings.approve');
                Route::post('/borrowings/{borrowing}/reject', [SportsController::class, 'rejectBorrowing'])->name('borrowings.reject');
                Route::put('/borrowings/{borrowing}', [SportsController::class, 'updateBorrowing'])->name('borrowings.update');
                Route::put('/borrowings/{borrowing}/status', [SportsController::class, 'updateBorrowingStatus'])->name('borrowings.updateStatus');
                Route::get('/borrowings/{borrowing}', [SportsController::class, 'showBorrowing'])->name('borrowings.show');

                // Athletes & Sports Management
                Route::get('/athletes', [SportsController::class, 'athletes'])->name('athletes');
                Route::post('/sports', [SportsController::class, 'storeSport'])->name('sports.store');
                Route::put('/sports/{sport}', [SportsController::class, 'updateSport'])->name('sports.update');
                Route::get('/sports/{sport}', [SportsController::class, 'showSport'])->name('sports.show');
                Route::post('/sports/{sport}/athletes', [SportsController::class, 'storeAthlete'])->name('sports.athletes.store');
                Route::delete('/sports/{sport}/athletes/{student}', [SportsController::class, 'removeAthlete'])->name('sports.athletes.destroy');
            });

        // Publications Module Routes
        Route::prefix('publications')->name('publications.')->middleware('module:publications')->group(function () {
            Route::get('/', [AdminPublicationController::class, 'index'])->name('index');

            // Articles
            Route::get('/articles/create', [AdminPublicationController::class, 'createArticle'])->name('articles.create');
            Route::post('/articles', [AdminPublicationController::class, 'storeArticle'])->name('articles.store');
            Route::get('/articles/{article:slug}', [AdminPublicationController::class, 'showArticle'])->name('articles.show');
            Route::put('/articles/{article:slug}', [AdminPublicationController::class, 'updateArticle'])->name('articles.update');
            Route::delete('/articles/{article:slug}', [AdminPublicationController::class, 'destroyArticle'])->name('articles.destroy');
            Route::post('/articles/{article:slug}/review', [AdminPublicationController::class, 'reviewArticle'])->name('articles.review');

            // Newspapers
            Route::get('/newspapers/create', [AdminPublicationController::class, 'createNewspaper'])->name('newspapers.create');
            Route::post('/newspapers', [AdminPublicationController::class, 'storeNewspaper'])->name('newspapers.store');
            Route::get('/newspapers/{newspaper:slug}', [AdminPublicationController::class, 'showNewspaper'])->name('newspapers.show');
            Route::put('/newspapers/{newspaper:slug}', [AdminPublicationController::class, 'updateNewspaper'])->name('newspapers.update');
            Route::delete('/newspapers/{newspaper:slug}', [AdminPublicationController::class, 'destroyNewspaper'])->name('newspapers.destroy');
            Route::post('/newspapers/{newspaper:slug}/review', [AdminPublicationController::class, 'reviewNewspaper'])->name('newspapers.review');

            // Galleries
            Route::get('/galleries/create', [AdminPublicationController::class, 'createGallery'])->name('galleries.create');
            Route::post('/galleries', [AdminPublicationController::class, 'storeGallery'])->name('galleries.store');
            Route::get('/galleries/{gallery:slug}', [AdminPublicationController::class, 'showGallery'])->name('galleries.show');
            Route::put('/galleries/{gallery:slug}', [AdminPublicationController::class, 'updateGallery'])->name('galleries.update');
            Route::delete('/galleries/{gallery:slug}', [AdminPublicationController::class, 'destroyGallery'])->name('galleries.destroy');
            Route::post('/galleries/{gallery:slug}/review', [AdminPublicationController::class, 'reviewGallery'])->name('galleries.review');
            Route::post('/galleries/{gallery:slug}/photos', [AdminPublicationController::class, 'uploadPhotos'])->name('galleries.photos.upload');
            Route::delete('/galleries/{gallery:slug}/photos/{photo}', [AdminPublicationController::class, 'deletePhoto'])->name('galleries.photos.destroy');
            Route::put('/galleries/{gallery:slug}/photos/reorder', [AdminPublicationController::class, 'reorderPhotos'])->name('galleries.photos.reorder');

            // Settings (admin/super_admin only)
            Route::post('/settings/toggle-publication-org', [AdminPublicationController::class, 'togglePublicationOrg'])
                ->middleware('role:admin,super_admin')
                ->name('settings.toggle-org');
        });

        // Good Moral Certificate Requests
        Route::prefix('good-moral')->name('good-moral.')->middleware('module:good_moral')->group(function () {
            Route::get('/', [AdminGoodMoralController::class, 'index'])->name('index');
            Route::put('/{goodMoral}', [AdminGoodMoralController::class, 'update'])->name('update');
        });

        // Settings — view & lookup-values open to any settings module role
        Route::middleware('module:settings')->group(function () {
            Route::get('/settings', [SettingsController::class, 'index'])->name('settings');

            // Lookup Values management (controller enforces per-module key restrictions)
            Route::put('/settings/lookup-values', [SettingsController::class, 'updateLookupValues'])->name('settings.lookup-values.update');
        });

        // Settings mutations — admin/super_admin only
        Route::middleware('role:admin,super_admin')->group(function () {
            // Discipline Workflow Step management
            Route::post('/settings/discipline-steps', [SettingsController::class, 'storeDisciplineStep'])->name('settings.discipline-steps.store');
            Route::put('/settings/discipline-steps/reorder', [SettingsController::class, 'reorderDisciplineSteps'])->name('settings.discipline-steps.reorder');
            Route::put('/settings/discipline-steps/{step}', [SettingsController::class, 'updateDisciplineStep'])->name('settings.discipline-steps.update');
            Route::delete('/settings/discipline-steps/{step}', [SettingsController::class, 'destroyDisciplineStep'])->name('settings.discipline-steps.destroy');

            // Discipline Violation Type management
            Route::post('/settings/violation-types', [SettingsController::class, 'storeViolationType'])->name('settings.violation-types.store');
            Route::put('/settings/violation-types/{type}', [SettingsController::class, 'updateViolationType'])->name('settings.violation-types.update');
            Route::delete('/settings/violation-types/{type}', [SettingsController::class, 'destroyViolationType'])->name('settings.violation-types.destroy');
        });
    });

    // Student Routes
    Route::prefix('student')->name('student.')->middleware(['role:student', 'onboarding'])->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [\App\Http\Controllers\Student\ProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [\App\Http\Controllers\Student\ProfileController::class, 'update'])->name('profile.update');
        Route::get('/requests', fn() => Inertia::render('Student/Requests/Index'))->name('requests.index');
        Route::get('/settings', fn() => Inertia::render('Student/Settings'))->name('settings');

        // Student Notifications (General)
        Route::get('/notifications', [StudentNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read', [StudentNotificationController::class, 'markAsRead'])->name('notifications.mark-read');
        Route::post('/notifications/read-all', [StudentNotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');

        // Student Discipline Module
        Route::prefix('discipline')->name('discipline.')->group(function () {
            Route::get('/', [StudentDisciplineController::class, 'index'])->name('index');
            Route::get('/notifications', [StudentDisciplineController::class, 'notifications'])->name('notifications.index');
            Route::post('/notifications/read', [StudentDisciplineController::class, 'markNotificationRead'])->name('notifications.mark-read');
            Route::get('/code-of-conduct', [StudentDisciplineController::class, 'codeOfConductIndex'])->name('code-of-conduct.index');
            Route::get('/code-of-conduct/{slug}', [StudentDisciplineController::class, 'codeOfConductShow'])->name('code-of-conduct.show');
            // Complaints (submit complaint, my complaints)
            Route::get('/complaints/create', [StudentComplaintController::class, 'create'])->name('complaints.create');
            Route::post('/complaints', [StudentComplaintController::class, 'store'])->name('complaints.store');
            Route::get('/complaints', [StudentComplaintController::class, 'index'])->name('complaints.index');
            Route::get('/complaints/{complaint}', [StudentComplaintController::class, 'show'])->name('complaints.show');
            Route::get('/{discipline}', [StudentDisciplineController::class, 'show'])->name('show');
        });

        // Student Guidance Module
        Route::prefix('guidance')->name('guidance.')->group(function () {
            Route::get('/', [StudentGuidanceController::class, 'index'])->name('index');
            Route::get('/appointments/taken-slots', [StudentGuidanceController::class, 'takenSlots'])->name('appointments.taken-slots');
            Route::post('/appointments', [StudentGuidanceController::class, 'store'])->name('appointments.store');
            Route::get('/appointments/{appointment}', [StudentGuidanceController::class, 'show'])->name('appointments.show');
        });

        // Student Sports Module
        Route::prefix('sports')->name('sports.')->group(function () {
            Route::get('/', [StudentSportsController::class, 'index'])->name('index');
            Route::post('/borrowings', [StudentSportsController::class, 'store'])->name('borrowings.store');
            Route::get('/borrowings/{borrowing}', [StudentSportsController::class, 'show'])->name('borrowings.show');
        });

        // Student Publications Module (officers + publication_admin accessible via publication middleware)
        Route::prefix('publications')->name('publications.')->middleware('publication')->group(function () {
            Route::get('/', [StudentPublicationController::class, 'index'])->name('index');

            // Articles
            Route::get('/articles/create', [StudentPublicationController::class, 'createArticle'])->name('articles.create');
            Route::post('/articles', [StudentPublicationController::class, 'storeArticle'])->name('articles.store');
            Route::get('/articles/{article:slug}', [StudentPublicationController::class, 'showArticle'])->name('articles.show');
            Route::put('/articles/{article:slug}', [StudentPublicationController::class, 'updateArticle'])->name('articles.update');
            Route::delete('/articles/{article:slug}', [StudentPublicationController::class, 'destroyArticle'])->name('articles.destroy');

            // Newspapers
            Route::get('/newspapers/create', [StudentPublicationController::class, 'createNewspaper'])->name('newspapers.create');
            Route::post('/newspapers', [StudentPublicationController::class, 'storeNewspaper'])->name('newspapers.store');
            Route::get('/newspapers/{newspaper:slug}', [StudentPublicationController::class, 'showNewspaper'])->name('newspapers.show');
            Route::put('/newspapers/{newspaper:slug}', [StudentPublicationController::class, 'updateNewspaper'])->name('newspapers.update');
            Route::delete('/newspapers/{newspaper:slug}', [StudentPublicationController::class, 'destroyNewspaper'])->name('newspapers.destroy');

            // Galleries
            Route::get('/galleries/create', [StudentPublicationController::class, 'createGallery'])->name('galleries.create');
            Route::post('/galleries', [StudentPublicationController::class, 'storeGallery'])->name('galleries.store');
            Route::get('/galleries/{gallery:slug}', [StudentPublicationController::class, 'showGallery'])->name('galleries.show');
            Route::put('/galleries/{gallery:slug}', [StudentPublicationController::class, 'updateGallery'])->name('galleries.update');
            Route::delete('/galleries/{gallery:slug}', [StudentPublicationController::class, 'destroyGallery'])->name('galleries.destroy');
            Route::post('/galleries/{gallery:slug}/photos', [StudentPublicationController::class, 'uploadPhotos'])->name('galleries.photos.upload');
            Route::delete('/galleries/{gallery:slug}/photos/{photo}', [StudentPublicationController::class, 'deletePhoto'])->name('galleries.photos.destroy');
        });

        // Student Organizations
        Route::prefix('organizations')->name('organizations.')->group(function () {
            Route::get('/', [StudentOrganizationController::class, 'index'])->name('index');
            Route::get('/candidacy/create', [StudentCandidacyController::class, 'create'])->name('candidacy.create');
            Route::post('/candidacy', [StudentCandidacyController::class, 'store'])->name('candidacy.store');
            Route::get('/candidacies', [StudentCandidacyController::class, 'index'])->name('candidacies.index');
            Route::get('/candidacy/{application}', [StudentCandidacyController::class, 'show'])->name('candidacy.show');
            Route::post('/candidacy/{application}/withdraw', [StudentCandidacyController::class, 'withdraw'])->name('candidacy.withdraw');
            Route::get('/{organization}', [StudentOrganizationController::class, 'show'])->name('show');
            Route::put('/{organization}', [StudentOrganizationController::class, 'update'])->name('update');
            Route::post('/{organization}/events', [StudentOrganizationController::class, 'storeEvent'])->name('events.store');
            Route::post('/{organization}/meetings', [StudentOrganizationController::class, 'storeMeeting'])->name('meetings.store');
        });
    });
});
