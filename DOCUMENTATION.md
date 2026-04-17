# OSA-IMS Documentation

## Office of Student Affairs - Information Management System

A comprehensive web-based information management system for the Office of Student Affairs (OSA), designed to centralize and streamline student records, discipline, guidance, organizations, sports, publications, and administrative functions.

---

## Table of Contents

1. [Technology Stack](#technology-stack)
2. [Installation & Setup](#installation--setup)
3. [Environment Configuration](#environment-configuration)
4. [System Modules](#system-modules)
5. [User Roles & Access Control](#user-roles--access-control)
6. [Database Overview](#database-overview)
7. [System Settings & Lookup Values](#system-settings--lookup-values)
8. [Predictive Risk Scoring](#predictive-risk-scoring)
9. [For Developers](#for-developers)
10. [Deployment](#deployment)
11. [Maintenance Guide](#maintenance-guide)
12. [Troubleshooting](#troubleshooting)

---

## Technology Stack

| Layer      | Technology                        |
|------------|-----------------------------------|
| Backend    | Laravel 12 (PHP 8.2+)            |
| Frontend   | Vue 3 (Composition API) + Inertia.js |
| Styling    | Tailwind CSS                      |
| UI Components | PrimeVue 4                     |
| Database   | MySQL                             |
| Build Tool | Vite                              |
| PDF Export | DomPDF                            |
| Excel Import/Export | Maatwebsite Excel        |
| Email      | Resend                            |
| Charts     | Chart.js                          |
| Rich Text  | Quill Editor                      |
| Auth       | Laravel Breeze + Sanctum          |

---

## Installation & Setup

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- MySQL 8.0+

### Step-by-Step Setup

```bash
# 1. Clone or extract the project
cd osa-ims

# 2. Install PHP dependencies
composer install

# 3. Install Node.js dependencies
npm install

# 4. Create environment file
cp .env.example .env

# 5. Generate application key
php artisan key:generate

# 6. Configure your .env file (see Environment Configuration below)

# 7. Run database migrations
php artisan migrate

# 8. Seed default data (roles, settings, etc.)
php artisan db:seed

# 9. Create storage symlink (for file uploads/images)
php artisan storage:link

# 10. Build frontend assets
npm run build

# 11. Start the development server
php artisan serve
```

Or use the shortcut:

```bash
composer setup    # Runs steps 2-10 automatically
composer dev      # Starts server, queue worker, and Vite concurrently
```

### Quick Start (Development)

```bash
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server (hot reload)
npm run dev

# Terminal 3: Queue worker (for emails/notifications)
php artisan queue:work
```

---

## Environment Configuration

Copy `.env.example` to `.env` and configure these key values:

### Database

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=osa-ims
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Email (Resend)

For sending email notifications (discipline alerts, appointment confirmations, etc.):

```env
MAIL_MAILER=resend
RESEND_API_KEY=re_xxxxxxxxxxxx
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="OSA-IMS"
```

Set `MAIL_MAILER=log` during development to log emails instead of sending them.

### Application

```env
APP_NAME="OSA-IMS"
APP_URL=http://localhost:8000
APP_ENV=local
APP_DEBUG=true
```

### Session & Queue

```env
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

---

## System Modules

### 1. Student Records Management

**Admin Features:**
- View, create, edit, and deactivate student records
- Bulk import students from Excel spreadsheets
- Export student lists to PDF
- Bulk create student user accounts
- View detailed student profiles (personal info, family, education, emergency contacts)

**Student Features:**
- Complete profile onboarding (required on first login)
- Update personal information, family info, educational background, and emergency contacts

### 2. Discipline & Conduct

**Admin Features:**
- Document violations with severity levels (Minor, Major)
- Configurable workflow steps (e.g., Violation Reported > Under Investigation > Resolved)
- Schedule and document discipline meetings/hearings
- Void invalid cases
- Manage violation types and their default sanctions in Settings
- Compute risk prediction scores for students

**Student Features:**
- View personal violation history and case details
- View code of conduct
- File complaints against other students or situations
- Track complaint status

### 3. Guidance & Counseling

**Admin Features:**
- Create and manage guidance cases (counseling, consultation, referral)
- Add case actions and notes
- Approve or reject student appointment requests
- View appointment calendar

**Student Features:**
- Book guidance appointments with available time slots
- View appointment history and status

### 4. Student Organizations

**Admin Features:**
- Create and manage student organizations
- Assign organization types (Academic, Cultural, Governance, Special Interest)
- Review candidacy applications (Approve, Reject, Set Under Review)
- Toggle candidacy submissions open/closed globally
- Print candidacy application forms

**Student Features:**
- Browse active organizations
- Submit candidacy applications (Certificate of Candidacy) for organization positions
- Manage organization details, officers, meetings, and events (if an officer)
- Print submitted candidacy forms

### 5. Sports & Athletics

**Admin Features:**
- Create and manage sports/teams
- Assign student athletes to teams
- Approve or reject equipment borrowing requests
- Track equipment return status

**Student Features:**
- View sports information
- Submit equipment borrowing requests

### 6. Publications

**Admin Features:**
- Review and moderate articles, newspapers, and galleries
- Manage publication content across organizations

**Student Features (Organization Officers):**
- Create and manage articles, newspaper issues, and photo galleries
- Submit content for review

**Public Access:**
- Browse published articles, newspapers, and photo galleries (no login required)

### 7. Good Moral Requests

**Admin Features:**
- Review and process good moral certificate requests
- Approve or deny based on student records

**Public Access:**
- Submit good moral certificate requests via a public form (no login required)

### 8. Reports & Analytics

- Admin dashboard with key metrics and charts
- Term summary reports (exportable to PDF)
- Risk level distribution charts
- Discipline and guidance statistics

---

## User Roles & Access Control

### Default Roles

| Role | Description |
|------|-------------|
| `super_admin` | Full system access, can manage all modules and settings |
| `admin` | Administrative access to assigned modules |
| `staff` | Limited administrative access based on module assignments |
| `student` | Student portal access |

### Module-Specific Roles

Additional roles can be created in **Admin > Roles** to restrict access to specific modules:
- `discipline_admin` - Discipline module access
- `guidance_admin` - Guidance module access
- `organization_admin` - Organization module access
- `sports_admin` - Sports module access
- `publication_admin` - Publication module access
- `student_records_admin` - Student records access

### How Access Control Works

1. **Role Check** - Users must have an appropriate role (admin, staff, etc.)
2. **Module Check** - The role must have access to the specific module being accessed
3. **Onboarding Check** - Students must complete profile onboarding before accessing the system

---

## Database Overview

The system uses **50+ database tables** organized into the following groups:

### Core Tables

| Table | Purpose |
|-------|---------|
| `users` | User authentication accounts |
| `roles` / `user_roles` | Role-based access control |
| `students` | Student personal records |
| `employees` | Staff/faculty records |
| `courses` / `sections` | Academic structure |
| `academic_calendar` | Academic terms/semesters |
| `enrolled_students` | Student enrollments |
| `system_settings` | Global configuration |

### Module Tables

| Module | Key Tables |
|--------|------------|
| Discipline | `discipline`, `discipline_history`, `discipline_meetings`, `discipline_violation_types`, `discipline_workflow_steps`, `complaints`, `complaint_history` |
| Guidance | `guidance_cases`, `guidance_case_actions`, `guidance_appointments` |
| Organizations | `student_org`, `org_positions`, `org_members`, `org_officers`, `org_advisers`, `org_meetings`, `candidacy_applications` |
| Sports | `sports`, `sport_athletes`, `sports_borrowing` |
| Publications | `publication_articles`, `publication_newspapers`, `publication_galleries`, `publication_gallery_photos` |
| Risk | `risk_predictions`, `violation_summary` |
| Student Info | `student_profiles`, `student_family_info`, `student_educational_backgrounds`, `student_emergency_contacts` |

### Running Migrations

```bash
# Run pending migrations
php artisan migrate

# Rollback the last batch
php artisan migrate:rollback

# Check migration status
php artisan migrate:status
```

---

## System Settings & Lookup Values

Configurable values are managed in **Admin > Settings**. These control dropdown options throughout the system.

### Configurable Lists

| Setting Key | Default Values | Used In |
|-------------|---------------|---------|
| `violation_severities` | Minor, Major | Discipline forms |
| `complaint_categories` | Academic Integrity, Campus Conduct, Prohibited Activities, Other | Complaint forms |
| `organization_types` | Academic, Cultural, Governance, Special Interest | Organization management |
| `default_org_positions` | President, Vice President, Secretary, Treasurer, Auditor, PIO, Business Manager, Sergeant-at-Arms | Candidacy application form |
| `guidance_case_types` | Counseling, Consultation, Referral | Guidance cases |
| `guidance_appointment_types` | Counseling, Consultation, Referral, Other | Guidance appointments |
| `event_statuses` | Planning, Upcoming, Completed | Event management |
| `sports_equipment` | Basketballs, Volleyballs, Badminton Sets, etc. | Equipment borrowing |

### Discipline Workflow Steps

The discipline module uses configurable workflow steps. Default steps can be customized in Settings. Each step has:
- **Label** - Display name (e.g., "Violation Reported", "Under Investigation")
- **Value** - Internal identifier
- **Sort Order** - Step sequence

### Violation Types

Violation types are grouped by severity and can be customized in Settings. Each type includes:
- **Name** - Violation name (e.g., "Tardiness", "Cheating")
- **Severity** - Minor or Major
- **Default Sanction** - Suggested punishment
- **Description** - Optional details

---

## Predictive Risk Scoring

The system includes a predictive risk scoring algorithm that flags students who may be at risk of committing violations.

### Formula

```
risk_score = violation_sub_score * 0.70 + guidance_sub_score * 0.30
```

**Violation Sub-Score** (capped at 100):
- Minor violations: 10 points each
- Major violations: 40 points each

**Guidance Sub-Score** (capped at 100):
- Referrals: 15 points each
- Other case types: 5 points each

### Risk Levels

| Level | Score Range |
|-------|------------|
| Low | 0 - 33 |
| Moderate | 34 - 66 |
| High | 67 - 100 |

### Computing Risk Scores

- **Bulk Compute:** Admin > Discipline > "Compute All" button
- **Individual:** Computed automatically when viewing a student's discipline record
- Results are stored in the `risk_predictions` table and displayed on the admin dashboard

For detailed documentation, see `docs/predictive_risk_scoring.md`.

---

## For Developers

### Project Structure

```
osa-ims/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/          # Admin controllers
│   │   │   ├── Student/        # Student controllers
│   │   │   └── ...             # Shared controllers
│   │   ├── Middleware/          # Auth, role, module access
│   │   └── Requests/           # Form validation classes
│   ├── Models/                  # Eloquent models (43 total)
│   ├── Services/                # Business logic services
│   └── Observers/               # Model event listeners
├── database/
│   ├── migrations/              # Schema definitions (85+ files)
│   └── seeders/                 # Default data seeders
├── resources/
│   ├── js/
│   │   ├── Components/          # Reusable Vue components
│   │   ├── Layouts/             # Page layout templates
│   │   ├── Pages/               # Page components (Admin/, Student/, Auth/)
│   │   └── composables/         # Vue composables (shared logic)
│   ├── css/                     # Tailwind CSS + print styles
│   └── views/                   # Blade templates (PDF exports, emails)
├── routes/
│   └── web.php                  # All route definitions
├── config/                      # Laravel configuration
├── docs/                        # Additional documentation
└── public/                      # Public assets
```

### Key Services

| Service | Purpose |
|---------|---------|
| `RiskScoringService` | Computes student risk prediction scores |
| `DisciplineService` | Discipline workflow and notification handling |
| `ComplaintService` | Complaint processing logic |
| `StudentImportService` | Bulk student import from Excel |
| `StudentAccountService` | Student account creation/deletion |
| `ModuleAuthorizationService` | Module access control logic |
| `PublicationAuthorizationService` | Publication access control |

### Adding a New Module

1. **Create the migration** - `php artisan make:migration create_<table>_table`
2. **Create the model** - `php artisan make:model <ModelName>`
3. **Create the controller** - Add to `app/Http/Controllers/Admin/` or `Student/`
4. **Add routes** - Register in `routes/web.php` under the appropriate group
5. **Create Vue pages** - Add to `resources/js/Pages/Admin/` or `Student/`
6. **Add to sidebar** - Update the layout component (`AdminLayout.vue` or `StudentLayout.vue`)
7. **Set up access control** - Add module key to `ModuleAuthorizationService` if needed

### Frontend Patterns

- **Inertia.js** - Server-side routing with Vue components (no API needed)
- **Composables** - Shared logic in `resources/js/composables/` (e.g., `useNotification`, `useStatusPresets`)
- **Form handling** - Use Inertia's `useForm()` for forms with validation
- **Modals** - Use the `Modal` component for confirmations and forms
- **Dark mode** - All components support dark mode via `dark:` Tailwind classes

### Running Tests

```bash
# Run all tests
php artisan test

# Or using Pest directly
./vendor/bin/pest
```

---

## Deployment

### Production Build

```bash
# Install production dependencies
composer install --optimize-autoloader --no-dev
npm ci && npm run build

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force
```

### Docker / Railway Deployment

The project includes a `start.sh` script and `Dockerfile` for containerized deployment. Key environment variables for deployment:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
RUN_MIGRATIONS=true     # Auto-run migrations on deploy
RUN_SEED=false           # Auto-run seeders on deploy
```

The `start.sh` script handles:
- Storage directory creation
- Configuration caching
- Migration execution (if `RUN_MIGRATIONS=true`)
- Queue worker startup
- Apache server startup

### File Storage

Uploaded files (profile images, publication photos, etc.) are stored in `storage/app/public/`. Ensure the storage symlink exists:

```bash
php artisan storage:link
```

For production, consider using cloud storage (S3, etc.) by updating `FILESYSTEM_DISK` in `.env`.

---

## Maintenance Guide

### Regular Tasks

1. **Database Backups** - Schedule regular MySQL backups
   ```bash
   mysqldump -u root -p osa-ims > backup_$(date +%Y%m%d).sql
   ```

2. **Clear Caches** (after updates)
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   php artisan route:clear
   ```

3. **Queue Monitoring** - Ensure the queue worker is running for email delivery
   ```bash
   php artisan queue:work
   ```

4. **Log Monitoring** - Check `storage/logs/laravel.log` for errors

5. **Storage Cleanup** - Periodically check `storage/app/public/` for disk usage

### Updating the System

```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install
npm install

# 3. Run new migrations
php artisan migrate

# 4. Build frontend
npm run build

# 5. Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Managing Lookup Values

Admins can customize dropdown values in **Admin > Settings** without touching code:
- Add/remove violation types and their default sanctions
- Customize discipline workflow steps
- Edit organization types, positions, equipment lists, etc.

### Managing User Accounts

- **Admin accounts** - Created manually in the database or via seeders
- **Student accounts** - Created via bulk import or individually in Admin > Students
- **Password resets** - Students must change password on first login
- **Deactivation** - Set student status to "inactive" to revoke access

---

## Troubleshooting

### Common Issues

**"500 Server Error" on first load**
- Ensure `.env` file exists with valid database credentials
- Run `php artisan key:generate`
- Check `storage/logs/laravel.log` for details

**"SQLSTATE connection refused"**
- Verify MySQL is running
- Check `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` in `.env`

**Styles not loading / blank page**
- Run `npm run build` (production) or `npm run dev` (development)
- Check that Vite is running if using dev mode

**Emails not sending**
- Ensure queue worker is running: `php artisan queue:work`
- Check `MAIL_MAILER` and `RESEND_API_KEY` in `.env`
- Use `MAIL_MAILER=log` to debug (emails logged to `storage/logs/`)

**File uploads not showing**
- Run `php artisan storage:link`
- Check `storage/app/public/` permissions

**"Onboarding required" loop**
- The student must complete profile setup (change password + fill profile)
- Check `must_change_password` and `profile_completed` fields in `users` table

**Position dropdown empty in candidacy form**
- Positions come from the `default_org_positions` lookup value in System Settings
- Check Admin > Settings to verify positions are configured

### Log Files

- **Application logs:** `storage/logs/laravel.log`
- **Queue/job failures:** `failed_jobs` table in database
- **Email logs:** `storage/logs/` (when `MAIL_MAILER=log`)

---

## Default Credentials

After running seeders (`php artisan db:seed`), the following accounts are created:

| Role | Email | Note |
|------|-------|------|
| Super Admin | Check `database/seeders/UserSeeder.php` | Has full system access |
| Student | Created via bulk import or manual creation | Must complete onboarding on first login |

> **Important:** Change all default passwords immediately after deployment.

---

## Support & Contact

For technical issues or questions about the system, refer to:
- This documentation
- `docs/predictive_risk_scoring.md` for the risk algorithm
- `routes/web.php` for all available endpoints
- Laravel documentation: https://laravel.com/docs
- Vue 3 documentation: https://vuejs.org/guide
- Inertia.js documentation: https://inertiajs.com
