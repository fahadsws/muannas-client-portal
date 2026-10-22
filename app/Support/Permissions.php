<?php

namespace App\Support;

/** Catalog of everything a role can be allowed to do inside an agency. */
class Permissions
{
    public const GROUPS = [
        'Workspaces' => [
            'workspaces.create' => ['Create workspaces', 'Add a new client workspace.'],
            'workspaces.edit' => ['Rename workspaces', 'Change a workspace name.'],
            'workspaces.delete' => ['Delete workspaces', 'Permanently remove a workspace and its content.'],
        ],
        'Content' => [
            'content.edit' => ['Edit content & strategy', 'Add, edit and delete posts, tabs and the strategy; upload images; change status.'],
        ],
        'Sharing' => [
            'sharing.manage' => ['Share workspaces', 'Manage the client link and invite people to a workspace.'],
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
            ['name' => 'Manager', 'description' => 'Runs every workspace day to day, but not team, roles or agency settings.', 'permissions' => array_values(array_diff($all, ['workspaces.delete', 'team.manage', 'roles.manage', 'settings.manage'])), 'is_system' => true],
            ['name' => 'Editor', 'description' => 'Creates and edits content and downloads documents.', 'permissions' => ['content.edit', 'documents.export'], 'is_system' => true],
            ['name' => 'Viewer', 'description' => 'Can look at everything and leave comments, but not change anything.', 'permissions' => [], 'is_system' => true],
        ];
    }
}
