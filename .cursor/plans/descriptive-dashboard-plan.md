---
name: Descriptive Dashboard Plan
overview: Single descriptive admin dashboard for all roles with module summaries, exactly 5 upcoming events, and event date-conflict warning. Predictive deferred.
todos: []
isProject: false
---

# Descriptive Dashboard (Single View for All Roles)

## Scope

- **One dashboard for everyone:** Regardless of role, all users who can access the admin dashboard see the **same** descriptive dashboard. No role-based hiding of widgets.
- **Descriptive only:** This plan covers summaries, counts, and lists. **Predictive** will be discussed separately later.

---

## 1. System context

OSA-IMS has modules: Students, Discipline, Complaints, Guidance, Organizations, Events, Sports, Candidacies. The admin dashboard ([AdminDashboardController](app/Http/Controllers/AdminDashboardController.php)) currently shows stat cards and upcoming events. Events are created in: **Admin** [EventController](app/Http/Controllers/Admin/EventController.php) (store/update) and **Student** [StudentOrganizationController::storeEvent](app/Http/Controllers/StudentOrganizationController.php).

---

## 2. What to display (descriptive)

### 2.1 Module summary cards (all modules)

One summary card per module; counts scoped to **current semester** (active academic calendar) where it makes sense.

| Module | What to show |
|--------|--------------|
| **Students** | Total enrolled students this sem |
| **Discipline** | Total violations/cases this sem; active/pending cases |
| **Organizations** | Total active organizations |
| **Events** | Total events this sem or this month; upcoming count |
| **Complaints** | Total complaints this sem; pending count |
| **Guidance** | Guidance cases this sem; pending appointments |
| **Sports** | Pending borrowings; optional total this sem |
| **Candidacies** | Pending candidacy applications |

Dashboard: **grid of module summary cards** (2-4 per row). Each card links to the relevant module index.

### 2.2 Upcoming events: exactly 5

- **Rule:** "Upcoming events" widget shows **exactly 5** upcoming events (same query: event_date from today up to 30 days, status Upcoming/Planning, ordered by date/time). Keep "View all" link.

### 2.3 Event date conflict warning (new business rule)

When **any** user (admin or student) adds or edits an event and chooses a **date** on which **another organization** already has an event:

- **Show a warning** (do not block): *"Another organization already has an event on this date. Do you want to continue? Ask your adviser or org admin first."*
- User can **Continue anyway** or cancel. System does **not** prevent double-booking; it only warns.

**Implementation:**

- **Backend:** Before saving (create or update), check: "Does any **other** org (exclude current event's org when editing) have an event on this `event_date`?" If yes, return conflict info (e.g. `date_conflict: true`, `conflicting_orgs: [{ org_name }]`).
- **Confirm on submit:** On submit, if conflict exists, return response asking for confirmation (e.g. 422 with `requires_confirmation: true`). Frontend shows warning dialog; if user confirms, resubmit with `confirm_date_conflict=1`; backend then saves.
- Apply in **both** flows: Admin [EventController](app/Http/Controllers/Admin/EventController.php) (store/update) and Student [StudentOrganizationController::storeEvent](app/Http/Controllers/StudentOrganizationController.php). Admin uses [EventFormModal.vue](resources/js/Components/Admin/EventFormModal.vue); student event form needs the same warning UI.

---

## 3. Additional descriptive ideas (suggestions)

- **Quick totals row:** One row of KPI numbers (e.g. "This sem: X students, Y violations, Z events").
- **Recent activity:** "Recent discipline cases" or "Recent complaints" (last 5) with links to show pages.
- **Enrollment breakdown:** Enrolled students by **course** or **year_level** for the active term (chart or table).
- **Discipline breakdown:** Violations by **violation_type** or **severity** (pie or bar) for the current term.
- **Complaints breakdown:** Complaints by **category** or **status** for the current term.
- **Events by month:** Bar/line chart of events per month for the current term or last 6 months.
- **Top organizations by events:** List of orgs with most events this sem with counts.
- **Date/term filter:** Dropdown "View for: This term / Last 6 months" (default: current academic term).
- **Export:** "Download summary as PDF" or "Export counts as CSV."

---

## 4. UI and layout

- **Design:** Follow [design rules](.cursor/rules/design.mdc): 12-col grid, spacing, neutral + one accent, clean cards, rounded corners.
- **Layout:** One dashboard page ([Admin/Dashboard.vue](resources/js/Pages/Admin/Dashboard.vue)):
  - **Row 1:** Module summary cards (all modules), same for all roles.
  - **Row 2 (optional):** Global filter "Scope: This term / Last 6 months."
  - **Row 3:** "Upcoming events" card with **exactly 5** events + "View all."
  - **Row 4:** Quick access links (existing) and any optional widgets (recent activity, breakdown charts).
- **Charts (if added):** Add Chart.js + vue-chartjs and reusable components (BarChart.vue, PieChart.vue).

---

## 5. Implementation outline

1. **Backend – dashboard data**  
   In [AdminDashboardController](app/Http/Controllers/AdminDashboardController.php): use [AcademicCalendar::active()](app/Models/AcademicCalendar.php) to scope counts to current term. Compute: enrolled students, violations (total, pending), active orgs, events (this sem/month, upcoming), complaints (total, pending), guidance cases and pending appointments, pending borrowings, pending candidacies. Pass as `stats` or `moduleSummaries` to the dashboard. Ensure the single dashboard route is what all roles use.

2. **Backend – upcoming events**  
   Change existing query from `limit(10)` to `limit(5)`.

3. **Backend – event date conflict**  
   Add helper: "other orgs with an event on this date" (given `event_date`, optional `exclude_event_id`, `exclude_org_id`). In EventController store/update and StudentOrganizationController::storeEvent: before saving, check conflict; if conflict and request does not have `confirm_date_conflict`, return validation/JSON asking for confirmation; if `confirm_date_conflict=1`, save. Optionally expose GET endpoint for "check date" so frontend can warn when user picks a date.

4. **Frontend – dashboard**  
   Update [Admin/Dashboard.vue](resources/js/Pages/Admin/Dashboard.vue): display full set of module summary cards (same for all roles), upcoming events list with exactly 5 items, optional filter and charts.

5. **Frontend – event conflict warning**  
   In [EventFormModal.vue](resources/js/Components/Admin/EventFormModal.vue): on submit, if backend returns "date conflict, confirm?" show dialog: *"Another organization already has an event on this date. Do you want to continue? Ask your adviser or org admin first."* [Cancel] [Continue anyway]. On Continue, resubmit with `confirm_date_conflict=1`. Same in student event form.

---

## 6. Predictive

Predictive analytics (risk scores, forecasts) are **out of scope** for this plan and will be discussed separately.

---

## 7. Summary

| Item | Decision |
|------|----------|
| **Dashboard** | Single descriptive dashboard; same view for all roles. |
| **Content** | Module summaries (students, violations, orgs, events, complaints, guidance, sports, candidacies), exactly 5 upcoming events, optional breakdowns and filters. |
| **Event conflict** | Warning when another org has an event on the chosen date; user can continue after "Ask your adviser or org admin first." |
| **Predictive** | Deferred; separate discussion. |
