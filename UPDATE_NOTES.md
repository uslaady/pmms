# PMMS — Update 3

## What changed in Update 3

- **Projects page rebuilt.** A formatting error in Update 2 left the bulk-upload panel unclosed, so
  the registration form and the whole project table were hidden inside it (table appeared only
  after clicking Bulk Upload, Register Project did nothing, stray "@endif" text, table pushed down).
  Now: the table always shows; **Register project** opens the single-project form; **Bulk upload**
  opens the guide and upload box (opening one closes the other).
- Table headings **Project Code** / **Project Name**; search box reads
  "Search Project Code, Project Name, LGA or Contractor".
- Directors see "Your projects are registered under your directorate automatically."
- New **Bulk Upload Guide** wording. The template column is now `project_name`, dates are
  **DD/MM/YYYY** (impossible dates like 31/02/2026 are refused), and files saved from Excel as
  "CSV UTF-8" are now read correctly (previously the first column heading was not recognised).
- **Rejected** status for projects and contractors. The Permanent Secretary rejects a pending
  project or contractor **with a reason**; it is not locked — the Director (or Director Planning
  for contractors) reads the reason, corrects it and clicks **Resubmit for approval**. Rejected
  contractors can be deleted by the executives.
- **Creating users:** Super Admin — any role, including Super Admin; Commissioner — every role
  except Super Admin and Commissioner (creates Permanent Secretaries); Permanent Secretary — every
  role except Super Admin, Commissioner and Permanent Secretary.
- Super Admin and Commissioner both see all projects and every page, and can do everything.
- The "Showing … directorate projects only" banner shows for every Director and Resident Engineer
  with their own directorate.

Apply exactly like Update 2 below (a fresh `migrate:fresh --seed` is required again because new
columns were added).

---

# Update 2: workflow, locking, contractor documents, password policy

## How to apply

This update **deletes all existing data and users** (as instructed — it was dummy data)
and rebuilds the database with new dummy data.

1. Back up your `pmms` folder and database (in case you want anything back).
2. Unzip this package **over** your `pmms` folder and replace all files.
3. Delete the old uploaded files of the dummy projects:
   `storage/app/public/projects` (and `public/storage/projects` if it is a real folder, not a link).
4. In a terminal inside `pmms`:

   ```bash
   php artisan migrate:fresh --seed
   php artisan storage:link
   php artisan optimize:clear
   ```
5. Sign in as `admin@works.gov` / `Superadmin001`. You will be asked to set your own password first.

Before go-live, run `php artisan migrate:fresh` followed by
`php artisan db:seed --class=RoleAndPermissionSeeder` and `php artisan db:seed --class=BornoLocationSeeder`
(no dummy data), then register the real projects and contractors.

## Accounts (all must change password at first sign-in)

| Email | Role | Password |
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
| prece@works.gov | Resident Engineer Civil | Residentcivil2 |
| prepb@works.gov | Resident Engineer Public Building | Residentpublic2 |
| **preee@works.gov** | **Resident Engineer Electrical (new)** | Residentelectrical2 |
| **preme@works.gov** | **Resident Engineer Mechanical (new)** | Residentmechanical2 |

## The workflow

1. **Director** registers a project (own directorate only) → status **Pending approval**.
2. **Permanent Secretary** approves it → **Approved** (or rejects it with a reason → **Rejected**;
   the Director corrects it and resubmits).
3. **Director Planning** registers contractors (single or bulk) with their documents → **Pending**;
   the **Permanent Secretary** checks and makes them **Active**.
4. **Director Planning** assigns an Active contractor to the approved project and registers the contract.
5. **Finance** records payments → **Permanent Secretary** approves → Finance marks them Paid.
6. **Resident Engineer** and **Director** submit progress updates, milestones, photos, documents
   and inspections → **Permanent Secretary** approves progress updates.

Each step notifies the next person in the chain.

## Who can do what

| | Admin | Commissioner | Perm Sec | Directors | Dir. Planning | Dir. Finance | Resident Eng. |
|---|---|---|---|---|---|---|---|
| Dashboard, Projects | All | All | All | Own directorate | All | All | Own directorate |
| Register / edit projects | ✓ | ✓ | – | Own directorate | Any directorate | – | – |
| Approve projects, change status | ✓ | ✓ | ✓ | – | – | – | – |
| Delete projects | ✓ | ✓ | ✓ | – | – | – | – |
| Assign contractor, register contract | ✓ | ✓ | – | – | ✓ | – | – |
| Record payment / mark paid | ✓ | ✓ | – | – | – | ✓ | – |
| Approve payments & progress updates | ✓ | ✓ | ✓ | – | – | – | – |
| Progress, milestones, photos, documents, inspections | ✓ | ✓ | – | Own directorate | – | – | Own directorate |
| Contractors page | Full | Full | View + status + delete locked | – | Register, bulk, edit, documents | – | – |
| GIS map / Analytics | All | All | All | Own directorate | All | All | Own directorate |
| Reports | All | All | All | Own directorate | All | All | – |
| Audit log | ✓ | ✓ | – | – | – | – | – |
| Users: create | Any role | All but Super Admin & Commissioner | All but Super Admin, Commissioner & Perm Sec | – | – | – | – |
| Users: status | Anyone | All but Super Admin | All but Admin & Commissioner | – | – | – | – |
| Users: reset password | Anyone | All but Super Admin | – | – | – | – | – |

## Locked records

- **Projects** that are **Suspended, Cancelled or Deactivated** are locked: all details stay visible
  (including the contractor), but nobody can add, change or remove anything — no updates, photos,
  milestones, inspections, documents, payments or contractor changes. Only the Super Admin,
  Commissioner or Permanent Secretary can edit the project, change its status (to reopen it) or delete it.
- **Contractors** that are **Deactivated, Suspended or Blacklisted** are locked: their details and
  documents can only be edited — or the contractor deleted — by the Super Admin, Commissioner or
  Permanent Secretary. Their existing projects keep running and can still be updated, and keep
  showing the contractor's name (even after deletion); they just cannot receive new projects.
- **Users** that are **Deactivated, Suspended or Blacklisted** cannot sign in; the sign-in page tells
  them their status (only after a correct password). If it happens while they are signed in, they are
  signed out at their next click.

## Bugs fixed

1. **PDF export crashed** on servers without PHP's `iconv` extension, and could break on special
   characters (₦, dashes). Fixed; PDFs are generated on any PHP setup.
2. **Inactive contractors could be assigned** to a single project. Now only Active contractors can be
   assigned, from the project page, by Director Planning — for single projects and bulk upload alike.
3. **Editing a project removed its contractor** if the contractor had become inactive. The contractor
   is no longer part of the project edit form at all, so an edit can never remove or change it.
4. **Contractors page was open to every role.** Now only Admin, Commissioner, Perm Sec and Director Planning.
5. **Approving an already-paid payment reset it to "approved"**, removing it from Total Paid.
   Only pending payments can now be approved.
6. **PDF text wrapping could cut names with special characters in half.** Fixed.
