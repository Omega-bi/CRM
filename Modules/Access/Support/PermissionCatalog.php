<?php

namespace Modules\Access\Support;

final class PermissionCatalog
{
    /**
     * @return array<int, array{code: string, name: string, group: string, description: string}>
     */
    public static function all(): array
    {
        return [
            ['code' => 'roles.view', 'name' => 'View roles', 'group' => 'Roles and access', 'description' => 'Can see the list of roles and their assigned permissions.'],
            ['code' => 'roles.create', 'name' => 'Create roles', 'group' => 'Roles and access', 'description' => 'Can create new roles and prepare access templates for users.'],
            ['code' => 'roles.update', 'name' => 'Update roles', 'group' => 'Roles and access', 'description' => 'Can rename roles and update their short descriptions.'],
            ['code' => 'roles.delete', 'name' => 'Delete roles', 'group' => 'Roles and access', 'description' => 'Can remove roles that are no longer assigned to users.'],
            ['code' => 'roles.manage', 'name' => 'Manage roles and permissions', 'group' => 'Roles and access', 'description' => 'Full access to roles. Can configure permissions and access levels.'],
            ['code' => 'permissions.view', 'name' => 'View permissions', 'group' => 'Roles and access', 'description' => 'Can view the available permission groups and access rules.'],

            ['code' => 'workspace.view', 'name' => 'View workspace', 'group' => 'Workspace', 'description' => 'Can open company workspace information and basic settings.'],
            ['code' => 'workspace.update', 'name' => 'Update workspace', 'group' => 'Workspace', 'description' => 'Can edit company workspace profile, name and core settings.'],
            ['code' => 'workspace.members.view', 'name' => 'View workspace members', 'group' => 'Workspace', 'description' => 'Can see users connected to the company workspace.'],
            ['code' => 'workspace.members.invite', 'name' => 'Invite workspace members', 'group' => 'Workspace', 'description' => 'Can invite new users to the company workspace.'],
            ['code' => 'workspace.members.update', 'name' => 'Update workspace members', 'group' => 'Workspace', 'description' => 'Can change member roles, positions and access settings.'],
            ['code' => 'workspace.members.remove', 'name' => 'Remove workspace members', 'group' => 'Workspace', 'description' => 'Can remove users from the company workspace.'],

            ['code' => 'settings.view', 'name' => 'View settings', 'group' => 'Settings', 'description' => 'Can open system settings and view configuration sections.'],
            ['code' => 'settings.update', 'name' => 'Update settings', 'group' => 'Settings', 'description' => 'Can change system settings and save configuration updates.'],

            ['code' => 'employees.view', 'name' => 'View employees', 'group' => 'Employees', 'description' => 'Can view employees, contacts, positions and department links.'],
            ['code' => 'employees.create', 'name' => 'Create employees', 'group' => 'Employees', 'description' => 'Can add new employee records to the company structure.'],
            ['code' => 'employees.update', 'name' => 'Update employees', 'group' => 'Employees', 'description' => 'Can edit employee profiles, contacts and position assignments.'],
            ['code' => 'employees.delete', 'name' => 'Delete employees', 'group' => 'Employees', 'description' => 'Can archive or remove employee records from the workspace.'],
            ['code' => 'employees.grant_access', 'name' => 'Grant employee system access', 'group' => 'Employees', 'description' => 'Can create user accounts for employees and give them system access.'],

            ['code' => 'departments.view', 'name' => 'View departments', 'group' => 'Company structure', 'description' => 'Can view the department tree and company structure.'],
            ['code' => 'departments.create', 'name' => 'Create departments', 'group' => 'Company structure', 'description' => 'Can create departments and place them in the structure.'],
            ['code' => 'departments.update', 'name' => 'Update departments', 'group' => 'Company structure', 'description' => 'Can rename departments, move them and update their members.'],
            ['code' => 'departments.delete', 'name' => 'Delete departments', 'group' => 'Company structure', 'description' => 'Can remove departments that are no longer used.'],
            ['code' => 'staff-positions.view', 'name' => 'View staff positions', 'group' => 'Company structure', 'description' => 'Can view staff positions and planned staffing numbers.'],
            ['code' => 'staff-positions.create', 'name' => 'Create staff positions', 'group' => 'Company structure', 'description' => 'Can add new staff positions inside departments.'],
            ['code' => 'staff-positions.update', 'name' => 'Update staff positions', 'group' => 'Company structure', 'description' => 'Can update staff position names, activity and department links.'],
            ['code' => 'staff-positions.delete', 'name' => 'Delete staff positions', 'group' => 'Company structure', 'description' => 'Can remove unused staff positions from the structure.'],

            ['code' => 'customers.view', 'name' => 'View customers', 'group' => 'Customers', 'description' => 'Can view customer companies and their profiles.'],
            ['code' => 'customers.create', 'name' => 'Create customers', 'group' => 'Customers', 'description' => 'Can add new customer companies to CRM.'],
            ['code' => 'customers.update', 'name' => 'Update customers', 'group' => 'Customers', 'description' => 'Can edit customer details, statuses and notes.'],
            ['code' => 'customers.delete', 'name' => 'Delete customers', 'group' => 'Customers', 'description' => 'Can remove customer records from CRM.'],
            ['code' => 'customers.contacts.manage', 'name' => 'Manage customer contacts', 'group' => 'Customers', 'description' => 'Can add, edit and remove customer contact persons.'],

            ['code' => 'projects.view', 'name' => 'View projects', 'group' => 'Projects', 'description' => 'Can view the project portfolio and project cards.'],
            ['code' => 'projects.create', 'name' => 'Create projects', 'group' => 'Projects', 'description' => 'Can create new construction projects in the workspace.'],
            ['code' => 'projects.update', 'name' => 'Update projects', 'group' => 'Projects', 'description' => 'Can edit project information, status and workspace data.'],
            ['code' => 'projects.delete', 'name' => 'Delete projects', 'group' => 'Projects', 'description' => 'Can remove projects from the workspace portfolio.'],
            ['code' => 'projects.members.manage', 'name' => 'Manage project members', 'group' => 'Projects', 'description' => 'Can add users to projects and configure their project access.'],
            ['code' => 'projects.manage', 'name' => 'Manage projects', 'group' => 'Projects', 'description' => 'Full project administration. Can manage structure, members and project settings.'],

            ['code' => 'project.view', 'name' => 'View project', 'group' => 'Project workspace', 'description' => 'Can open a project workspace and view its main information.'],
            ['code' => 'project.update', 'name' => 'Update project', 'group' => 'Project workspace', 'description' => 'Can update project workspace data and operational settings.'],
            ['code' => 'project.tasks.view', 'name' => 'View project tasks', 'group' => 'Project workspace', 'description' => 'Can view tasks, boards and project work items.'],
            ['code' => 'project.tasks.create', 'name' => 'Create project tasks', 'group' => 'Project workspace', 'description' => 'Can create project tasks, boards and columns.'],
            ['code' => 'project.tasks.update', 'name' => 'Update project tasks', 'group' => 'Project workspace', 'description' => 'Can update task names, statuses, assignees and board placement.'],
            ['code' => 'project.tasks.delete', 'name' => 'Delete project tasks', 'group' => 'Project workspace', 'description' => 'Can delete project tasks and remove outdated work items.'],
            ['code' => 'project.files.view', 'name' => 'View project files', 'group' => 'Project workspace', 'description' => 'Can view project files, documents and uploaded materials.'],
            ['code' => 'project.files.upload', 'name' => 'Upload project files', 'group' => 'Project workspace', 'description' => 'Can upload documents, files and supporting materials to the project.'],
            ['code' => 'project.files.delete', 'name' => 'Delete project files', 'group' => 'Project workspace', 'description' => 'Can delete project files and remove outdated documents.'],
            ['code' => 'daily-logs.create', 'name' => 'Create daily logs', 'group' => 'Project workspace', 'description' => 'Can create daily site logs and record field progress.'],
            ['code' => 'acts.approve', 'name' => 'Approve acts', 'group' => 'Project workspace', 'description' => 'Can review and approve project acts and completion documents.'],
        ];
    }

    /**
     * @return array<string>
     */
    public static function codes(): array
    {
        return collect(self::all())
            ->pluck('code')
            ->all();
    }

    /**
     * @return array<string, array<int, array{code: string, name: string, group: string, description: string}>>
     */
    public static function grouped(): array
    {
        return collect(self::all())
            ->groupBy('group')
            ->all();
    }
}
