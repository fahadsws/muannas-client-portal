<?php

namespace App\Support;

/** Catalog of everything a role can be allowed to do inside an agency. */
class Permissions
{
    public const GROUPS = [
        'Client management' => [
            'workspaces.create' => ['Add clients', 'Add a new client, with a login for them.'],
            'workspaces.edit' => ['Edit clients', 'Change a client name, logo and assigned team.'],
            'workspaces.delete' => ['Delete clients', 'Permanently remove a client and everything in their portal.'],
        ],
        'Clients' => [
            'clients.all' => ['Access every client', 'See all clients. Without this, a team member only sees the clients assigned to them.'],
        ],
        'Billing & reports' => [
            'billing.manage' => ['Manage invoices', 'Upload invoices, mark them paid and see payment history.'],
            'reports.manage' => ['Manage performance reports', 'Enter monthly numbers and upload report PDFs.'],
        ],
        'Content' => [
            'content.edit' => ['Edit content & strategy', 'Add, edit and delete posts, tabs and the strategy; upload images; change status.'],
        ],
        'Sharing' => [
            'sharing.manage' => ['Manage client logins', 'Create and reset the logins clients use to sign in.'],
        ],
        'Documents' => [
            'documents.export' => ['Generate documents', 'Create and download PDF, Word and CSV reports.'],
            'templates.manage' => ['Edit document templates', 'Create, change and delete templates.'],
        ],
        'Agency' => [
            'team.manage' => ['Manage team', 'Add, edit and remove team members and change their roles.'],
            'roles.manage' => ['Manage roles', 'Create, edit and delete roles and their permissions.'],
            'settings.manage' => ['Agency settings', 'Edit the agency name and brand colour.'],
        ],
    ];

    public static function keys(): array
    {
        return collect(self::GROUPS)->flatMap(fn ($g) => array_keys($g))->values()->all();
    }

    public static function label(string $key): string
    {
        foreach (self::GROUPS as $g) {
            if (isset($g[$key])) {
                return $g[$key][0];
            }
        }

        return $key;
    }

    /** Roles every agency starts with. 'Owner' is locked and always has everything. */
    public static function defaultRoles(): array
    {
        $all = self::keys();

        return [
            ['name' => 'Owner', 'description' => 'Full control of the agency. Cannot be changed.', 'permissions' => $all, 'is_system' => true],
            ['name' => 'Manager', 'description' => 'Runs every client day to day, but not team, roles or agency settings.', 'permissions' => array_values(array_diff($all, ['workspaces.delete', 'team.manage', 'roles.manage', 'settings.manage'])), 'is_system' => true],
            ['name' => 'Editor', 'description' => 'Works on assigned clients: creates content, projects and files, and downloads documents.', 'permissions' => ['content.edit', 'documents.export', 'reports.manage'], 'is_system' => true],
            ['name' => 'Viewer', 'description' => 'Can look at everything and leave comments, but not change anything.', 'permissions' => [], 'is_system' => true],
        ];
    }
}
