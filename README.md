# PMMS — Ministry of Works & Housing Project Monitoring & Management System

A Laravel 12 application covering the full PMMS specification: project
registration and search, contractor and contract management, progress updates
with an approval workflow, milestones, site inspections, geo-tagged photo and
document uploads, financial tracking (payments, variations, retention),
GIS mapping (Leaflet + OpenStreetMap), executive dashboard with charts,
analytics (SPI/CPI, contractor rankings, delay analysis), CSV report exports,
in-app notifications with a daily scheduler, a full audit trail, user
management, and an optional public transparency portal — all behind
role-based access control for 13 roles and 4 directorates.

## 1. Setup

This repo contains application code only (no vendor/ or Laravel core, since
Composer couldn't run in the build environment). To stand it up:

```bash
composer create-project laravel/laravel pmms
cd pmms
composer require laravel/sanctum
php artisan install:api

# Copy this scaffold's folders over the defaults:
#   app/  database/  routes/  resources/views/
```

Register the middleware aliases in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'permission' => \App\Http\Middleware\EnsureHasPermission::class,
        'log.audit'  => \App\Http\Middleware\LogAudit::class,
    ]);
})
```

Configure `.env` (MySQL/MariaDB, `FILESYSTEM_DISK=public`; switch to `s3` in
production for photos/documents), then:

```bash
php artisan migrate
php artisan db:seed              # roles + permissions + demo data
php artisan storage:link
php artisan serve
```

Sign in at `/login` with the accounts from the Permanent Secretary's role document
(every account must set its own password at first sign-in):

| Email | Position | Default password |
|---|---|---|
| admin@works.gov | Super Admin | Superadmin001 |
| com@works.gov | Honorable Commissioner | Administrator001 |
| ps@works.gov | Permanent Secretary | Permsecretary001 |
| dce@works.gov | Director Civil Engineering | Dircivil001 |
| dpb@works.gov | Director Public Building | Dirpublic001 |
| dee@works.gov | Director Electrical Engineering | Direlectrical001 |
| dme@works.gov | Director Mechanical Engineering | Dirmechanical001 |
| dpr@works.gov | Director Planning, Research & Statistics | Dirplanning001 |
| dfa@works.gov | Director Finance & Accounts | Dirfinance001 |
| prece@works.gov | Project Resident Engineer Civil | Residentcivil2 |
| prepb@works.gov | Project Resident Engineer Public Building | Residentpublic2 |
| preee@works.gov | Project Resident Engineer Electrical | Residentelectrical2 |
| preme@works.gov | Project Resident Engineer Mechanical | Residentmechanical2 |

**Change these credentials before any real deployment.** For production, seed
only `RoleAndPermissionSeeder` (skip `DemoDataSeeder`).

The notification scheduler needs a cron entry in production:
`* * * * * php artisan schedule:run` — it runs `pmms:check-project-health`
daily at 06:00 to flag overdue/slipping projects and inspection-due notices.

## 2. Feature map (spec module → where it lives)

| Module | Implementation |
|---|---|
| 1. User management | `UserController`, `users/index` view, login history, rate-limited login |
| 2. Project management | `ProjectController`, registration form + filters in `projects/index` |
| 3. Contractor management | `ContractorController`, `contractors/index` |
| 4. Contract management | `ContractController` — contracts, variation orders, extensions of time |
| 5. Progress monitoring | `ProjectUpdate` submissions → Director approval → project progress syncs |
| 6. Milestone tracking | `MilestoneController`, checklist UI on project page |
| 7. Site inspection | `InspectionController`, inline form on project page |
| 8. Photo management | `ProjectImageController` — GPS auto-capture from device, before/during/after stages |
| 9. Document management | `DocumentController` — typed uploads (BOQ, drawings, certificates...) with download |
| 10. Financial tracking | Payments with pending→approved workflow, outstanding balance, retention |
| 11. GIS mapping | `MapController` — Leaflet map at `/map`, GeoJSON feed with status/LGA/contractor filters, status color legend |
| 12. Dashboard | `DashboardController` — KPI cards + Chart.js doughnut/bar charts + top delayed |
| 13. Notifications | `NotificationService` + `pmms:check-project-health` scheduled command + `/notifications` inbox (email/SMS hooks marked in code) |
| 14. Reports | `ReportController` — project summary & financial CSV exports (PDF/Excel: add dompdf / maatwebsite-excel) |
| 15. Audit trail | `LogAudit` middleware + `/audit-log` viewer |
| 16. Analytics | `AnalyticsController` — SPI, CPI, contractor rankings, delay-by-LGA |
| 17. Search | Filterable project index (code, name, status, LGA, contractor, year) |
| 18. Mobile friendly | Responsive Bootstrap layout; photo form uses `capture="environment"` + geolocation API |
| Risk & issue register | `RiskIssueController` + `risk_register` / `project_issues` tables |
| Public portal | `/public/projects` — no login, completed/ongoing projects with map & photos |
| REST API | `routes/api.php` behind Sanctum for the mobile field app |

## 3. The 13 roles and the 4 directorates

Seeded by `RoleAndPermissionSeeder` (safe to re-run); each role maps to a permission set
enforced by route middleware (`permission:x.y`) and `$user->hasPermission()` checks.

Every project belongs to one directorate (`projects.directorate`: civil, public_building,
electrical, mechanical). Directors and Resident Engineers are tied to their directorate by
`Role::DIRECTORATE_BY_ROLE`; the `directorate` middleware (`EnsureDirectorateAccess`) blocks
any project, or anything belonging to a project, outside it, and `Project::visibleTo($user)`
limits every list, chart, map, analytic and report. Admin, Commissioner, Permanent
Secretary, Director Planning and Director Finance see all directorates.

To add or change a role: edit `RoleAndPermissionSeeder` (and `Role` constants) and re-run
`php artisan db:seed --class=RoleAndPermissionSeeder`.

## 4. Suggested next steps

- Swap CSV exports for PDF/Excel (`barryvdh/laravel-dompdf`, `maatwebsite/excel`)
- Queue email/SMS delivery in `NotificationService` (hooks are marked)
- Add two-factor auth (Laravel Fortify) and S3 + virus scanning for uploads
- Gantt chart on the project page (e.g. frappe-gantt fed by the milestones API)
- Offline field sync: wrap the API in a PWA with background sync
