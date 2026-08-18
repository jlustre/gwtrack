# Prospect Management Module

## Purpose

Prospect Management is the private CRM layer for GWTrack. It lets associates manage personal prospects, follow-ups, appointments, communication history, notes, tags, files, referrals, sharing, and conversions while preserving strict data privacy.

## Privacy Rule

- Prospect records are private by default.
- Prospect owners can manage their own records.
- Shared users can only access explicitly shared prospects.
- Shared access must be active, unrevoked, and unexpired.
- Revoked or expired access immediately denies visibility.
- Super Admin and Admin can access all records for platform operations.
- Team leaders, trainers, CFMs, and agency owners do not automatically see prospect details unless access is shared or a later agency policy explicitly grants it.

## Implemented Foundation

- Database migration for prospect CRM tables.
- Lookup seeders for sources, types, interests, pipeline stages, communication types, appointment types, follow-up statuses, tags, and share permissions.
- Demo seeder with 50 prospects, follow-ups, appointments, communications, notes, sharing records, conversions, and import history.
- Eloquent model relationship scaffolding.
- Laravel policy scaffolding for prospect privacy and collaboration permissions.
- Spatie permissions for managing, sharing, importing, exporting, and viewing shared prospects.
- Prospect Management route and dashboard module screen structure.

## Implemented Dashboard Modules

- Prospect stat cards.
- Pipeline summary.
- Follow-up center.
- Hot prospects.
- Appointment calendar.
- Reports snapshot.
- Communication timeline.
- Recently contacted prospects.
- Shared With Me.
- Shared By Me.
- Import and duplicate summary.
- Table scaffolds for the overview panels and module shortcut pages.

## Core Tables

- `prospects`
- `prospect_types`
- `prospect_type_prospect`
- `prospect_interests`
- `prospect_interest_prospect`
- `prospect_sources`
- `prospect_tags`
- `prospect_tag_pivot`
- `pipeline_stages`
- `communication_types`
- `appointment_types`
- `followup_statuses`
- `prospect_notes`
- `prospect_communications`
- `prospect_appointments`
- `prospect_followups`
- `prospect_files`
- `prospect_shares`
- `prospect_share_permissions`
- `prospect_access_logs`
- `prospect_conversions`
- `prospect_imports`

## Policy Classes

- `ProspectPolicy`
- `ProspectNotePolicy`
- `ProspectSharePolicy`
- `CommunicationLogPolicy`
- `AppointmentPolicy`
- `FollowUpPolicy`

## Planned Livewire Components

Dashboard:

- `ProspectDashboard`
- `ProspectStatsCards`
- `FollowUpsDueToday`
- `OverdueFollowUps`
- `HotProspects`
- `UpcomingAppointments`
- `PipelineSummary`

CRUD:

- `ProspectIndex`
- `ProspectCreate`
- `ProspectEdit`
- `ProspectShow`
- `ProspectDeleteModal`
- `ProspectArchiveModal`

Pipeline:

- `ProspectPipelineBoard`
- `ProspectKanbanCard`
- `ProspectStageManager`

Notes and Communication:

- `ProspectNotesPanel`
- `ProspectAddNoteModal`
- `ProspectCommunicationTimeline`
- `ProspectLogCommunicationModal`

Appointments and Follow-Ups:

- `ProspectAppointmentCalendar`
- `ProspectAppointmentCreate`
- `ProspectFollowUpList`
- `ProspectFollowUpCreate`
- `ProspectReminderPanel`

Sharing:

- `ProspectShareModal`
- `ProspectAccessManager`
- `SharedWithMeProspects`
- `SharedProspectShow`
- `ProspectAccessLogViewer`
- `RevokeProspectAccessModal`

Imports and Exports:

- `ProspectImportWizard`
- `ProspectExportTool`
- `ProspectDuplicateChecker`

## Route Structure

- `/team/prospects`
- `/team/prospects/create`
- `/team/prospects/pipeline`
- `/team/prospects/follow-ups`
- `/team/prospects/appointments`
- `/team/prospects/shared-with-me`
- `/team/prospects/shared-by-me`
- `/team/prospects/access-manager`
- `/team/prospects/import`
- `/team/prospects/settings`

## Sharing Workflow

1. Prospect owner selects one or more prospects.
2. Owner selects a collaborator and permission level.
3. Owner optionally sets an expiration date.
4. System creates `prospect_shares` record.
5. System records `access_granted` in `prospect_access_logs`.
6. Shared user can only perform actions allowed by `prospect_share_permissions`.
7. Owner can revoke access at any time.
8. System records `access_revoked` in `prospect_access_logs`.

## Import Workflow

1. User uploads CSV.
2. System previews mapped fields.
3. System detects duplicates by email and phone.
4. User chooses merge, skip, or create new.
5. Imported prospects are assigned only to the logged-in user.

## Conversion Workflow

Prospects can convert into:

- Client
- Associate recruit
- Referral partner
- Inactive record

Associate conversion should later connect to:

- Invitation registration
- Onboarding
- CFM assignment
- Licensing tracker
- Field Apprenticeship Program

Client conversion should later support:

- Policy/application reference fields
- Client follow-up reminders
- Client status reporting
