# WSIT School Management System

A shared-hosting friendly school management system built with object-oriented PHP, MySQL, Tailwind CSS, and a lightweight flash-toast interface.

## Requirements

- PHP 8.1 or newer with PDO MySQL
- MySQL 5.7+ or MariaDB 10.4+
- Apache with `mod_rewrite` (or direct access to `public/index.php`)
- No Node.js, Composer, or build step

## Setup

1. Upload the project to your hosting account. Set the site document root to `public/` when possible.
2. Create a MySQL database and import `database/schema.sql`.
3. Copy `.env.example` to `.env` and enter your database/app credentials. (Legacy `config/database.php` and `config/app.php` files are still supported.)
4. Visit `/setup` once to create the first administrator. Remove or disable setup access after initial installation.

For a local demonstration, run `php scripts/seed-demo.php` from the project folder after configuring MySQL. It creates a demo administrator, a current academic year, five classes, and ten students. The script prints a randomly generated temporary password once; save it before closing the terminal. Do not run the demo seeder on a production database.

Tailwind is loaded from its browser CDN for zero-build deployment. For production, keep the generated class names in the markup (avoid dynamic class concatenation).

For the PHP development server, run `php -S 127.0.0.1:8000 -t public public/router.php`. The router file is required because PHP's built-in server does not read Apache `.htaccess` rules.

With WAMP, start Apache and MySQL from the WAMP tray menu and open `http://localhost/wsit-school-management-system/public/`. The app detects this subdirectory install path automatically. If your project folder has a different name, set `APP_BASE_URL` in `.env` (or `base_url` in `config/app.php`) to `/your-folder/public`.

## Implemented modules

- **Dashboard** — current academic year, active student/class/section counts, and recent student records.
- **Student register** — search by English/Bangla name, student ID, or roll; filter by class and status; admit, edit, and archive student records. Registration captures date of birth, birth registration number, admission date, previous school, address, student photo and a linked guardian. New admissions include a final review step. Student IDs use `STU-YEAR-NNN` format.
- **Enrollment history and promotion** — promote one active class cohort into a new academic year, assign destination rolls automatically, and retain the previous year/class/section/roll on each student detail page.
- **Student data export** — download filtered student records as spreadsheet-safe UTF-8 CSV.
- **Guardian links** — manage multiple linked guardians per student, with a primary contact, from each student's details.
- **Audit log** — administrators with settings access can review recent sign-ins and record changes at `/audit-logs`.
- **Uploads** — student photos, study materials, and exam suggestion attachments are stored under private `storage/uploads/` and served through authenticated routes. Ensure PHP can write to `storage/` on the host.
- **Automatic fees and payroll** — fee invoices can be generated from class fee structures; salary payments calculate gross, deductions and net from the teacher's effective salary structure.
- **Exam-linked report cards** — report records use an `exam_id` foreign key while preserving existing exam/year labels for compatibility.
- **Academic setup** — create academic years, switch the current year, define Classes 1–10 with shifts, and add named sections and optional room numbers.
- **Authentication foundation** — first-admin setup, sign-in/out, PDO-backed users, CSRF tokens, session regeneration, secure cookie flags, database-backed sign-in throttling, and the existing flash-toast system.
- **Security and audit trail** — sign-in attempts are limited per account and IP; protected uploads check the user's module permission or linked student/guardian/teacher record; record changes and sign-ins are logged for administrators.
- **AJAX navigation layer** — same-origin links and GET/POST forms are fetched in the background and update the page without a full reload. Browser back/forward works, server redirects and flash toasts are retained, and normal form behavior remains available if JavaScript is disabled. This is wired globally in the shared layout, so new modules inherit it automatically.
- **Grouped navigation rail** — the sidebar mirrors the structure of the full system build: eleven top level sections (Overview, Student Information, Academic, Exams & Results, Attendance, Finance, Accounts, Teacher Assignments, Facilities, Communication, Settings), each collapsible. The group containing the current page opens automatically, and the collapsed/expanded state is remembered per browser. The whole tree lives in `app/Core/Nav.php`, so adding a module means editing that one file.

### Record modules

Most modules are flat records — a table, a few fields, a list to browse — so they share one reusable engine rather than a controller and a view each:

- `app/Core/Crud.php` — the list/create/edit/delete controller: search, pagination, per-field validation, and persistence.
- `app/Models/*.php` — one file per module describing its table, columns, fields and permissions.
- `resources/views/crud/` — the shared list and form templates.

Adding a module means writing a model definition, registering six routes in `app/Core/Application.php`, and flipping the navigation entry from planned to working.

### The module set

Every entry in the navigation now leads to a working screen.

| Section | Modules |
|---------|---------|
| Overview | Dashboard, public website |
| Student Information | Student details, admission, CSV import, student houses, guardians |
| Academic | Academic setup, teachers, designations, subjects, study materials, lesson plans, class routines, weekly timetable |
| Exams & Results | Exams, schedules, report cards, tabulation sheets, merit lists, section results, fail lists, admit cards, exam attendance, exam suggestions, ID cards, certificates, online examinations, mark entry, seating chart |
| Attendance | Student attendance, batch attendance, attendance register, teacher attendance, staff attendance, leave requests |
| Finance | Fee heads, fee structures, fee collections, salary structures, salary payments |
| Accounts | Account dashboard, transactions, fee discounts, fee carry forwards, bank reconciliations |
| Teacher Assignments | Allocation matrix, class teachers, teacher subjects, class routines |
| Facilities | Library books, book issues, transport routes and stops, student transport, hostel rooms and allocations, inventory |
| Communication | Notice board, holidays, calendar, complaints |
| Settings | School settings, permissions and roles, user accounts, SMS integration, payment gateways, branches |

**Branch limitation:** the Branches screen is currently a campus directory only. Student, attendance, finance, and staff records are not partitioned by branch, so do not use it as multi-campus access isolation.

### The portals

A teacher, a student or a parent gets their own portal instead of the administration screens. An account carries a link type and a link id, so it points at the teacher, student or guardian record it belongs to. The link is managed from the **User accounts** screen, and it is what gives a person their portal.

| Portal | What it shows |
|--------|---------------|
| **Student** | Attendance and its percentage, report cards, fees, the online examinations they may sit, the day's classes and the notice board |
| **Teacher** | The classes and subjects they take, their students, the day's timetable, the leave requests waiting for a decision |
| **Parent** | Every child on the account, with their attendance, results and fees |

An online examination is marked as it is handed in: the multiple choice answers are scored against the correct answer, the written answers are left for a teacher.

The administration navigation is filtered by permission, so a teacher never sees the fee screens in the menu. The server refuses them either way.

### The result engine

`app/Models/ResultSet.php` is the shared engine behind the result screens. It loads the marks once and works out the total, the grade point and the class position using the grading scale of the National Education Policy — 80 and above is A+ (5.0), down to 33 for a D, and anything below 33 is a fail. A student who fails one subject has a grade point of zero and an overall grade of F, and is left out of the merit list but shown on the fail list with the subjects they failed.

Marks can be entered one student at a time, or a whole class at once on the **mark entry** screen. The printable mark sheet totals itself.

### Permissions are enforced

The full build only hides menu entries from people who lack a permission. Here the check happens on the server: a module asks `Guard::requirePermission()` for its slug before it serves the page, so a teacher cannot open the fee screens by typing the address. The catalogue and the role grants are managed from the Permissions screen.

## Setup scripts

| Script | Purpose |
|--------|---------|
| `php scripts/migrate.php` | Applies `database/schema.sql`, adds missing columns and exam-card indexes/foreign keys, creates the main campus when needed, and assigns existing school records to it. Run this after importing the schema on both existing and fresh installations. It also links legacy report cards to matching exams where possible and preserves reports with no match. |
| `php scripts/seed-permissions.php` | Creates the permission catalogue and the default role grants. |
| `php scripts/seed-modules.php` | Fills the people, academic and communication modules with sample school data: subjects, teachers, guardians, notices, holidays and so on. |
| `php scripts/seed-records.php` | Fills the finance, attendance, facilities, exam and result modules: fees, salaries, attendance, books, transport, hostel, timetables, exams and report cards. |
| `php scripts/seed-accounts.php` | Creates the accounts that make the portals reachable: an administrator, a teacher, a student and a parent, each linked to its person record. |
| `php scripts/seed-demo.php` | Creates the demo administrator, an academic year, five classes and ten students. |
| `php scripts/backup.php` | Writes a streamed SQL backup to the private `storage/backups/` folder; restore it with a MySQL client or the hosting control panel. |

The project uses server-rendered PHP templates and native browser JavaScript only. It works without a Node build step or Composer packages.

For shared hosting that cannot set `public/` as the document root, the root `.htaccess` routes web requests to `public/` and denies direct requests to source, configuration, database, script, and storage folders. Apache with rewrite support is required for this fallback. Keep `storage/backups/` private and download backups through the host file manager or SSH.
