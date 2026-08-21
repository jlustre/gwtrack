# GWTrack Business Requirements

This document defines the business requirements for GWTrack.com, a private Greatway Financial Group team tracking portal.

## Project Summary

GWTrack.com will help a Greatway Financial Group team track new recruits, associates, onboarding, licensing, mentorship, training, assessments, resources, motivation, recognition, events, notifications, and rank advancement.

The system should give each user a clear view of what they need to do next, while giving mentors and leaders visibility into team progress.

## Business Objectives

- Improve visibility into new recruit and associate progress.
- Standardize onboarding and licensing tracking.
- Ensure every New Recruit or Associate has a Certified Field Mentor when required.
- Support the Field Apprenticeship Program with progress tracking and mentor notes.
- Centralize training, assessments, events, resources, announcements, and recognition.
- Track progress toward rank advancement from Training Associate through National Executive Vice Chairman.
- Give leadership useful reporting and team visibility.
- Create a scalable foundation for future automation, notifications, and analytics.

## Target Users

- Super Admins who manage the entire platform.
- Admins who manage users and core setup tables.
- Agency Owners who oversee agency-level operations.
- Team Leaders who manage and support their teams.
- Certified Field Mentors who guide apprentices.
- Trainers who manage training and assessments.
- Associates who track their growth and rank progress.
- New Recruits who complete onboarding, licensing, training, and apprenticeship.

## Functional Requirements

### Authentication And Profiles

- Users must only be able to register through an invitation link from a current GWTrack member or be created by an admin.
- Invitation links must include a tracked registration code.
- New user registrations must store the sponsoring member as `sponsor_id`.
- New user registrations must assign the default `member` role.
- New user registrations must assign the default `TA` rank.
- New user registrations must place the user on the same team as their sponsor.
- Only users with `super-admin`, `admin`, or `agency-owner` roles may change a user's role, rank, or team after registration.
- Unless information is explicitly tagged global, users may only see records inside their sponsorship hierarchy.
- Sponsorship hierarchy visibility includes direct recruits and all downline recruits under them.
- Registration must require an GW Associate ID.
- Registration must require the invited person to confirm they are an active Greatway Financial Group associate.
- Registration must display the sponsor connected to the invitation code and require the invited person to confirm that sponsor.
- If the sponsor shown on the registration form is not correct, the invited person must be instructed to discontinue registration and request the correct sponsor's invitation link.
- Users must be able to log in, log out, and manage account credentials through Laravel Breeze authentication.
- Users must have profile records containing contact details, rank, team, sponsor, mentor, and licensing information.
- Users must have an account status.

### Member Invitations

- Active GWTrack members must be able to create invitation links from their profile.
- Invitation links must be single-use by default.
- The system must prevent multiple active invitation links for the same email recipient.
- The system must prevent invitations to an email address that already belongs to a registered GWTrack user.
- Accepted invitations must be deactivated after successful registration.
- Accepted invitations must not appear in the sponsor's Recent Invitations panel.
- Sponsors must be able to delete an active invitation link, which deactivates the code.
- A deleted invitation must not block a future invitation to the same email if that person has not registered.
- Sponsors must be able to preview and edit an invitation email before sending it.
- The invitation email body must include the registration link.
- The invitation email popup must remind the sender to add the recipient name before sending.
- Invitation email templates must be stored in the database and support future template management.
- Invitation emails must send from the logged-in sponsor's name and email identity, not a generic team sender.

### Role-Based Access Control

- The system must use Spatie Laravel Permission.
- The system must support roles and permissions.
- Roles are GWTrack application access groups, while ranks are Greatway Financial Group advancement levels.
- Admin users must be able to assign roles.
- Admin Management must support CRUD workflows for important setup tables.
- Features must be protected by permissions.
- Navigation should only show items appropriate to the authenticated user.
- Senior Vice President (SVP) and above usually qualify for the `agency-owner` role. SVP is the first Field Leader rank.
- Senior Associate (SA) rank usually qualifies for the `team-leader` role.
- Associate rank and above may qualify for the `certified-field-mentor` role after successful CFM Training completion and approval.
- Members who completed the Field Apprenticeship Program and started CFM Training may qualify for the `trainer` role.
- Roles grant actions and tools, but do not automatically bypass sponsorship hierarchy visibility unless an explicit exception exists.

### Hierarchy Visibility

- Non-global records must be scoped to the viewer's sponsorship hierarchy by default.
- Global records may be visible outside the viewer's hierarchy when the content type and permissions allow it.
- Members may see their own information, direct recruits, and all downlines under those direct recruits.
- Agency owners may see CFM profile information from other agencies for mentor assignment planning.
- Cross-agency CFM visibility must be limited to CFM profile details needed for assignment decisions.
- An agency owner's own CFM pool has first priority for mentor assignment.
- Only agency owners may assign a CFM to a new member under their sponsorship hierarchy.

### Dashboard

- Users must see a personalized dashboard.
- Dashboard should show onboarding progress, licensing progress, apprenticeship progress, training progress, current rank, next rank progress, assigned CFM, upcoming events, announcements, and recognition.
- Leaders should see team progress summaries where permitted.

### Team Hierarchy

- The system must support teams.
- Teams may have owners and parent teams.
- Users may belong to a team.
- Leaders must be able to view team members where permissions allow.
- Team hierarchy should support a visual team view.
- Team navigation should include My Directs, My Trainees, My CFMs, All Downlines, and Prospect Management.

### Onboarding Checklist

- Admin users must be able to manage onboarding steps.
- Onboarding steps may be global or country-specific.
- Onboarding steps must identify responsible parties for confirmation and follow-up.
- Users must be able to view onboarding steps.
- Users should only see country-specific onboarding steps when their member profile country matches the step country.
- User progress must be tracked per step.
- When a user checks an onboarding item, the item moves to pending confirmation.
- A notified party must confirm the item before it becomes completed.
- A notified party may add confirmation comments and either confirm or reject the item.
- Steps must support required or optional status.
- Completion dates must be stored.

### Licensing Tracker

- Admin users must be able to manage licensing steps.
- Users must be able to view licensing requirements.
- Licensing progress must be tracked per user.
- Licensing steps must identify responsible parties for confirmation and follow-up.
- Licensing statuses should include not started, in progress, submitted, approved, and completed.
- Licensing notes must be supported.

### Certified Field Mentor Assignment

- New Recruits and Associates should be assigned to a Certified Field Mentor.
- Only agency owners must be able to assign mentors to new members under their sponsorship hierarchy.
- Agency owners should prioritize their own CFM pool before selecting a CFM from another agency.
- A CFM may only be assigned when licensed in the associate's province or state; assignment screens filter CFMs by the associate's profile jurisdiction and the server enforces the same rule.
- CFMs must be able to view assigned apprentices.
- Mentor assignments must support active, completed, and replaced statuses.

### Field Apprenticeship Program

- Admin users must be able to manage apprenticeship programs and steps.
- Apprenticeship steps must identify responsible parties for confirmation and follow-up.
- Users must be able to view apprenticeship progress.
- CFMs must be able to update apprentice progress.
- Authorized users must be able to approve apprenticeship completion.
- Mentor notes must be available for apprentice tracking.

### CFM Certification Training

- Admin users must be able to manage CFM training modules.
- CFM training modules must identify responsible parties for confirmation and follow-up.
- Users must be able to complete CFM training.
- Users must be able to request CFM certification after completing required training.
- Authorized users must be able to approve or reject CFM certification requests.
- Approved users at Associate rank or above may receive the Certified Field Mentor role.

### Training Center

- Admins or Trainers must be able to manage training categories, modules, and lessons.
- Users must be able to browse published training.
- Training progress must be tracked per user.
- Training modules may be associated with onboarding, apprenticeship, CFM certification, or rank advancement.

### Assessments

- Admins or Trainers must be able to create assessments.
- Assessments must support questions and answers.
- Users must be able to take assessments.
- Assessment attempts must track score, pass/fail status, start date, and completion date.
- Passing score must be configurable.

### Rank Advancement

- Admin users must be able to manage ranks and rank requirements.
- Ranks follow the Greatway Producer track (TA → AVP) and Builder / Field Leader track (SVP → NEVC).
- The rank advancement tracker must show current and next-rank compensation (Total FYO, compensation rate, and Personal Production Bonus where applicable).
- SVP and above are Field Leaders. Rank-based agency-leader access (including CFM management) starts at SVP.
- Users must be able to view progress toward the next rank.
- Leaders must be able to view team rank progress.
- Rank progress must support configurable requirements aligned to Greatway income, NBA, and leg qualifications.
- Rank advancement may require manual approval.
- Canonical promotion and compensation figures live in `docs/greatway-promotion-compensation-guidelines.md` and `config/greatway-compensation.php`.

### Monetization

- Super admins and admins can enable or disable rank-based monthly subscriptions from Admin Settings.
- Default monthly prices: SA $9.95, MB $14.95, SMB $19.95, AVP and SVP $24.95, EVP $29.95, EVC $34.95, SEVC $39.95, NEVC $49.95.
- TA and ASC remain free. Unlicensed associates are free at every rank.
- After a rank change, the new price begins on the next subscription anniversary month.
- Associates can choose monthly auto-pay or an annual invoice equal to twelve months of the rank price.
- Licensed associates pay issued invoices from My Billing. Super admins, admins, and agency owners process outstanding invoices from Subscription Payments.

### Admin Checklists Hub

- Authorized admins must manage checklists from a hub at `/admin/checklists`.
- The hub must open three administration pages: Checklist Types, Checklist Instructions, and Checklist Items.
- Each of those pages must provide a Back to Checklists action that returns to the hub.
- Checklist Types define categories such as onboarding, licensing, contracting, FAP, CFM training, and mentoring.
- Checklist Instructions hold rich-text help, manuals, and reference URLs shown while members complete a checklist.
- Checklist Items are the steps members complete across those types.

### Resource Library

- Authorized users must be able to manage resources.
- Resources may be files, links, videos, documents, or other content types.
- Users must be able to browse published resources.
- Resource navigation should group content into Documents, Videos, Recorded Webinars, and Zoom Links.

### Events Calendar

- Authorized users must be able to manage events.
- Users must be able to view upcoming events.
- Events must include title, description, location, start time, and optional end time.

### Announcements

- Authorized users must be able to manage announcements.
- Published announcements must appear on the dashboard and announcements page.
- Announcements may have publish and expiration dates.

### Recognition

- Admin users must be able to manage badges.
- Users may receive badges for progress, milestones, rank advancement, training, licensing, or leadership.
- Recognition should be visible in a recognition area.

### Notifications

- Users should receive in-app notifications for important portal activity.
- Notification events may include mentor assignment, apprenticeship approval, CFM request updates, assessment results, new announcements, events, and rank advancement.
- The topbar should include a notification bell dropdown with unread count and recent notifications.

### Tasks And Search

- Users should have a My Tasks area for assigned action items, follow-ups, reminders, and portal tasks.
- The topbar should include a responsive global search entry point for members, training, resources, announcements, and events.

### Checklist Accountability

- Checklist items must include responsible parties who confirm, follow up, or help ensure completion.
- Responsible party codes are Self, SP, AO, TL, CFM, and TR.
- SP means the member's direct sponsor from the sponsorship hierarchy.
- A checklist item may have one responsible party or a combination of responsible parties.
- Checklist items may include notified parties who receive in-app notifications or email when the item is completed.
- Notified party codes are SP, AO, TL, CFM, and TR.
- Notified parties are the people to alert whenever the checklist item is completed.
- User-submitted checklist items may require notified-party confirmation before completion is final.
- Checklist accountability applies to onboarding, licensing, Field Apprenticeship Program, and CFM Training.

## Non-Functional Requirements

### Technology

- Framework: Laravel 13.
- Authentication: Laravel Breeze with Blade stack.
- UI: Blade, Livewire, Alpine.js, Tailwind CSS.
- Authorization: Spatie Laravel Permission.
- Database: MySQL.

### Design

- The interface must feel like a premium financial services admin portal.
- The color palette should use navy, gold, white, and soft gray.
- The layout must include sidebar navigation and a topbar.
- Sidebar navigation must be grouped into Trackers, My Team, Communications, Resources, and Admin Management.
- The topbar must include responsive search, a notification bell dropdown, and an avatar/profile dropdown.
- Dashboard cards, progress bars, rank badges, checklist cards, training cards, and hierarchy views must be responsive.
- The interface must be mobile-first.

### Security

- All core pages must require authentication.
- Administrative actions must require permissions.
- Users should only see records they are permitted to access.
- File uploads must be validated.
- Sensitive operations should be auditable in a future phase.

### Data Integrity

- Progress records should be tied to users and source records.
- Deleting core configuration records should not accidentally erase historical user progress.
- Historical progress should be preserved wherever possible.
- Seed data should provide baseline roles, permissions, and ranks.

### Reporting

- The system should eventually support reports for onboarding, licensing, training, apprenticeship, rank progress, mentor activity, and team progress.
- Initial implementation can use dashboard summaries and filtered admin lists.

## Initial Database Scope

- users
- registration_invitations
- profiles
- ranks
- teams
- licensing_steps
- user_licensing_progress
- onboarding_steps
- user_onboarding_progress
- training_categories
- training_modules
- training_lessons
- training_progress
- assessments
- questions
- answers
- assessment_attempts
- rank_requirements
- user_rank_progress
- resources
- events
- announcements
- badges
- mentor_assignments
- apprenticeship_programs
- apprenticeship_steps
- user_apprenticeship_progress
- mentor_notes
- cfm_training_modules
- cfm_training_progress
- cfm_certification_requests

## Success Criteria

- New users can log in and see the correct dashboard.
- Roles and permissions control navigation and access.
- Sidebar links resolve to accessible module or placeholder pages.
- New Recruits and Associates can be assigned to CFMs.
- Users can track onboarding, licensing, apprenticeship, training, assessments, and rank progress.
- CFMs can manage assigned apprentices.
- Leaders can view team progress.
- Admins can configure core portal data.
- The portal has a polished, mobile-friendly financial services design.

## Out Of Scope For Initial Scaffolding

- Full production deployment.
- Payment processing.
- Commission tracking.
- Carrier integrations.
- CRM integrations.
- SMS delivery.
- Advanced analytics.
- Automated rank approval.
- Multi-agency tenant isolation.
- Public marketing website.

## Open Business Questions

- Which remaining rank-advancement evidence and approval steps still need a GWTrack workflow beyond the published Greatway income, NBA, and leg qualifications?
- What exact licensing workflow applies by province or state?
- Which apprenticeship steps are mandatory?
- Who can approve CFM certification?
- Can apprentices view mentor notes?
- Should events support registration and attendance?
- Should resources be visible by role, rank, team, or all users?
- Should notifications include email in the first release?
- Should team members be allowed in multiple teams?
