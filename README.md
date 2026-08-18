# GWTrack.com Laravel Scaffolding

Private TALL stack portal for a Greatway Financial Group team.

Target path requested: `C:\laragon2\www\GWTrack`

Note: the scaffold was prepared here because write access to `C:\laragon2\www` was not granted in this session.

## 1. Installation Commands

Laravel 13 is the current target major version for this scaffold and requires PHP 8.3+.

```bash
cd C:\laragon2\www
composer create-project laravel/laravel:^13.0 GWTrack
cd GWTrack

cp .env.example .env
php artisan key:generate
```

Configure MySQL in `.env`:

```env
APP_NAME=GWTrack
APP_URL=http://gwtrack.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gwtrack
DB_USERNAME=root
DB_PASSWORD=
```

Breeze with Blade:

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install
```

Livewire:

```bash
composer require livewire/livewire
```

Spatie Permission:

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

Build and migrate:

```bash
npm run build
php artisan migrate --seed
php artisan storage:link
```

Local development:

```bash
composer run dev
```

## 2. Folder Structure

```text
app/
  Enums/
    CfmCertificationStatus.php
    LicensingStatus.php
    ProgressStatus.php
  Http/
    Middleware/
      EnsureUserIsActive.php
  Livewire/
    Dashboard/
      MainDashboard.php
    Onboarding/
      MyChecklist.php
    Licensing/
      LicensingTracker.php
    Apprenticeship/
      MyProgram.php
      ApprenticeRoster.php
      MentorNotes.php
    Training/
      TrainingCenter.php
      ModuleShow.php
      LessonPlayer.php
    Assessments/
      AssessmentIndex.php
      TakeAssessment.php
    RankAdvancement/
      RankTracker.php
    Team/
      TeamHierarchy.php
    Resources/
      ResourceLibrary.php
    Events/
      EventCalendar.php
    Recognition/
      RecognitionWall.php
    Admin/
      Users/UserIndex.php
      Ranks/RankIndex.php
      Training/TrainingModuleIndex.php
      Settings/AdminSettings.php
  Models/
database/
  migrations/
  seeders/
routes/
  web.php
resources/
  views/
    layouts/
      app.blade.php
      navigation.blade.php
    livewire/
```

## 3. Database Schema Plan

Core identity:

- `users`: Breeze auth user plus rank, team, sponsor, mentor, active flags.
- `registration_invitations`: tracked invitation codes, sponsor, invited email, usage, expiry, email delivery, acceptance, and revocation state.
- `email_templates`: database-managed templates for invitation and future portal emails.
- `profiles`: extended user data, phone, province/state, license number, recruit date.
- `ranks`: FA through EP.
- `teams`: hierarchical team ownership and leadership.

Tracking:

- `licensing_steps`, `user_licensing_progress`
- `onboarding_steps`, `user_onboarding_progress`
- `rank_requirements`, `user_rank_progress`

Training and assessment:

- `training_categories`, `training_modules`, `training_lessons`, `training_progress`
- `assessments`, `questions`, `answers`, `assessment_attempts`

Content and engagement:

- `resources`, `events`, `announcements`, `badges`

Certified Field Mentor:

- `mentor_assignments`
- `apprenticeship_programs`, `apprenticeship_steps`, `user_apprenticeship_progress`
- `mentor_notes`
- `cfm_training_modules`, `cfm_training_progress`
- `cfm_certification_requests`

## 4. Models And Relationships

Primary relationships:

- `User hasOne Profile`
- `User belongsTo Rank`
- `User belongsTo Team`
- `User belongsTo sponsor User`
- `User belongsTo mentor User`
- `User hasMany apprentices through mentor_assignments`
- `Team belongsTo owner User`
- `Team belongsTo leader User`
- `Team belongsTo parent Team`
- `TrainingModule belongsTo TrainingCategory`
- `TrainingModule hasMany TrainingLesson`
- `TrainingProgress belongsTo User, TrainingLesson`
- `Assessment hasMany Question`
- `Question hasMany Answer`
- `AssessmentAttempt belongsTo User, Assessment`
- `RankRequirement belongsTo Rank`
- `MentorAssignment belongsTo apprentice User`
- `MentorAssignment belongsTo mentor User`
- `CfmCertificationRequest belongsTo user`

## 5. Roles And Permissions

Invitation and registration rules:

- GWTrack is invitation-only; public registration without a valid invitation code is blocked.
- Each invitation link includes a tracked registration code and sponsor.
- New members inherit `sponsor_id` from the member tied to the invitation code.
- Registration requires GW Associate ID, active Greatway Financial Group associate confirmation, and sponsor confirmation.
- If the displayed sponsor is incorrect, the invited person should discontinue registration and request the correct invitation link.
- The system prevents duplicate active invitations for the same email and prevents invitations to already registered emails.
- Successful registration deactivates the invitation code.
- Accepted invitations are hidden from the sponsor's Recent Invitations panel.
- Sponsors may delete active invitations, which revokes the code and allows a new invitation to that email if the person has not registered.
- Sponsors can preview and edit invitation emails; the message must keep the registration link and reminds the sender to add the recipient name.
- Invitation emails use the logged-in sponsor's name and email identity.

Roles:

- super-admin
- admin
- agency-owner
- team-leader
- certified-field-mentor
- trainer
- associate
- member
- new-recruit

Rank and role distinction:

- Ranks are Greatway Financial Group advancement levels.
- Roles are GWTrack access and permission groups.
- ED and above usually carry the `agency-owner` role.
- SM usually carries the `team-leader` role.
- CFM can be granted to SFA and above after successful CFM Training completion and approval.
- Trainer can be granted after successful Field Apprenticeship Program completion and once CFM Training has started.

Hierarchy visibility:

- Sponsorship hierarchy is the default visibility boundary for non-global information.
- A member can see themselves, direct recruits, and all downline recruits under those direct recruits.
- Information tagged global may be visible outside hierarchy boundaries when permissions allow it.
- Roles grant actions and tools, but do not automatically bypass hierarchy visibility.
- Only agency owners may assign a CFM to a new member under their sponsorship hierarchy.
- Agency owners should prioritize their own CFM pool before considering CFMs from other agencies.
- Agency owners may see CFM profile information from other agencies for assignment planning only.

Checklist accountability:

- Checklist items include responsible parties for confirmation and follow-up.
- Responsible party codes are Self, SP, AO, TL, CFM, and TR.
- SP means the member's direct sponsor from the sponsorship hierarchy.
- Responsible parties may be combined, for example `Self, CFM` or `AO, TR`.
- Checklist items may include notified parties who receive notifications or email when an item is completed.
- Notified party codes are SP, AO, TL, CFM, and TR.
- Notified parties are completion-alert recipients for the checklist item.
- Onboarding checklist items move to pending confirmation when checked and become completed only after a notified party confirms them.
- Notified parties can add review comments and either confirm or reject pending onboarding items.
- This applies to onboarding, licensing, Field Apprenticeship Program, and CFM Training checklists.

Permissions:

- view dashboard
- manage users
- manage roles
- manage ranks
- manage training
- manage assessments
- manage resources
- manage onboarding
- manage licensing
- manage rank advancement
- manage events
- manage announcements
- assign mentors
- view apprentices
- update apprentice progress
- approve apprenticeship
- manage CFM certification
- view team
- manage team

Recommended role matrix:

- super-admin: all permissions
- admin: user management and core setup table management, excluding role ownership unless granted
- agency-owner: all operational permissions except low-level system ownership if added later
- team-leader: dashboard, team, rank, onboarding, licensing, apprentices, events, announcements
- certified-field-mentor: dashboard, apprentices, update apprentice progress, resources, training
- trainer: dashboard, training, assessments, resources
- associate: dashboard, own onboarding/licensing/training/assessments/rank/resources/events
- member: dashboard, team, personal progress, and member resources
- new-recruit: dashboard, onboarding, licensing, field apprenticeship, training basics, resources

## 6. Route Structure

Use authenticated routes with permission middleware. Admin areas should be role or permission gated.

```php
Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::view('/dashboard', 'dashboard')->middleware('permission:view dashboard')->name('dashboard');

    Route::view('/onboarding', 'onboarding.index')->name('onboarding.index');
    Route::view('/licensing', 'licensing.index')->name('licensing.index');
    Route::view('/tasks', 'tasks.index')->name('tasks.index');
    Route::view('/field-apprenticeship', 'apprenticeship.index')->name('apprenticeship.index');
    Route::view('/training', 'training.index')->name('training.index');
    Route::view('/assessments', 'assessments.index')->name('assessments.index');
    Route::view('/rank-advancement', 'rank-advancement.index')->name('rank-advancement.index');
    Route::view('/team', 'team.index')->middleware('permission:view team')->name('team.index');
    Route::view('/team/directs', 'team.directs')->middleware('permission:view team')->name('team.directs');
    Route::view('/team/trainees', 'team.trainees')->middleware('permission:view team')->name('team.trainees');
    Route::view('/team/cfms', 'team.cfms')->middleware('permission:view team')->name('team.cfms');
    Route::view('/team/downlines', 'team.downlines')->middleware('permission:view team')->name('team.downlines');
    Route::view('/team/prospects', 'team.prospects')->middleware('permission:view team')->name('team.prospects');
    Route::view('/resources/documents', 'resources.documents')->name('resources.documents');
    Route::view('/resources/videos', 'resources.videos')->name('resources.videos');
    Route::view('/resources/recorded-webinars', 'resources.recorded-webinars')->name('resources.recorded-webinars');
    Route::view('/resources/zoom-links', 'resources.zoom-links')->name('resources.zoom-links');
    Route::view('/events', 'events.index')->name('events.index');
    Route::view('/calendar', 'events.index')->name('calendar.index');
    Route::view('/recognition', 'recognition.index')->name('recognition.index');
    Route::view('/announcements', 'announcements.index')->name('announcements.index');
    Route::view('/notifications', 'notifications.index')->name('notifications.index');

    Route::prefix('admin')->name('admin.')->middleware('role:super-admin|admin|agency-owner|team-leader|certified-field-mentor|trainer')->group(function () {
        Route::view('/settings', 'admin.settings')->name('settings');
        Route::view('/users', 'admin.users.index')->middleware('permission:manage users')->name('users.index');
        Route::view('/roles', 'admin.roles.index')->middleware('permission:manage roles')->name('roles.index');
        Route::view('/ranks', 'admin.ranks.index')->middleware('permission:manage ranks')->name('ranks.index');
        Route::view('/training', 'admin.training.index')->middleware('permission:manage training')->name('training.index');
        Route::view('/cfm-certification', 'admin.cfm.index')->middleware('permission:manage CFM certification')->name('cfm.index');
    });
});
```

## 7. Middleware Setup

Register aliases in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        'active' => \App\Http\Middleware\EnsureUserIsActive::class,
    ]);
})
```

## 8. Livewire Component List

Dashboard:

- `Dashboard\MainDashboard`
- `Dashboard\ProgressSummaryCards`
- `Dashboard\AnnouncementsPanel`
- `Dashboard\UpcomingEvents`

Onboarding:

- `Onboarding\MyChecklist`
- `Onboarding\AdminStepManager`

Licensing:

- `Licensing\LicensingTracker`
- `Licensing\AdminLicensingStepManager`

Field Apprenticeship and CFM:

- `Apprenticeship\MyProgram`
- `Apprenticeship\ApprenticeRoster`
- `Apprenticeship\MentorNotes`
- `Apprenticeship\MentorSessions`
- `Apprenticeship\CfmCertificationRequestForm`
- `Apprenticeship\AdminCfmApprovalQueue`

Training:

- `Training\TrainingCenter`
- `Training\CategoryModules`
- `Training\ModuleShow`
- `Training\LessonPlayer`
- `Training\AdminModuleManager`

Assessments:

- `Assessments\AssessmentIndex`
- `Assessments\TakeAssessment`
- `Assessments\AttemptResults`
- `Assessments\AdminAssessmentBuilder`

Rank advancement:

- `RankAdvancement\RankTracker`
- `RankAdvancement\RequirementProgress`
- `RankAdvancement\AdminRequirementManager`

Team:

- `Team\TeamHierarchy`
- `Team\TeamMemberProfile`
- `Team\MentorAssignmentManager`

Resources, events, recognition:

- `Resources\ResourceLibrary`
- `Events\EventCalendar`
- `Recognition\RecognitionWall`
- `Announcements\AnnouncementFeed`

Admin:

- `Admin\Users\UserIndex`
- `Admin\Users\UserForm`
- `Admin\Roles\RolePermissionMatrix`
- `Admin\Settings\AdminSettings`

## 9. Dashboard Layout Plan

Design language:

- Navy primary: `#0B1F3A`
- Gold accent: `#C8A24A`
- White surface: `#FFFFFF`
- Soft gray background: `#F5F7FA`
- Deep text: `#172033`

Shell:

- Grouped left sidebar navigation with role-aware dropdown sections.
- Topbar with responsive search, notification bell dropdown, and profile/avatar menu.
- Mobile drawer navigation.
- Main content uses full-width bands, compact cards, and clear progress states.

Dashboard widgets:

- Welcome and current rank badge.
- Onboarding progress card.
- Licensing progress card.
- Apprenticeship progress card.
- Training completion card.
- Next rank requirements.
- Assigned CFM or apprentice roster.
- Upcoming events.
- Announcements.
- Recognition highlights.

Navigation groups:

- Dashboard
- Trackers: My Onboarding, Licensing Tracker, My Tasks, Field Apprenticeship, CFM Training, Training Center, Assessments
- My Team: My Directs, My Trainees, My CFMs, All Downlines, Prospect Management
- Communications: Announcements, Events, Calendar, Notifications, Rank Advancement, Recognition
- Resources: Documents, Videos, Recorded Webinars, Zoom Links
- Admin Management: Admin Dashboard, User Management, Roles & Permissions, Ranks, Teams, Training Setup, CFM Certification, All Setup Tables, Admin Settings

## 10. Development Roadmap

Phase 1:

- Laravel 13 project install
- Breeze auth
- Livewire and Alpine stack
- Tailwind theme
- Spatie roles and permissions
- Core migrations and seeders
- Authenticated dashboard shell

Phase 2:

- Profiles
- Teams and hierarchy
- Rank model and rank display
- Admin user management
- Mentor assignment foundation

Phase 3:

- Onboarding tracker
- Licensing tracker
- Field Apprenticeship Program
- Mentor notes and sessions

Phase 4:

- Training center
- Assessments
- Progress analytics
- Rank advancement tracker

Phase 5:

- Resources
- Events
- Announcements
- Recognition and badges
- Notifications

Phase 6:

- CFM certification workflow
- Approval queues
- Reporting dashboards
- Audit logs and operational hardening
