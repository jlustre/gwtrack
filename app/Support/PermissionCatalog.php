<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

class PermissionCatalog
{
    /**
     * @return array<string, array{label: string, description: string, match: list<string>}>
     */
    public static function groups(): array
    {
        return [
            'platform' => [
                'label' => 'Platform & Admin',
                'description' => 'Core admin access for users, roles, ranks, and dashboards.',
                'match' => ['view dashboard', 'manage users', 'manage roles', 'manage ranks', 'view sensitive profile data', 'view analytics'],
            ],
            'training' => [
                'label' => 'Training & Assessments',
                'description' => 'Academy content, enrollments, assessments, and resources.',
                'match' => ['manage training', 'manage training enrollments', 'manage assessments', 'manage resources', 'view training summary'],
            ],
            'lifecycle' => [
                'label' => 'Onboarding & Advancement',
                'description' => 'Onboarding, licensing, rank advancement, and events.',
                'match' => ['manage onboarding', 'manage licensing', 'manage rank advancement', 'manage events', 'view licensing summary', 'view rank summary'],
            ],
            'communications' => [
                'label' => 'Communications',
                'description' => 'Announcements, campaigns, newsletters, and recognition.',
                'match' => ['manage announcements', 'view announcements', 'create announcements', 'edit announcements', 'delete announcements', 'publish announcements', 'manage recognition posts', 'manage recognition', 'manage campaigns', 'manage newsletters', 'view communication analytics', 'send announcement', 'send broadcast'],
            ],
            'mentorship' => [
                'label' => 'Mentorship & CFM',
                'description' => 'Mentor assignment, apprentices, CFM certification, and effectiveness.',
                'match' => ['assign mentors', 'view apprentices', 'update apprentice progress', 'approve apprenticeship', 'manage CFM certification', 'submit mentor feedback', 'view own mentor feedback requests', 'view CFM effectiveness', 'manage CFM evaluations', 'view CFM reports', 'manage mentor reviews', 'manage action plans'],
            ],
            'team' => [
                'label' => 'Team & Downline',
                'description' => 'Team visibility, hierarchy, and member management.',
                'match' => ['view team', 'manage team', 'view own team', 'view direct downline', 'view full downline', 'view all teams', 'view team tree', 'view org chart', 'view team table', 'manage team members', 'export team data'],
            ],
            'crm' => [
                'label' => 'Prospects & CRM',
                'description' => 'Prospects, sharing, imports, and funnel configuration.',
                'match' => ['manage prospects', 'view shared prospects', 'share prospects', 'import prospects', 'export prospects', 'view crm funnels', 'manage crm funnels'],
            ],
            'calendar' => [
                'label' => 'Calendar',
                'description' => 'Personal and organization calendar controls.',
                'match' => ['view calendar', 'create calendar events', 'edit own calendar events', 'delete own calendar events', 'manage team calendar', 'manage training calendar', 'manage licensing calendar', 'manage prospect appointments', 'manage mentor sessions', 'manage rank review events', 'view shared calendar events', 'invite attendees', 'manage event visibility', 'view private events', 'manage organization calendar'],
            ],
            'booking' => [
                'label' => 'Bookings',
                'description' => 'Availability, booking links, and session requests.',
                'match' => ['view booking dashboard', 'manage own availability', 'manage own booking event types', 'create booking links', 'view own bookings', 'book mentor sessions', 'approve booking requests', 'decline booking requests', 'reschedule bookings', 'cancel bookings', 'manage team bookings', 'view apprentice bookings', 'manage booking settings'],
            ],
            'fna' => [
                'label' => 'FNA',
                'description' => 'Financial needs analysis records and reviews.',
                'match' => ['manage fna records', 'view shared fna records', 'submit fna for review', 'review trainee fna records', 'view fna agency reports', 'view fna financial details', 'export fna records', 'manage fna settings'],
            ],
            'goals' => [
                'label' => 'Goals',
                'description' => 'Personal and team goal coaching.',
                'match' => ['manage goals', 'view team goals', 'coach goals'],
            ],
            'support' => [
                'label' => 'Support',
                'description' => 'Support tickets, wishlist, and support operations.',
                'match' => ['submit support ticket', 'view own support tickets', 'comment on own ticket', 'reopen own ticket', 'vote on wishlist items', 'view all support tickets', 'manage support tickets', 'assign support tickets', 'update support ticket status', 'add internal support notes', 'manage enhancement wishlist', 'manage support settings', 'view support reports'],
            ],
            'messaging' => [
                'label' => 'Messaging',
                'description' => 'Direct messages, groups, and conversation lifecycle.',
                'match' => ['send messages', 'view conversations', 'delete own messages', 'delete group messages', 'manage message groups', 'send message broadcasts', 'archive conversations', 'restore conversations'],
            ],
            'notifications' => [
                'label' => 'Notifications',
                'description' => 'In-app notifications, templates, and escalation.',
                'match' => ['view notifications', 'manage own notification preferences', 'manage notification templates', 'manage notification settings', 'view notification logs', 'manage escalation rules', 'view critical alerts', 'delete notifications'],
            ],
            'backups' => [
                'label' => 'Backups',
                'description' => 'Backup creation, restore, and schedules (super-admin).',
                'match' => ['view backups', 'create backups', 'download backups', 'delete backups', 'restore backups', 'manage backup schedules', 'manage backups'],
            ],
            'other' => [
                'label' => 'Other',
                'description' => 'Permissions that do not match a primary group.',
                'match' => [],
            ],
        ];
    }

    /**
     * @return Collection<string, Collection<int, Permission>>
     */
    public static function groupedPermissions(?string $search = null): Collection
    {
        $query = Permission::query()->orderBy('name');

        if (filled($search)) {
            $query->where('name', 'like', '%'.trim($search).'%');
        }

        $permissions = $query->get();
        $groups = collect(self::groups())->mapWithKeys(fn (array $meta, string $key) => [$key => collect()]);

        $claimed = [];

        foreach (self::groups() as $key => $meta) {
            if ($key === 'other') {
                continue;
            }

            foreach ($meta['match'] as $name) {
                $permission = $permissions->firstWhere('name', $name);

                if ($permission) {
                    $groups[$key]->push($permission);
                    $claimed[$name] = true;
                }
            }
        }

        foreach ($permissions as $permission) {
            if (! isset($claimed[$permission->name])) {
                $groups['other']->push($permission);
            }
        }

        return $groups->filter(fn (Collection $items) => $items->isNotEmpty());
    }

    /**
     * Permissions that are enforced in application code (routes, services, policies, Livewire).
     *
     * @return list<string>
     */
    public static function implementedPermissions(): array
    {
        return [
            'view dashboard',
            'manage users',
            'manage roles',
            'manage ranks',
            'manage training',
            'manage training enrollments',
            'manage assessments',
            'manage resources',
            'manage onboarding',
            'manage licensing',
            'manage rank advancement',
            'manage events',
            'manage announcements',
            'view announcements',
            'create announcements',
            'edit announcements',
            'delete announcements',
            'publish announcements',
            'manage recognition posts',
            'manage campaigns',
            'manage newsletters',
            'assign mentors',
            'view apprentices',
            'update apprentice progress',
            'approve apprenticeship',
            'manage CFM certification',
            'view team',
            'manage team',
            'view own team',
            'view direct downline',
            'view full downline',
            'view all teams',
            'view team tree',
            'view org chart',
            'view team table',
            'manage team members',
            'export team data',
            'view licensing summary',
            'view training summary',
            'view rank summary',
            'view sensitive profile data',
            'manage prospects',
            'view shared prospects',
            'share prospects',
            'import prospects',
            'export prospects',
            'view crm funnels',
            'manage crm funnels',
            'view calendar',
            'create calendar events',
            'edit own calendar events',
            'delete own calendar events',
            'manage team calendar',
            'manage training calendar',
            'manage licensing calendar',
            'manage prospect appointments',
            'manage mentor sessions',
            'manage rank review events',
            'view shared calendar events',
            'invite attendees',
            'manage event visibility',
            'view private events',
            'manage organization calendar',
            'view booking dashboard',
            'manage own availability',
            'manage own booking event types',
            'create booking links',
            'view own bookings',
            'book mentor sessions',
            'approve booking requests',
            'decline booking requests',
            'reschedule bookings',
            'cancel bookings',
            'manage team bookings',
            'view apprentice bookings',
            'manage booking settings',
            'manage fna records',
            'view shared fna records',
            'submit fna for review',
            'review trainee fna records',
            'view fna agency reports',
            'view fna financial details',
            'export fna records',
            'manage fna settings',
            'manage goals',
            'view team goals',
            'coach goals',
            'submit support ticket',
            'view own support tickets',
            'comment on own ticket',
            'reopen own ticket',
            'vote on wishlist items',
            'view all support tickets',
            'manage support tickets',
            'assign support tickets',
            'update support ticket status',
            'add internal support notes',
            'manage enhancement wishlist',
            'manage support settings',
            'view support reports',
            'send messages',
            'view conversations',
            'delete own messages',
            'delete group messages',
            'manage message groups',
            'send message broadcasts',
            'view communication analytics',
            'archive conversations',
            'restore conversations',
            'view notifications',
            'manage own notification preferences',
            'send announcement',
            'send broadcast',
            'manage notification templates',
            'manage notification settings',
            'view notification logs',
            'manage escalation rules',
            'view critical alerts',
            'delete notifications',
            'submit mentor feedback',
            'view own mentor feedback requests',
            'view CFM effectiveness',
            'manage CFM evaluations',
            'view CFM reports',
            'manage recognition',
            'manage mentor reviews',
            'view analytics',
            'manage action plans',
            'view backups',
            'create backups',
            'download backups',
            'delete backups',
            'restore backups',
            'manage backup schedules',
            'manage backups',
        ];
    }

    public static function isImplemented(string $permission): bool
    {
        return in_array($permission, self::implementedPermissions(), true);
    }
}
