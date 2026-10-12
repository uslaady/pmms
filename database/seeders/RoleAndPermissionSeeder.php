<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Roles, permissions and the 13 starter accounts: the 11 from the Permanent
 * Secretary's "Updated Roles 08/10/2026" document plus the Resident Engineers
 * for Electrical and Mechanical Engineering. Every account must change its
 * password at first sign-in.
 *
 * Safe to re-run:  php artisan db:seed --class=RoleAndPermissionSeeder
 *  - roles/permissions are created or updated, never duplicated
 *  - the 13 accounts get their default password reset (and must change it) each run
 *  - roles from the old 12-role design are retired and their users deactivated
 */
class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Administration
            'users.view', 'users.create', 'users.status', 'users.reset_password',
            'roles.manage', 'settings.manage', 'audit.view',
            // Projects
            'projects.view', 'projects.create', 'projects.edit', 'projects.delete', 'projects.approve',
            'projects.assign_contractor',
            // Contractors
            'contractors.view', 'contractors.register', 'contractors.edit', 'contractors.status',
            'contractors.documents', 'contractors.delete',
            // Contracts & money
            'contracts.manage', 'contracts.view',
            'payments.record', 'payments.approve', 'payments.view',
            // Field work
            'progress.update', 'progress.approve', 'milestones.update',
            'inspections.create', 'inspections.view',
            'images.upload', 'documents.upload',
            // Insight
            'gis.manage', 'gis.view',
            'reports.view', 'reports.export',
            'dashboard.executive', // = Analytics page
            'dashboard.view',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name], ['display_name' => str_replace('.', ' ', ucfirst($name))]);
        }

        // Drop permissions that no longer exist (users.manage, contractors.manage)
        Permission::whereNotIn('name', $permissions)->get()->each(function (Permission $p) {
            $p->roles()->detach();
            $p->delete();
        });

        // Resident Engineer: site work on his directorate's projects (always limited to it by middleware).
        $resident = [
            'projects.view',
            'progress.update', 'milestones.update',
            'inspections.create', 'inspections.view',
            'images.upload', 'documents.upload',
            'dashboard.view',
            'gis.view', 'dashboard.executive', // personalised map & analytics (own directorate), no reports
        ];

        // Director: everything his Resident Engineer does, plus registering and editing
        // his directorate's projects and personalised reports. He never assigns
        // contractors, registers contracts or deletes projects.
        $director = array_merge($resident, [
            'projects.create', 'projects.edit',
            'contracts.view', 'payments.view',
            'gis.manage',
            'reports.view', 'reports.export',
        ]);

        $roles = [
            Role::SUPER_ADMIN => ['Super Admin', $permissions],
            Role::COMMISSIONER => ['Honorable Commissioner', $permissions],
            Role::PERMANENT_SECRETARY => ['Permanent Secretary', [
                'projects.view', 'projects.approve', 'projects.delete',
                'progress.approve',
                'contractors.view', 'contractors.status', 'contractors.delete',
                'contracts.view', 'payments.view', 'payments.approve',
                'users.view', 'users.create', 'users.status',
                'inspections.view',
                'gis.view', 'dashboard.view', 'dashboard.executive',
                'reports.view', 'reports.export',
            ]],
            Role::DIRECTOR_CIVIL => ['Director Civil Engineering', $director],
            Role::DIRECTOR_PUBLIC_BUILDING => ['Director Public Building', $director],
            Role::DIRECTOR_ELECTRICAL => ['Director Electrical Engineering', $director],
            Role::DIRECTOR_MECHANICAL => ['Director Mechanical Engineering', $director],
            Role::DIRECTOR_PLANNING => ['Director Planning, Research & Statistics', [
                'projects.view', 'projects.create', 'projects.edit', 'projects.assign_contractor',
                'contractors.view', 'contractors.register', 'contractors.edit', 'contractors.documents',
                'contracts.view', 'contracts.manage', 'payments.view',
                'documents.upload', 'inspections.view',
                'gis.view', 'gis.manage',
                'dashboard.view', 'dashboard.executive',
                'reports.view', 'reports.export',
            ]],
            Role::DIRECTOR_FINANCE => ['Director Finance & Accounts', [
                'projects.view', 'contracts.view',
                'payments.view', 'payments.record',
                'inspections.view',
                'gis.view', 'dashboard.view', 'dashboard.executive',
                'reports.view', 'reports.export',
            ]],
            Role::RESIDENT_CIVIL => ['Project Resident Engineer Civil', $resident],
            Role::RESIDENT_PUBLIC_BUILDING => ['Project Resident Engineer Public Building', $resident],
            Role::RESIDENT_ELECTRICAL => ['Project Resident Engineer Electrical', $resident],
            Role::RESIDENT_MECHANICAL => ['Project Resident Engineer Mechanical', $resident],
        ];

        foreach ($roles as $name => [$display, $perms]) {
            $role = Role::updateOrCreate(['name' => $name], ['display_name' => $display, 'is_active' => true]);
            $role->permissions()->sync(Permission::whereIn('name', $perms)->pluck('id'));
        }

        // ---- Retire the old role set ------------------------------------------
        $accounts = [
            ['admin@works.gov', 'Super Admin', Role::SUPER_ADMIN, 'Superadmin001'],
            ['com@works.gov', 'Honorable Commissioner', Role::COMMISSIONER, 'Administrator001'],
            ['ps@works.gov', 'Permanent Secretary', Role::PERMANENT_SECRETARY, 'Permsecretary001'],
            ['dce@works.gov', 'Director Civil Engineering', Role::DIRECTOR_CIVIL, 'Dircivil001'],
            ['dpb@works.gov', 'Director Public Building', Role::DIRECTOR_PUBLIC_BUILDING, 'Dirpublic001'],
            ['dee@works.gov', 'Director Electrical Engineering', Role::DIRECTOR_ELECTRICAL, 'Direlectrical001'],
            ['dme@works.gov', 'Director Mechanical Engineering', Role::DIRECTOR_MECHANICAL, 'Dirmechanical001'],
            ['dpr@works.gov', 'Director Planning, Research & Statistics', Role::DIRECTOR_PLANNING, 'Dirplanning001'],
            ['dfa@works.gov', 'Director Finance & Accounts', Role::DIRECTOR_FINANCE, 'Dirfinance001'],
            ['prece@works.gov', 'Project Resident Engineer Civil', Role::RESIDENT_CIVIL, 'Residentcivil2'],
            ['prepb@works.gov', 'Project Resident Engineer Public Building', Role::RESIDENT_PUBLIC_BUILDING, 'Residentpublic2'],
            // Added on the Perm Sec's instruction: one Resident Engineer per directorate
            ['preee@works.gov', 'Project Resident Engineer Electrical', Role::RESIDENT_ELECTRICAL, 'Residentelectrical2'],
            ['preme@works.gov', 'Project Resident Engineer Mechanical', Role::RESIDENT_MECHANICAL, 'Residentmechanical2'],
        ];

        $retiredIds = Role::whereIn('name', Role::RETIRED_ROLES)->pluck('id');
        $keepEmails = array_column($accounts, 0);

        User::whereIn('role_id', $retiredIds)->whereNotIn('email', $keepEmails)
            ->update(['status' => 'deactivated', 'is_active' => false]);

        Role::whereIn('id', $retiredIds)->each(function (Role $r) {
            $r->permissions()->sync([]);
            $r->update(['is_active' => false]);
        });

        // ---- The 11 documented accounts ---------------------------------------
        foreach ($accounts as [$email, $name, $roleName, $password]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'role_id' => Role::where('name', $roleName)->value('id'),
                'password' => $password,
                'status' => 'active',
                'is_active' => true,
                'department' => \App\Models\Project::DIRECTORATES[Role::DIRECTORATE_BY_ROLE[$roleName] ?? ''] ?? null,
                'must_change_password' => true,
            ]);
        }
    }
}
