# GWTracks Development Roadmap

This roadmap organizes GWTracks.com into practical delivery phases. The first priority is a clean Laravel foundation, then progressively adding the business modules around onboarding, licensing, mentorship, apprenticeship, training, assessments, rank advancement, and team visibility.

## Phase 0: Project Setup And Architecture

### Goals

- Establish the Laravel application foundation.
- Install and configure the TALL stack.
- Create base planning documents and architecture decisions.

### Tasks

- [x] Install Laravel 13.
- [x] Create local `.env` file.
- [x] Configure MySQL database connection.
- [x] Install Laravel Breeze with Blade stack.
- [x] Install Livewire.
- [x] Confirm Alpine.js and Tailwind CSS setup.
- [x] Install Spatie Laravel Permission.
- [x] Publish Spatie permission migrations.
- [x] Create base app layout with sidebar and topbar.
- [x] Create grouped sidebar navigation with expandable sections.
- [x] Define design tokens for navy, gold, white, soft gray, and premium auth gold/black treatment.
- [x] Create initial documentation.
- [x] Configure PHPUnit to use an isolated in-memory SQLite test database.

### Deliverables

- Working Laravel project.
- Auth scaffolding.
- Tailwind and Livewire available.
- Spatie Permission installed.
- Planning documents completed.

## Phase 1: Identity, Roles, Ranks, And Teams

### Goals

- Build the foundation for users, access, ranks, and teams.

### Tasks

- [x] Create profile model and migration.
- [x] Create ranks table and model.
- [x] Create teams table and model.
- [x] Add rank, team, sponsor, mentor, and status fields to users.
- [x] Add joined date field to users.
- [x] Add last login timestamp to users.
- [x] Add last login IP to users.
- [x] Add online status flag to users.
- [x] Track login timestamp, login IP, and online status during authentication.
- [x] Clear online status during logout.
- [x] Add Spatie HasRoles trait to User model.
- [x] Create RankSeeder.
- [x] Seed Greatway Producer/Builder ranks (TA through NEVC) with FYO, PPB, track, and Field Leader flags.
- [x] Remap leftover legacy rank codes onto Greatway codes.
- [x] Create RolePermissionSeeder.
- [x] Convert role names to lowercase dash slugs.
- [x] Document distinction between Greatway ranks and GWTracks permission roles.
- [x] Add default `member` role.
- [x] Add operational `admin` role.
- [x] Create initial `super-admin` user seeder.
- [x] Build admin user list scaffold.
- [x] Build role-based Admin Management scaffold for `super-admin`, `admin`, `agency-owner`, `team-leader`, `certified-field-mentor`, and `trainer`.
- [x] Restrict role, rank, team, and table management pages by admin role.
- [x] Build basic team hierarchy data model.
- [x] Add closure-table downline hierarchy support for fast descendant queries.
- [x] Add sponsor relationship history table.
- [x] Add team visibility permission table for explicit access exceptions.
- [x] Add hierarchy query service for direct recruits, descendants, visible members, and member metrics.
- [x] Assign new registered users the `member` role.
- [x] Assign new registered users the TA rank.
- [x] Assign new registered users to the same team as their sponsor.
- [x] Build full admin user management CRUD for role, rank, team, status, and sponsor changes.
- [x] Build generic Admin Management CRUD for important setup tables.
- [x] Document strict sponsorship hierarchy visibility and global information exception.
- [x] Build reusable hierarchy-scoped query helpers for user and team visibility data.
- [ ] Extend hierarchy-scoped query helpers to resource, progress, and communication data.
- [ ] Add global vs hierarchy-scoped visibility fields to content modules that need cross-hierarchy access.

### Invitation And Registration Access

- [x] Create registration invitations table and model.
- [x] Require invitation links for public registration.
- [x] Store tracked registration codes for invitations.
- [x] Store `sponsor_id` from the invitation during registration.
- [x] Block open registration without an invitation code.
- [x] Block duplicate active invitations for the same email.
- [x] Block invitations to emails that are already registered.
- [x] Deactivate invitation codes after successful registration.
- [x] Hide accepted invitations from the sponsor profile.
- [x] Allow sponsors to delete active invitations and reinvite if the recipient has not registered.
- [x] Require GW Associate ID during registration.
- [x] Require active Greatway Financial Group associate confirmation.
- [x] Require sponsor confirmation on the registration form.
- [x] Show the sponsor tied to the registration code.
- [x] Add database-managed invitation email template.
- [x] Add invitation email preview and editable message popup.
- [x] Require the registration link to remain in the invitation email message.
- [x] Send invitation emails from the logged-in sponsor identity.

### Deliverables

- Users can have roles, ranks, teams, sponsors, and mentors.
- Initial roles, permissions, and ranks are seeded.
- Admins can begin managing user records.

## Phase 2: Portal Shell And Dashboard

### Goals

- Create the premium financial services portal experience.
- Give users a useful first screen after login.

### Tasks

- [x] Build responsive sidebar navigation.
- [x] Build grouped sidebar navigation for Trackers, My Team, Communications, Resources, and Admin Management.
- [x] Move My Tasks to the top of the Trackers menu group.
- [x] Build topbar with responsive search, notification dropdown, and avatar menu.
- [x] Build dashboard page shell.
- [x] Create dashboard cards for rank, onboarding, licensing, apprenticeship, and training.
- [x] Create dashboard cards for announcements and events.
- [x] Create Calendar & Events module database scaffold.
- [x] Create Calendar & Events models and authorization policies.
- [x] Create Calendar & Events month, week, day, agenda, settings, detail, and export routes.
- [x] Build Google/Outlook-style calendar workspace scaffold with left controls, main grid, right details panel, and mobile create button.
- [x] Add calendar-specific role permissions.
- [x] Add Calendar & Events seed data for team, training, licensing, prospect, FAP, CFM, rank review, and recognition scenarios.
- [x] Add Calendar & Events Livewire component scaffolds.
- [x] Add Calendar & Events module documentation.
- [x] Add permission-aware navigation.
- [x] Add global search scaffold page.
- [x] Add rank badge display.
- [x] Add progress bar display.
- [x] Build My Tasks action center for checklist confirmations, CFM assignment tasks, invitation email follow-up, and rank review tasks.
- [x] Make My Tasks fast action buttons extensible from controller definitions.
- [x] Add demo task scenario seeders for checklist confirmations, CFM assignment, invitation email follow-up, and rank review data.
- [x] Move checklist confirmation review panels from individual checklist pages into My Tasks.
- [x] Build premium invitation registration page.
- [x] Build premium login page.
- [x] Build premium forgot password page.
- [x] Build premium reset password page.
- [x] Build premium confirm password page.
- [x] Build premium email verification page.
- [x] Add shared premium auth shell.
- [x] Add registration/login auth image with gradient mask.

### Deliverables

- Authenticated portal shell.
- Responsive dashboard.
- Permission-aware navigation.

## Phase 3: Onboarding And Licensing

### Goals

- Let users track onboarding and licensing progress.
- Let admins configure steps.

### Tasks

- [x] Create onboarding step migrations.
- [x] Add global and country-specific applicability to onboarding steps.
- [x] Create user onboarding progress migration.
- [x] Create licensing step migrations.
- [x] Create user licensing progress migration.
- [x] Create business-relevant Licensing checklist seeder.
- [x] Add responsible-party accountability to Licensing checklist items.
- [x] Add `SP` direct sponsor code to checklist responsible-party options.
- [x] Add notified-party completion notification targets to Licensing checklist items.
- [ ] Create onboarding and licensing models.
- [x] Build My Onboarding tracker page with progress cards, progress graph, and interactive checklist.
- [x] Add pending confirmation, reviewer comments, confirm/reject workflow, and confirmation stats to My Onboarding.
- [x] Build Licensing Tracker page with progress cards, progress graph, interactive checklist, and pending confirmation workflow.
- [x] Build admin onboarding step manager.
- [x] Build admin licensing step manager.
- [x] Build admin Checklists hub (`/admin/checklists`) with Types, Instructions, and Items pages and Back to Checklists navigation.
- [x] Add progress summaries to dashboard scaffold.

### Deliverables

- Users can view onboarding and licensing checklists.
- Progress can be tracked by user.
- Admins can manage checklist steps.

## Phase 4: Certified Field Mentor And Apprenticeship

### Goals

- Support the core CFM assignment and Field Apprenticeship Program workflow.

### Tasks

- [x] Create mentor assignments migration and model.
- [x] Create apprenticeship programs migration.
- [x] Create apprenticeship steps migration.
- [x] Create user apprenticeship progress migration.
- [x] Create mentor notes migration.
- [x] Create business-relevant Field Apprenticeship Program checklist seeder.
- [x] Add responsible-party accountability to FAP checklist items.
- [x] Add `SP` direct sponsor code to FAP checklist responsible-party options.
- [x] Add notified-party completion notification targets to FAP checklist items.
- [ ] Create apprenticeship program, step, progress, and mentor note models.
- [ ] Build admin mentor assignment screen.
- [ ] Restrict CFM assignment to agency owners for members under their sponsorship hierarchy.
- [ ] Prioritize agency owner's own CFM pool in assignment workflows.
- [ ] Support limited cross-agency CFM profile visibility for agency owners.
- [ ] Build CFM apprentice list.
- [x] Build Field Apprenticeship progress page with progress cards, progress graph, interactive checklist, and pending confirmation workflow.
- [ ] Build mentor notes panel.
- [ ] Build apprenticeship approval workflow.
- [x] Add assigned CFM card to dashboard scaffold.
- [x] Add Prospect Management scaffold under My Team.
- [x] Add Downline Management dashboard under My Team.
- [x] Add Genealogy Tree view for sponsor-to-recruit relationships.
- [x] Add Organizational Chart view for executive branch structure.
- [x] Add CRM-style Downline Table view with filters, pagination, export link, and icon actions.
- [x] Add downline member profile page.
- [x] Add Downline Livewire component scaffolds.
- [x] Add Downline demo seeder and hierarchy path rebuild.

### Deliverables

- New Recruits and Associates can be assigned to CFMs.
- CFMs can view assigned apprentices.
- Apprenticeship progress can be tracked.
- Mentor notes can be recorded.
- Apprenticeship can be approved by authorized users.

## Phase 4A: CFM Mentor Scheduling And Booking

### Goals

- Add Calendly/Cal.com-style scheduling for Certified Field Mentors and assigned trainees.
- Connect confirmed mentor bookings to the Calendar & Events module and Field Apprenticeship workflow.

### Tasks

- [x] Create booking and availability database schema foundation.
- [x] Create booking event type, availability, booking link, booking, attendee, question, answer, reschedule, cancellation, and activity log models.
- [x] Add booking authorization policy scaffolds.
- [x] Add booking-specific Spatie permissions.
- [x] Add booking permissions to CFM, trainer, team leader, member, associate, new recruit, agency owner, admin, and super-admin role flows.
- [x] Add Mentor Scheduling navigation under Communications.
- [x] Add CFM Booking Dashboard page scaffold.
- [x] Add My Availability page scaffold.
- [x] Add Booking Event Types page scaffold.
- [x] Add Booking Links page scaffold.
- [x] Add Booking Requests page scaffold.
- [x] Add My Bookings page scaffold.
- [x] Add Mentor Session Calendar page scaffold.
- [x] Add Booking Settings page scaffold.
- [x] Add public booking page route scaffolds.
- [x] Add Admin Management scaffolds for booking event types, booking links, and bookings.
- [x] Add Booking Livewire component scaffolds.
- [x] Add default CFM event type, availability, question, link, booking request, and confirmed booking seed data.
- [x] Document scheduling architecture, workflows, authorization, conflict detection, and calendar auto-creation logic.
- [ ] Build Livewire CRUD for availability schedules, rules, overrides, and blackout dates.
- [ ] Build booking event type CRUD with buffers, limits, approval rules, and custom questions.
- [ ] Build booking link generator for personal, event-type, apprentice, team, invite-only, one-time, and expiring links.
- [ ] Build timezone-aware slot generation service.
- [ ] Build conflict detection service.
- [ ] Build trainee booking form and confirmation screen.
- [ ] Build approval, decline, reschedule, cancellation, complete, and no-show workflows.
- [ ] Add booking notifications and reminders.
- [ ] Add email reminder support and SMS placeholder.
- [ ] Connect completed mentor sessions to Field Apprenticeship progress.
- [ ] Add booking reports for utilization, no-show rate, completion rate, lead time, and apprenticeship impact.

### Deliverables

- CFMs can publish availability and booking event types.
- Trainees can request or confirm mentor sessions through booking links.
- Confirmed sessions create calendar events for both CFM and trainee.
- Booking history, reschedules, cancellations, and activity logs are preserved.

## Phase 5: CFM Certification Training

### Goals

- Let users complete CFM training and request certification.

### Tasks

- [x] Create CFM training modules migration.
- [x] Create CFM training progress migration.
- [x] Create CFM certification requests migration.
- [x] Create business-relevant CFM Training module seeder.
- [x] Add responsible-party accountability to CFM Training checklist items.
- [x] Add `SP` direct sponsor code to CFM Training checklist responsible-party options.
- [x] Add notified-party completion notification targets to CFM Training checklist items.
- [ ] Create CFM training and certification models.
- [x] Build CFM training module manager.
- [x] Build user CFM training page with progress cards, progress graph, interactive checklist, and pending confirmation workflow.
- [ ] Build certification request form.
- [x] Build admin CFM certification scaffold page.
- [ ] Build admin certification request review screen.
- [ ] Assign `certified-field-mentor` role after approval.
- [ ] Enforce Associate-and-above eligibility before granting `certified-field-mentor`.

### Role Eligibility Notes

- SVP rank and above usually maps to the `agency-owner` role. SVP is the first Field Leader rank.
- Senior Associate (SA) rank usually maps to the `team-leader` role.
- Associate rank and above may become CFM after successful CFM Training and approval.
- Trainer role may be granted after FAP completion and once CFM Training has started.

### Deliverables

- Users can complete CFM training.
- Users can request CFM certification.
- Authorized reviewers can approve or reject requests.

## Phase 6: Training Center

### Goals

- Centralize training content and track user progress.

### Tasks

- [x] Create training categories migration.
- [x] Create training modules migration.
- [x] Create training lessons migration.
- [x] Create training progress migration.
- [ ] Create training models.
- [x] Build training catalog scaffold page.
- [ ] Build module detail page.
- [ ] Build lesson viewer.
- [x] Build admin training manager scaffold page.
- [x] Add training progress to dashboard scaffold.

### Deliverables

- Users can browse and complete training.
- Trainers can manage training content.
- Training progress is tracked.

## Phase 7: Assessments

### Goals

- Add assessment and quiz functionality for training validation.

### Tasks

- [x] Create assessments migration.
- [x] Create questions migration.
- [x] Create answers migration.
- [x] Create assessment attempts migration.
- [ ] Create assessment, question, answer, and attempt models.
- [x] Build assessments scaffold page.
- [ ] Build assessment builder.
- [ ] Build user assessment-taking flow.
- [ ] Build assessment result screen.
- [x] Store score, pass/fail status, and completion dates in schema.

### Deliverables

- Trainers can create assessments.
- Users can take assessments.
- Assessment attempts are stored.

## Phase 8: Rank Advancement

### Goals

- Track user progress toward rank advancement.

### Tasks

- [x] Create rank requirements migration.
- [x] Create user rank progress migration.
- [ ] Create rank requirement and user rank progress models.
- [x] Build admin ranks scaffold page.
- [x] Build user rank advancement tracker scaffold page.
- [x] Show current and next-rank Greatway compensation (Total FYO, compensation rate, PPB) on the rank advancement tracker.
- [x] Treat SVP as the agency-owner / first Field Leader rank for CFM management and related access.
- [x] Add next-rank progress card to dashboard scaffold.
- [ ] Add leadership view for team rank progress.
- [ ] Support manual rank approval where required.

### Deliverables

- Users can view progress toward next rank, including Greatway compensation rates.
- Leaders can review rank progress.
- Admins can configure rank requirements.
- SVP is the agency-owner rank; Managing Broker is a Producer rank and does not grant agency-leader access by rank alone.

## Phase 9: Resources, Events, Announcements, And Recognition

### Goals

- Add supporting engagement and communication tools.

### Tasks

- [x] Create resources migration.
- [x] Create events migration.
- [x] Create announcements migration.
- [x] Create badges migration.
- [ ] Create resource, event, announcement, and badge models.
- [x] Build Resource Library scaffold page.
- [x] Build grouped Resources scaffold pages for Documents, Videos, Recorded Webinars, and Zoom Links.
- [x] Build Events Calendar scaffold page.
- [x] Build Announcements page scaffold.
- [x] Build Recognition wall scaffold page.
- [x] Move Recognition into the Communications navigation group.
- [x] Build admin managers for resources, events, announcements, and badges.
- [x] Add dashboard widgets for announcements and events.
- [ ] Add dashboard widget for recognition.

### Deliverables

- Users can access resources.
- Users can view events and announcements.
- Users can see recognition.
- Admins can manage engagement content.

## Phase 9A: Prospect Management CRM

### Goals

- Add a private CRM-style workspace for personal prospects, follow-ups, appointments, sharing, communication history, imports, and conversion tracking.
- Protect prospect records with owner-first privacy and explicit sharing permissions.

### Tasks

- [x] Create prospect CRM database schema foundation.
- [x] Create lookup tables for prospect sources, types, interests, pipeline stages, communication types, appointment types, follow-up statuses, tags, and share permissions.
- [x] Create prospect model relationship scaffolding.
- [x] Create prospect policy scaffolding for privacy-first access control.
- [x] Add Spatie permissions for prospect management, sharing, shared viewing, import, and export.
- [x] Add Prospect Management navigation route and scaffold screen.
- [x] Add Prospect Management architecture document.
- [x] Add demo prospect seeder with at least 50 prospects and realistic CRM scenarios.
- [x] Build Prospect Management dashboard modules for pipeline, follow-ups, appointments, hot prospects, communications, sharing, imports, and reports.
- [x] Add table scaffolds to Prospect Management overview panels and module shortcut pages.
- [x] Add feature tests for prospect demo data and Prospect Management dashboard modules.
- [ ] Build `ProspectDashboard` Livewire component.
- [ ] Build prospect CRUD Livewire components.
- [ ] Build prospect profile screen with notes, communications, appointments, follow-ups, tags, and sharing.
- [ ] Build pipeline Kanban board.
- [ ] Build follow-up center.
- [ ] Build appointment calendar.
- [ ] Build access manager for sharing, expiration, revoke, and access logs.
- [ ] Build CSV import wizard with duplicate detection and merge/skip/create flow.
- [ ] Build conversion workflow into client, associate recruit, referral partner, or inactive record.
- [ ] Add prospect notifications.
- [ ] Add prospect reports and privacy-scoped analytics.

### Deliverables

- Users can privately manage personal prospects.
- Users can explicitly share selected prospect records with controlled permissions.
- Prospect activity and access are auditable.
- Conversion workflows connect future prospects to onboarding, licensing, CFM assignment, FAP, and client follow-up.

## Phase 10: Notifications And Activity

### Goals

- Notify users about important portal activity.

### Tasks

- [x] Implement Laravel database notifications.
- [ ] Notify users on mentor assignment.
- [ ] Notify users on apprenticeship approval.
- [ ] Notify users on CFM certification status changes.
- [ ] Notify users on assessment results.
- [ ] Notify users on new announcements.
- [ ] Notify users on upcoming events.
- [x] Add notification icon/link to topbar.
- [x] Add Notifications sidebar menu link.
- [x] Build Notifications page scaffold.
- [x] Add notification dropdown to topbar.

### Deliverables

- In-app notification system.
- Notification topbar experience.

## Phase 11: Admin Settings And Reporting

### Goals

- Give administrators the tools needed to manage the system and inspect progress.

### Tasks

- [x] Build Admin Settings scaffold page.
- [x] Build role-based Admin Management scaffold pages.
- [x] Build generic Admin Management CRUD for important setup tables.
- [x] Add Admin Management sidebar group with users, roles, ranks, teams, checklists hub, training, CFM certification, setup tables, and settings.
- [ ] Add user filters by role, rank, team, and status.
- [ ] Add onboarding progress report.
- [ ] Add licensing progress report.
- [ ] Add apprenticeship progress report.
- [ ] Add CFM mentor activity report.
- [ ] Add training progress report.
- [ ] Add rank progress report.

### Deliverables

- Admins can manage portal settings.
- Leaders can review operational progress.

## Phase 12: Testing, Hardening, And Launch Readiness

### Goals

- Prepare the application for reliable use.

### Tasks

- [x] Add feature tests for auth and permissions.
- [x] Add feature tests for invitation registration business rules.
- [x] Add feature tests for role-based Admin Management access.
- [x] Add feature tests for login tracking and soft-delete behavior.
- [x] Add feature tests for admin user management CRUD.
- [x] Add feature tests for Admin Management CRUD.
- [x] Add feature tests for topbar navigation, search scaffold, and sidebar destination access.
- [x] Add feature tests for onboarding, licensing, Field Apprenticeship, and CFM Training tracker confirmation workflows.
- [ ] Add tests for mentor assignment, CFM certification requests, training, assessments, and rank advancement.
- [ ] Add policies for sensitive models.
- [ ] Add policy tests for sponsorship hierarchy visibility and global information exceptions.
- [ ] Add policy tests for agency-owner-only CFM assignment.
- [x] Add validation rules for registration invitation, sponsor confirmation, GW Associate ID, and profile updates.
- [ ] Add form request classes for all major write workflows.
- [ ] Add file upload validation.
- [ ] Add activity logging in a future-safe way.
- [x] Add soft deletes to users and portal business tables.
- [ ] Review database indexes.
- [x] Review mobile layouts for auth pages.
- [ ] Run Laravel Pint.
- [ ] Prepare production environment notes.

### Deliverables

- Tested core workflows.
- Hardened authorization.
- Launch-ready first version.

## Suggested Build Order

1. Foundation and authentication.
2. Roles, permissions, users, ranks, and teams.
3. Portal layout and dashboard.
4. Onboarding and licensing.
5. CFM assignment and apprenticeship.
6. CFM certification.
7. Training and assessments.
8. Rank advancement.
9. Resources, events, announcements, recognition.
10. Notifications, reporting, testing, and launch hardening.

## Future Enhancements

- Email notifications.
- SMS notifications.
- Event registration and attendance.
- Resource role targeting.
- Automated badge assignment.
- Automated rank advancement checks.
- Audit logs.
- CSV import and export.
- Advanced analytics.
- Team performance dashboards.
- Mobile app or PWA support.
- CRM integrations.
- Carrier or licensing integrations.
