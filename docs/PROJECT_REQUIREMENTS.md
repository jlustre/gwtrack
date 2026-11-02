# GWTracks Business Rules & Project Requirements

This is the living requirements file for GWTracks.com. Update it whenever business rules, access rules, workflows, or module decisions change.

## Project Purpose

GWTracks.com is a private Greatway Financial Group team portal for tracking new recruits, associates, onboarding, licensing, mentorship, training, assessments, resources, motivation, recognition, and rank advancement.

## Core Product Goals

- Give new recruits and associates a clear path from onboarding through licensing and apprenticeship.
- Give Certified Field Mentors a structured way to guide apprentices.
- Give team leaders and agency owners visibility into team progress.
- Keep training, assessments, resources, events, and announcements in one portal.
- Support rank advancement tracking from Training Associate through National Executive Vice Chairman, including Greatway FYO compensation rates. Canonical catalog: `docs/greatway-promotion-compensation-guidelines.md`.
- Use role-based access control so each user sees only what is appropriate.

## Core Roles

- super-admin
- admin
- agency-owner
- team-leader
- certified-field-mentor
- trainer
- associate
- member
- new-recruit

## Rank Path

Producer track:

- TA - Training Associate (80% Total FYO)
- ASC - Associate (100% Total FYO; 3 NBAs)
- SA - Senior Associate (110% Total FYO; $10,000 LTI)
- MB - Managing Broker (120% Total FYO; $40,000 LTI)
- SMB - Senior Managing Broker (130% Total FYO; $80,000 LTI)
- AVP - Associate Vice President (145% Total FYO; $100,000 12MI)

Builder track (Field Leaders, SVP and above):

- SVP - Senior Vice President (145% Total FYO; $60,000 12MI plus 3 MB legs or 1 SVP). Agency-owner rank.
- EVP - Executive Vice President (150% Total FYO including 5% PPB)
- EVC - Executive Vice Chairman (155% Total FYO including 10% PPB)
- SEVC - Senior Executive Vice Chairman (160% Total FYO including 15% PPB)
- NEVC - National Executive Vice Chairman (165% Total FYO including 20% PPB)

## Rank And Role Distinction

- Ranks are Greatway Financial Group advancement levels.
- Roles are GWTracks access and permission groups.
- Senior Vice President (SVP) and above usually carry the `agency-owner` role. SVP is the first Field Leader rank.
- Senior Associate usually carries the `team-leader` role.
- CFM can be granted to Associate and above after successful CFM Training completion and approval.
- Trainer can be granted after successful Field Apprenticeship Program completion and once CFM Training has started.
- Authorized admins remain responsible for final role assignment until role automation is explicitly built.

## Core Modules

- Dashboard
- User Profile Management
- Team Hierarchy
- Prospect Management
- Licensing Tracker
- Onboarding Checklist
- Admin Checklists Hub (Types, Instructions, Items)
- My Tasks
- Training Center
- Assessments
- Rank Advancement Tracker
- Resource Library
- Documents
- Videos
- Recorded Webinars
- Zoom Links
- Motivation & Recognition
- Events Calendar
- Calendar
- Notifications
- Certified Field Mentor Assignment
- Field Apprenticeship Program
- CFM Certification Training
- Admin Settings

## Global Business Rules

- GWTracks is a private portal. Users must be authenticated to access core features.
- Users must be active to access the portal.
- Role-based access control is handled through Spatie Laravel Permission.
- A user may have one or more roles.
- A user belongs to one current rank.
- Rank-based monthly subscriptions can be enabled from Admin Settings. Unlicensed associates and TA/ASC ranks stay free. A new rank's price starts on the next subscription anniversary. Associates may choose monthly auto-pay or annual billing. Issued invoices are paid from My Billing; office collection is processed from Admin Management → Subscription Payments.
- A user may belong to a team.
- A user may have a sponsor.
- A prospective member may only register through an invitation link from a current GWTracks member.
- Each invitation link must include a tracked registration code.
- A newly registered member must receive `sponsor_id` from the sponsoring member tied to the registration code.
- A newly registered member receives the default `member` role.
- A newly registered member receives the default `TA` (Training Associate) rank.
- A newly registered member is placed on the same team as their sponsor.
- Only users with `super-admin`, `admin`, or `agency-owner` roles may change a user's role, rank, or team after registration.
- Unless information is explicitly tagged global, users may only see records inside their sponsorship hierarchy.
- Sponsorship hierarchy visibility includes direct recruits and all downline recruits under them.
- Only active Greatway Financial Group associates may complete registration.
- Registration must collect the invited person's GW Associate ID.
- Registration must show the sponsor tied to the invitation code and require the invited person to confirm that sponsor before continuing.
- If the displayed sponsor is not the correct sponsor, the invited person should discontinue registration and request a new invitation link from the correct sponsor.
- A user may have an assigned Certified Field Mentor.
- A New Recruit or Associate should not move through the apprenticeship process without a CFM assignment.
- Checklist items must identify who is responsible for confirmation and follow-up.
- Responsible party codes are Self, SP, AO, TL, CFM, and TR, and may be combined.
- SP means the member's direct sponsor from the sponsorship hierarchy.
- Checklist items may identify who should be notified through notifications or email when the item is completed.
- Notified party codes are SP, AO, TL, CFM, and TR, and may be combined.
- Notified parties are completion-alert recipients for the checklist item.

## Invitation Rules

- Active members may create invitation links from their member profile.
- Invitation links are tracked through `registration_invitations`.
- Invitation links are single-use by default.
- The system must prevent a new active invitation when the same email already has an active invitation.
- The system must prevent invitations to emails that already belong to registered GWTracks users.
- A sponsor may preview and edit the invitation email before sending.
- The invitation email body must include the registration link.
- The invitation email popup must display this reminder below the message field: "The registration link must remain in the message. Add the recipient name before sending."
- Invitation email templates must be stored in the database for future management.
- Invitation emails must use the logged-in sponsor's name and email as the sender/reply-to identity.
- Successful registration must deactivate the invitation code.
- Accepted invitations must be hidden from the sponsor's Recent Invitations panel.
- Sponsors may delete active invitations, which revokes the invitation code.
- Deleted invitations may be recreated for the same email if the recipient has not registered.

## Certified Field Mentor Rules

- Every New Recruit or Associate should be assigned to a Certified Field Mentor.
- A CFM guides the apprentice through the Field Apprenticeship Program.
- A CFM can view assigned apprentices.
- A CFM can update apprentice progress when permission allows.
- A CFM can create mentor notes for assigned apprentices.
- Mentor notes may include private internal notes and apprentice-facing notes if enabled later.
- Team Leaders, Agency Owners, and Super Admins may review mentorship progress.
- Apprenticeship completion requires approval by an authorized user.
- Only agency owners may assign a CFM to a new member under their sponsorship hierarchy.
- Agency owners should prioritize their own CFM pool before assigning an outside-agency CFM.
- Agency owners may see CFM profile information from other agencies only for CFM discovery and assignment planning.

## Hierarchy Visibility Rules

- Sponsorship hierarchy is the default visibility boundary for non-global information.
- A member can see information for themselves, members they directly recruited, and all recruits underneath those members.
- A member cannot see another sponsor line's information unless the information is tagged global or an explicit exception applies.
- Roles determine permissions, but roles do not automatically override hierarchy visibility.
- Global information may be visible outside hierarchy boundaries when the content type and permission rules allow it.

## CFM Certification Rules

- Users may request CFM certification after completing required CFM training.
- CFM training modules must include responsible parties for confirmation and follow-up.
- CFM certification requests begin as pending.
- Authorized users may approve or reject CFM certification requests.
- Approved users may receive the Certified Field Mentor role.
- Rejected certification requests should include review notes.

## Onboarding Rules

- Onboarding steps are configurable by administrators.
- Each user can have individual onboarding progress records.
- Onboarding steps can be required or optional.
- Onboarding steps must include responsible parties for confirmation and follow-up.
- Checked onboarding items must move to pending confirmation until a notified party confirms them.
- Notified parties must be able to add comments and confirm or reject pending onboarding items.
- Completion dates should be tracked.
- Admins and leaders should be able to review onboarding progress for their team members.

## Licensing Rules

- Licensing steps are configurable by administrators.
- Each user can have individual licensing progress records.
- Licensing steps must include responsible parties for confirmation and follow-up.
- Licensing progress should support statuses such as not started, in progress, submitted, approved, and completed.
- Licensing notes should be available for internal tracking.
- Licensing workflows may vary by province or state in a future version.

## Training Rules

- Training content is organized by category, module, and lesson.
- Training modules may be drafted or published.
- Users should only see published training unless they have management permissions.
- Lesson completion should be tracked per user.
- Training may be tied to onboarding, apprenticeship, CFM certification, or rank advancement.

## Assessment Rules

- Assessments may be attached to training modules.
- Assessments contain questions and answers.
- Assessment attempts should store score, pass/fail state, completion date, and answer snapshot.
- Passing score is configurable per assessment.
- Trainers and admins may manage assessments.

## Rank Advancement Rules

- Rank requirements are configurable per Greatway rank.
- User progress is tracked against rank requirements.
- Rank advancement should be visible to the user and their leadership chain.
- The tracker shows current and next-rank Total FYO, compensation rate, and Personal Production Bonus.
- Rank advancement may depend on training, licensing, apprenticeship, production (LTI or 12MI), NBA/recruiting, or Field Leader leg criteria.
- SVP and above are Field Leaders and receive generational override eligibility.
- Rank-based agency-leader and CFM management access starts at SVP.
- Canonical promotion and compensation figures live in `docs/greatway-promotion-compensation-guidelines.md` and `config/greatway-compensation.php`.

## Admin Checklists Hub Rules

- Checklist administration starts at `/admin/checklists`.
- The hub opens Checklist Types, Checklist Instructions, and Checklist Items.
- Each of those pages includes a Back to Checklists action that returns to the hub.

## Team Hierarchy Rules

- Teams may have owners and leaders.
- Teams may be nested under parent teams.
- Team leaders can view their team when permission allows.
- Agency owners can view broader agency-level progress when permission allows.
- Super Admins can view and manage all teams.

## Resource Library Rules

- Resources may be links, files, videos, documents, or other content types.
- Resources can be drafted or published.
- Published resources are available to appropriate roles.
- Resource management is restricted to authorized users.

## Events Rules

- Events include title, description, location, start time, and optional end time.
- Events may be used for training sessions, team meetings, webinars, licensing sessions, and recognition events.
- Event management is restricted to authorized users.

## Announcements Rules

- Announcements may be drafted and published.
- Published announcements should appear on the dashboard and announcements page.
- Announcement management is restricted to authorized users.

## Recognition Rules

- Badges may be used to recognize progress, achievements, milestones, and leadership.
- Recognition can support motivation and team culture.
- Badge assignment rules still need business confirmation.

## Access Control Rules

Permissions include:

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

## Default Role Expectations

super-admin:

- Full access to all portal areas and administrative settings.

admin:

- Access to user management and core setup table management, excluding low-level role ownership unless specifically granted.

agency-owner:

- Broad access to users, teams, training, onboarding, licensing, rank advancement, mentorship, announcements, and events.

team-leader:

- Access to team visibility, assigned team progress, mentorship status, onboarding, licensing, rank advancement, events, and announcements.

certified-field-mentor:

- Access to assigned apprentices, mentor notes, apprenticeship progress, training, and resources.

trainer:

- Access to training and assessment management.

associate:

- Access to personal dashboard, onboarding, licensing, apprenticeship, training, assessments, rank progress, resources, events, recognition, announcements, and team view where allowed.

new-recruit:

- Access to personal dashboard, onboarding, licensing, apprenticeship, starter training, resources, events, recognition, and announcements.

## UI Requirements

- Use a premium financial services admin portal design.
- Main color palette: navy, gold, white, and soft gray.
- Include sidebar navigation.
- Include grouped sidebar navigation.
- Include topbar with responsive search, notification bell dropdown, and avatar/profile dropdown.
- Use responsive dashboard cards.
- Use progress bars for onboarding, licensing, training, apprenticeship, and rank advancement.
- Use rank badges.
- Use checklist cards.
- Use training module cards.
- Include team hierarchy views.
- Design mobile-first.

## Primary Navigation

- Dashboard
- Trackers: My Onboarding, Licensing Tracker, Contracting Tracker, My Tasks, Field Apprenticeship, CFM Training, Training Center, Assessments
- My Team: My Directs, My Trainees, My CFMs, All Downlines, Prospect Management
- Communications: Announcements, Events, Calendar, Notifications, Rank Advancement, Recognition
- Resources: Documents, Videos, Recorded Webinars, Zoom Links
- Admin Management: Admin Dashboard, User Management, Roles & Permissions, Ranks, Teams, Checklists (Types, Instructions, Items), Training Setup, CFM Certification, All Setup Tables, Admin Settings

## Open Business Decisions

- Which remaining rank-advancement evidence and approval steps still need a GWTracks workflow beyond the published Greatway catalog.
- Exact licensing workflow by province or state.
- Which apprenticeship steps are mandatory.
- CFM certification approval criteria.
- Whether mentor notes can be visible to apprentices.
- Badge and recognition assignment rules.
- Notification delivery channels.
- Whether events need registration and attendance tracking.
- Whether resources need role-specific visibility rules.
- Whether team hierarchy should support multiple teams per user.

## Technical Decisions

- Framework: Laravel 13.
- UI stack: Blade, Livewire, Alpine.js, Tailwind CSS.
- Auth: Laravel Breeze with Blade stack.
- Authorization: Spatie Laravel Permission.
- Database: MySQL.
- Frontend design: responsive admin portal with navy/gold financial services styling.

## Change Log

- 2026-05-31: Added responsible-party and notified-party accountability fields for onboarding, licensing, FAP, and CFM Training checklists.
- 2026-05-31: Added `SP` direct sponsor as a checklist accountability and completion-notification code.
- 2026-05-31: Added baseline seeders for Licensing checklist, Field Apprenticeship Program checklist, and CFM Training modules.
- 2026-05-31: Built Licensing, Field Apprenticeship, and CFM Training tracker pages with progress cards, progress graphs, collapsible checklist rows, pending confirmation, reviewer comments, and confirm/reject workflow.
- 2026-05-31: Added strict sponsorship hierarchy visibility rules, global information exception, agency-owner-only CFM assignment, and cross-agency CFM profile visibility exception.
- 2026-05-31: Clarified that Greatway ranks and GWTracks roles are separate, with eligibility expectations for agency-owner, team-leader, certified-field-mentor, and trainer roles.
- 2026-05-31: Added global and country-specific onboarding checklist applicability rules.
- 2026-05-31: Added invitation-only registration business rules for duplicate prevention, active GW associate confirmation, sponsor confirmation, email preview/editing, sponsor sender identity, accepted invitation hiding, and invitation deletion/recreation.
- 2026-05-31: Added default registration assignment rules for `member` role, TA rank, sponsor team inheritance, and `super-admin`/`agency-owner`-only role/rank/team changes.
- 2026-08-20: Adopted Greatway Producer/Builder promotions and FYO compensation rates (TA through NEVC).
- 2026-08-20: Set SVP as the agency-owner / first Field Leader rank.
- 2026-08-20: Documented the admin Checklists hub (`/admin/checklists`) with Types, Instructions, and Items pages plus Back to Checklists navigation.
- 2026-05-31: Added grouped sidebar navigation, responsive topbar search, notification bell dropdown, avatar menu, My Tasks, Prospect Management, resource category pages, and Admin Management grouping.
- 2026-05-30: Initial business rules and project requirements file created.
