# Downline Management Module

## Architecture

The Downline Management Module uses the existing `users.sponsor_id` field as the source of truth for direct sponsorship and adds a closure table named `user_hierarchy_paths` for fast descendant queries.

For MLM-style hierarchy, the recommended strategy is:

- Adjacency list for writes: `users.sponsor_id` is simple, readable, and matches registration sponsorship.
- Closure table for reads: `user_hierarchy_paths` stores every ancestor-to-descendant path with depth, making direct recruits, all descendants, branch roots, and permission checks fast.
- Avoid nested set as the primary strategy because sponsor changes create expensive tree rewrites.
- Avoid materialized path as the primary strategy because path strings are harder to enforce with relational permissions and user moves.

This hybrid adjacency plus closure-table strategy gives simple sponsor updates and efficient team visibility reads.

## Database

- `sponsor_relationships`: active and historical sponsor relationships.
- `user_hierarchy_paths`: closure table for ancestor, descendant, and depth.
- `team_visibility_permissions`: explicit visibility exceptions for profile, sensitive data, and exports.
- `users.sponsor_id`: direct sponsor source of truth.
- `users.mentor_id`: current CFM or mentor assignment shortcut.
- `mentor_assignments`: mentor assignment history.
- Existing progress tables power licensing, onboarding, training, apprenticeship, and rank progress summaries.

## Routes

- `/team`: Downline dashboard.
- `/team/tree`: genealogy tree view.
- `/team/org-chart`: executive org chart.
- `/team/table`: CRM-style table view.
- `/team/member/{user}`: member profile.
- `/team/member/{user}/tree`: tree rooted at a member.
- `/team/member/{user}/org-chart`: org chart rooted at a member.
- `/team/export`: CSV export.

## Authorization

Visibility is enforced through Spatie permissions plus policies:

- `view own team`
- `view direct downline`
- `view full downline`
- `view all teams`
- `view team tree`
- `view org chart`
- `view team table`
- `manage team members`
- `assign mentors`
- `export team data`
- `view licensing summary`
- `view training summary`
- `view rank summary`
- `view sensitive profile data`

Policy classes:

- `TeamPolicy`
- `DownlinePolicy`
- `MemberVisibilityPolicy`
- `TeamExportPolicy`
- `UserPolicy`

## Livewire Component Map

- `DownlineDashboard`
- `DownlineTreeView`
- `DownlineTreeNode`
- `DownlineOrgChart`
- `DownlineOrgChartNode`
- `DownlineTableView`
- `DownlineFilters`
- `DownlineSearch`
- `DownlineProfilePanel`
- `DownlineBranchSummary`
- `DownlineBulkActions`
- `AssignCfmModal`
- `MemberQuickViewModal`
- `DownlineExportTool`

## View Strategy

- Tree View: black and gold genealogy layout, zoom controls, compact toggle, branch actions, rank badges, and progress indicators.
- Org Chart: executive layout with branch summaries, leader cards, team health counts, and branch navigation.
- Table View: paginated member table with search, filters, bulk action scaffolding, export, and icon row actions.
- Member View: visibility-safe member profile with sponsor, CFM, rank, team metrics, and progress bars.

## Performance Strategy

- Use `user_hierarchy_paths` for all descendant and branch queries.
- Use `users.sponsor_id` for direct recruits.
- Eager load profile, rank, sponsor, and mentor relationships.
- Paginate table views.
- Use role and permission checks before exports.
- Use cached metrics later for very large teams: total team count, rank distribution, country distribution, and training averages.

## Development Roadmap

- [x] Add closure table migration for hierarchy paths.
- [x] Add sponsor relationship history table.
- [x] Add explicit team visibility permission table.
- [x] Add hierarchy query service.
- [x] Add downline permissions to role seeder.
- [x] Add policy scaffolding.
- [x] Add dashboard, tree, org chart, table, member, and export routes.
- [x] Add first-pass Blade UI for all three core views.
- [x] Add Livewire component stubs for future interactive behavior.
- [x] Add downline demo seeder.
- [ ] Add AJAX lazy loading endpoints for tree branches.
- [ ] Replace tree branch action scaffolds with Livewire modals.
- [ ] Add saved filters and column visibility persistence.
- [ ] Add real bulk actions for CFM assignment, announcements, tags, events, and tasks.
- [ ] Add cached downline metrics table or scheduled cache warmer.
