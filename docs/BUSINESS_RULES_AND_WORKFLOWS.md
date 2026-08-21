# GWTrack Business Rules And Workflows

This document defines the operating rules and primary workflows for GWTrack.com. It should be treated as the source of truth for how users, teams, onboarding, licensing, mentorship, apprenticeship, training, assessments, resources, recognition, events, and rank advancement behave inside the portal.

## Portal Rules

- GWTrack is a private portal for a Greatway Financial Group team.
- Users must be authenticated before accessing the portal.
- Users must have an active account status to access protected areas.
- Access is controlled by role and permission using Spatie Laravel Permission.
- A user may have multiple roles when needed.
- A user may belong to one primary team.
- A user may have one sponsor.
- A visitor may only register as a GWTrack member through an invitation link from a current GWTrack member.
- Every invitation link must include a tracked registration code.
- Registration codes are stored and audited so the portal can identify who sponsored each new member.
- A newly registered member must receive `sponsor_id` from the sponsoring member tied to the invitation code.
- A newly registered member receives the default `member` role.
- A newly registered member receives the default `TA` (Training Associate) rank.
- A newly registered member is assigned to the same team as their sponsor.
- Only users with `super-admin`, `admin`, or `agency-owner` roles may change a user's role, rank, or team after registration.
- Only active Greatway Financial Group associates may complete registration.
- Registration must collect the user's GW Associate ID.
- The invited person must confirm that the displayed sponsor is the person who invited them before registration can continue.
- If the displayed sponsor is not correct, the invited person should discontinue registration and request the correct invitation link from the correct sponsor.
- A user may have one active Certified Field Mentor assignment.
- A user has one current rank.
- Historical progress should be preserved when rank, team, mentor, or role changes occur.
- Unless content or information is explicitly tagged as global, users may only see information inside their sponsorship hierarchy.
- A user's visible hierarchy includes members they directly recruited and all recruits underneath those members.
- Checklist items must identify who is responsible for confirmation and follow-up.
- Responsible party codes are Self, SP, AO, TL, CFM, and TR, and may be combined.
- SP means the member's direct sponsor from the sponsorship hierarchy.
- Checklist items may identify who should be notified through notifications or email when the item is completed.
- Notified party codes are SP, AO, TL, CFM, and TR, and may be combined.
- Notified parties are completion-alert recipients for the checklist item.
- Calendar events may be private, shared with invited attendees, shared with a team, shared with a downline branch, or visible across the organization.
- Private calendar events are visible only to the organizer, invited attendees, or users with private-calendar authority.
- Calendar events tied to prospects, licensing, FAP, CFM, training, or rank advancement must respect the same hierarchy visibility rules as the related business record.

## Roles

- super-admin
- admin
- agency-owner
- team-leader
- certified-field-mentor
- trainer
- associate
- member
- new-recruit

## Rank And Role Distinction

- Ranks are Greatway Financial Group advancement levels and represent business progression.
- Roles are GWTrack application access groups and determine portal permissions.
- Rank changes do not automatically equal role changes unless a GWTrack workflow explicitly grants the corresponding role.
- Senior Vice President (SVP) and above usually qualify for the `agency-owner` role. SVP is the first Field Leader rank.
- Senior Associate rank members usually qualify for the `team-leader` role.
- The `certified-field-mentor` role may be granted to members at Associate rank and above after successfully completing CFM training and approval.
- The `trainer` role may be granted to members who successfully completed the Field Apprenticeship Program and have started CFM Training.
- Final role assignment remains controlled by authorized administrators unless automated approval workflows are implemented later.

## Rank Path

Producer track (personal production):

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

Canonical figures: `docs/greatway-promotion-compensation-guidelines.md` and `config/greatway-compensation.php`.

## Compensation Rules

- Each rank stores track (producer or builder), Total FYO, compensation rate, PPB rate, non-FYC rate, income metric, and Field Leader flag.
- The rank advancement tracker shows current and next-rank compensation.
- Generational overrides apply at SVP and above.
- Rank-based CFM management access starts at SVP. The `agency-owner` role also grants that access independently of rank.

## Access Control Rules

- `super-admin` can manage the entire portal.
- `admin` can manage users and core setup tables, excluding low-level role ownership unless specifically granted.
- `agency-owner` can manage agency-level users, teams, training, onboarding, licensing, mentorship, announcements, events, and rank advancement.
- `team-leader` can view and manage assigned team members where permissions allow.
- `certified-field-mentor` can view assigned apprentices and update apprenticeship progress where permissions allow.
- `trainer` can manage training and assessments.
- `associate` can view personal progress, training, resources, events, recognition, announcements, and team information where allowed.
- `member` can view personal progress, training, resources, events, recognition, announcements, and team information where allowed.
- `new-recruit` can view personal onboarding, licensing, apprenticeship, starter training, events, resources, recognition, and announcements.

## Hierarchy Visibility Rules

- Sponsorship hierarchy is the default visibility boundary for non-global data.
- A user may see their own information, members they directly recruited, and all downline members recruited under those direct recruits.
- A user may not see another sponsor line's information unless the record is explicitly tagged global or an exception rule applies.
- Direct sponsorship is stored on `users.sponsor_id`.
- Fast downline visibility queries are powered by `user_hierarchy_paths`, a closure table containing ancestor, descendant, and depth.
- Sponsor relationship history is tracked in `sponsor_relationships`.
- Explicit visibility exceptions are tracked in `team_visibility_permissions`.
- `view direct downline` limits visibility to the member and first-level recruits unless another permission expands visibility.
- `view full downline` allows visibility into all descendant levels under the viewer's sponsorship branch.
- `view all teams` allows authorized platform-level users to view all team data.
- `export team data` is required before CSV export is available.
- `view sensitive profile data` is required before sensitive profile fields can be shown outside self-view or explicit visibility grants.
- Global information is visible across hierarchy boundaries when the content type and user permissions allow it.
- Roles grant actions and tools, but roles do not automatically override sponsorship hierarchy visibility unless a specific business exception is defined.
- Agency owners may see profile information for Certified Field Mentors from other agencies for CFM discovery and mentor assignment planning.
- Cross-agency CFM visibility is limited to CFM profile information needed for assignment decisions and does not grant general access to another agency's downline.
- An agency owner's own CFM pool has first priority for mentor assignment before considering CFMs from other agencies.
- Only agency owners may assign a CFM to a new member under their sponsorship hierarchy.
- A CFM may only be assigned to an apprentice or associate when the CFM is licensed in the associate's province or state (country + province/state on the associate profile must match an entry on the CFM's licensed jurisdictions list).
- CFM assignment UIs must filter the selectable CFM list to licensed matches as soon as the associate is chosen; the server rejects assignments that violate this rule.

## Core Permissions

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
- view calendar
- create calendar events
- edit own calendar events
- delete own calendar events
- manage team calendar
- manage training calendar
- manage licensing calendar
- manage prospect appointments
- manage mentor sessions
- manage rank review events
- view shared calendar events
- invite attendees
- manage event visibility
- view private events
- manage organization calendar

## User Lifecycle Workflow

1. A current GWTrack member creates or provides an invitation link with a tracked registration code.
2. The invited person opens the invitation link and completes registration.
3. The system validates that the invitation code is active, not revoked, not expired, and has available uses.
4. The new user's `sponsor_id` is set to the sponsoring member tied to the invitation code.
5. The invitation record stores usage and accepted user details for tracking.
6. The user receives a role such as `new-recruit`, `associate`, `member`, `trainer`, `certified-field-mentor`, `team-leader`, `agency-owner`, or `super-admin`.
7. The user is assigned to a team when applicable.
8. The user is assigned a starting rank.
9. New Recruits and Associates should be assigned to a Certified Field Mentor.
10. The user begins onboarding, licensing, starter training, and apprenticeship progress.
11. Progress is reviewed by the user, CFM, team leadership, and administrators where permitted.
12. The user may advance through rank requirements as criteria are completed.
13. The user profile remains active unless suspended, deactivated, or archived.

## Invitation Registration Workflow

1. A current active GWTrack member sponsors a prospective member.
2. The system creates a registration invitation with a unique registration code.
3. The system must not create a new active invitation for an email address that already has an active invitation.
4. The system must not create or send an invitation to an email address that already belongs to a registered GWTrack user.
5. The sponsor may preview and edit the invitation email before sending it.
6. The invitation email message must keep the registration link in the body.
7. The invitation email popup must remind the sender to add the recipient name before sending.
8. The invitation email sender name and reply-to identity must use the logged-in sponsor's profile details.
9. The invitation email template is stored in the database so it can be managed without code changes.
10. The sponsor sends the invitation link to the prospective member.
11. The prospective member can only access the registration form through the invitation link.
12. Open registration without a valid invitation code is blocked.
13. The registration form shows the sponsor tied to the registration code.
14. The prospective member must confirm the sponsor before continuing.
15. The prospective member must enter an GW Associate ID and confirm that they are an active Greatway Financial Group associate.
16. When the form is submitted, the system validates the code, sponsor status, invited email when present, expiry, revocation status, and usage count.
17. The new member account is created with `sponsor_id` set to the sponsor's user ID.
18. The new member account receives the `member` role, `TA` rank, and the sponsor's current team.
19. The registration invitation records `accepted_by`, `accepted_at`, and updated usage count.
20. After successful registration, the invitation code must be deactivated so it cannot be reused.
21. Accepted invitations must not appear in the sponsor's Recent Invitations panel.
22. A sponsor may delete an active invitation, which deactivates the code.
23. After an active invitation is deleted, the sponsor may create and send a new invitation to the same email as long as that email has not registered.
24. Single-use invitation links cannot be reused after acceptance.

## Onboarding Workflow

1. Admin creates onboarding steps.
2. Admin marks each step as required or optional.
3. Admin assigns responsible parties for confirmation and follow-up using Self, SP, AO, TL, CFM, TR, or combinations.
4. Admin assigns notified parties who receive notification or email when the item is completed.
5. Admin may mark onboarding steps as global or country-specific.
6. Global steps apply to all members.
7. Country-specific steps apply only when the member profile country matches the step country.
8. User receives onboarding progress records for applicable checklist items.
9. User checks an onboarding item to submit it for confirmation.
10. Submitted items move to pending confirmation status.
11. Notified parties review pending items and may add confirmation comments.
12. Notified parties may confirm or reject submitted items.
13. Confirmed items move to completed status and receive a completion date.
14. Rejected items remain visible to the member with review comments so they can be corrected and resubmitted.
15. Responsible parties follow up until checklist items are completed.
16. Notified parties receive notifications or email when items are completed.
17. Authorized leaders may review progress.
18. Dashboard reflects onboarding completion percentage.

## Licensing Workflow

1. Admin creates licensing steps.
2. Admin assigns responsible parties for confirmation and follow-up using Self, SP, AO, TL, CFM, TR, or combinations.
3. Admin assigns notified parties who receive notification or email when the item is completed.
4. Steps may be general or tied to a province or state in a future version.
5. User begins each licensing step as not started.
6. User checks a licensing item to submit it for confirmation.
7. Submitted items move to pending confirmation status.
8. Notified parties review pending items and may add confirmation comments.
9. Notified parties may confirm or reject submitted items.
10. Confirmed items move to completed status and receive a completion date.
11. Rejected items remain visible to the member with review comments so they can be corrected and resubmitted.
12. Responsible parties confirm or follow up until licensing items are completed.
13. Notified parties receive notifications or email when items are completed.
14. Notes may be added for internal tracking.
15. Dashboard reflects licensing progress.
16. Licensing completion can contribute to apprenticeship or rank advancement.

## Certified Field Mentor Assignment Workflow

1. A New Recruit or Associate is identified as needing a mentor.
2. The associate's country and province/state must be on file; assignment cannot proceed without them.
3. The agency owner selects an active Certified Field Mentor who is licensed in that jurisdiction; the assignment UI lists only matching CFMs once the associate is chosen.
4. The agency owner's own CFM pool should be considered first among licensed matches.
5. If needed, the agency owner may review CFM profile information from other agencies for assignment planning.
6. A mentor assignment is created with apprentice, mentor, assigner, start date, and status.
7. Apprentice dashboard shows the assigned mentor.
8. Mentor dashboard shows assigned apprentices.
9. Mentor may create notes, track sessions, and update apprenticeship progress.
10. Assignment can be ended or replaced by the responsible agency owner or higher authorized administrator.

## Field Apprenticeship Workflow

1. Admin creates an apprenticeship program.
2. Admin adds apprenticeship steps.
3. Admin assigns responsible parties for confirmation and follow-up using Self, SP, AO, TL, CFM, TR, or combinations.
4. Admin assigns notified parties who receive notification or email when the item is completed.
5. Apprentice receives progress records for each step.
6. CFM guides the apprentice through the steps.
7. Apprentice checks a FAP item to submit it for confirmation.
8. Submitted items move to pending confirmation status.
9. Notified parties review pending items and may add confirmation comments.
10. Notified parties may confirm or reject submitted items.
11. Confirmed items move to completed status and receive a completion date.
12. Rejected items remain visible to the apprentice with review comments so they can be corrected and resubmitted.
13. CFM records notes and progress updates.
14. Responsible parties confirm or follow up until FAP items are completed.
15. Notified parties receive notifications or email when items are completed.
16. Required steps must be completed before apprenticeship approval.
17. Authorized user approves apprenticeship completion.
18. Completion may unlock next training, recognition, or rank progress.

## Mentor Notes Workflow

1. CFM opens an assigned apprentice record.
2. CFM creates a mentor note.
3. Note is associated with mentor, apprentice, and mentor assignment.
4. Note may include follow-up date.
5. Notes are private by default unless apprentice-facing notes are enabled later.
6. Team Leaders, Agency Owners, and Super Admins may review notes where permissions allow.

## Calendar And Events Workflow

1. A user opens the Calendar workspace from the Communications menu.
2. The calendar shows only events the user is allowed to see.
3. A user may create personal calendar events when they have `create calendar events`.
4. A user may edit or delete only their own events unless they have team or organization calendar authority.
5. A private event is hidden from all users except the organizer, invited attendees, or users with `view private events`.
6. A team event is visible to the selected team or visibility rule recipients.
7. An organization event is visible to users with shared calendar access.
8. Prospect appointments should be linked to the related prospect record when applicable.
9. Licensing reviews should be linked to licensing milestones when applicable.
10. FAP mentor sessions should be linked to the apprentice when applicable.
11. CFM training events should be linked to the applicable CFM certification module or workflow.
12. Rank review events should be linked to the rank advancement workflow when applicable.
13. Attendees may be users or prospects.
14. RSVP status is tracked for invited attendees.
15. Event reminders may be delivered through in-app notifications or email in future notification workflows.
16. Calendar event activity is logged for auditing important scheduling changes.

## CFM Certification Workflow

1. User completes required CFM certification training modules.
2. Each CFM training module has responsible parties for confirmation and follow-up using Self, SP, AO, TL, CFM, TR, or combinations.
3. Each CFM training module has notified parties who receive notification or email when the item is completed.
4. User checks a CFM training module to submit it for confirmation.
5. Submitted modules move to pending confirmation status.
6. Notified parties review pending modules and may add confirmation comments.
7. Notified parties may confirm or reject submitted modules.
8. Confirmed modules move to completed status and receive a completion date.
9. Rejected modules remain visible to the member with review comments so they can be corrected and resubmitted.
10. Responsible parties confirm or follow up until CFM training items are completed.
11. Notified parties receive notifications or email when items are completed.
12. User submits CFM certification request.
13. Request enters pending status.
14. Authorized reviewer approves or rejects the request.
15. Reviewer notes are stored.
16. Approved users at Associate rank or above may be assigned the Certified Field Mentor role.
17. Rejected user can review feedback and reapply if allowed.

## Training Workflow

1. Trainer or admin creates training categories.
2. Trainer or admin creates modules inside categories.
3. Trainer or admin creates lessons inside modules.
4. Modules may be draft or published.
5. Users only see published training unless they have management permissions.
6. Lesson and module progress is tracked per user.
7. Training may be attached to onboarding, apprenticeship, CFM certification, or rank advancement.
8. The `trainer` role may be granted after successful Field Apprenticeship Program completion and after the member has started CFM Training.

## Assessment Workflow

1. Trainer or admin creates an assessment.
2. Questions and answers are attached to the assessment.
3. Assessment is assigned to a module or used independently.
4. User starts an assessment attempt.
5. User submits answers.
6. System calculates score and pass/fail status.
7. Attempt history is stored.
8. Passing assessments may complete training or rank requirements.

## Rank Advancement Workflow

1. Admin defines rank requirements for each Greatway rank (Producer TA–AVP and Builder SVP–NEVC).
2. User progress is tracked against each requirement.
3. Requirements may include licensing, onboarding, training, assessments, production, recruiting, Field Leader legs, mentorship, or manual approval.
4. The tracker shows current and next-rank compensation (Total FYO, compensation rate, and PPB).
5. User and leadership can view progress toward next rank.
6. Authorized users may approve rank advancement when requirements are met.
7. User current rank is updated.
8. Rank progress history remains available for reporting.

## Admin Checklists Hub Workflow

1. Authorized admins open `/admin/checklists`.
2. The hub links to Checklist Types, Checklist Instructions, and Checklist Items.
3. Types define checklist categories (onboarding, licensing, contracting, FAP, CFM training, mentoring, and future types).
4. Instructions store rich-text help, manuals, and reference URLs shown while members complete a checklist.
5. Items are the steps members complete across those types.
6. Types, Instructions, and Items pages include a Back to Checklists action that returns to the hub.

## Team Hierarchy Workflow

1. Agency Owner or authorized admin creates a team.
2. Team may have an owner and optional parent team.
3. Users are assigned to teams.
4. Team Leaders can view team members where permitted.
5. Agency Owners can view broader team progress.
6. Super Admin can view and manage all teams.
7. Visibility must still respect the sponsorship hierarchy unless the information is tagged global or an explicit exception applies.

## Team Navigation Workflow

1. Users with team visibility can access My Directs, My Trainees, My CFMs, All Downlines, and Prospect Management.
2. My Directs represents immediately sponsored or directly assigned members.
3. My Trainees represents members actively progressing through training under the current user's visibility.
4. My CFMs represents Certified Field Mentor relationships and apprentice coverage.
5. All Downlines represents broader hierarchy visibility where permission allows.
6. Prospect Management represents prospective member follow-up before or during the invitation process.

## Resource Workflow

1. Authorized user creates a resource.
2. Resource may be a file, link, document, video, or other content type.
3. Resource can be draft or published.
4. Resource visibility should support global content and hierarchy-scoped content.
5. Published resources appear in the appropriate resource section.
6. Resource navigation is grouped into Documents, Videos, Recorded Webinars, and Zoom Links.

## Event Workflow

1. Authorized user creates an event.
2. Event includes title, description, location, start time, and optional end time.
3. Event appears on calendar and dashboard widgets.
4. Future versions may support registration, attendance, reminders, and event recordings.

## Announcement Workflow

1. Authorized user creates an announcement.
2. Announcement may be draft or published.
3. Published announcements appear on the dashboard and announcements page.
4. Announcement may expire after a configured date.
5. Future versions may support audience targeting by role, team, or rank.

## Recognition Workflow

1. Admin creates badges.
2. Badges may represent progress, achievement, leadership, licensing, training, rank advancement, or mentorship.
3. Badges can be assigned manually in the first version.
4. Future versions may support automated badge rules.
5. Recognition appears on the dashboard and recognition wall.

## Notification Rules

- Notifications should be generated for important events such as mentor assignment, training completion, assessment result, apprenticeship approval, CFM request status, rank advancement, new announcement, and upcoming event.
- Initial version may use in-app notifications.
- The topbar notification bell should show unread count, recent notifications, and links to all notifications and announcements.
- Future versions may add email and SMS notifications.

## Task Center Rules

- Checklist confirmation tasks for onboarding, licensing, Field Apprenticeship, and CFM Training are reviewed from My Tasks.
- Individual checklist pages show the member's own progress and submission status.
- Notified parties use My Tasks to add review comments and confirm or reject submitted checklist items.
- My Tasks may also include CFM assignment tasks, invitation email follow-up, promotion review, and future assigned work.

## Portal Navigation Rules

- Sidebar navigation is grouped into Dashboard, Trackers, My Team, Communications, Resources, and Admin Management.
- Trackers includes My Onboarding, Licensing Tracker, Contracting Tracker, My Tasks, Field Apprenticeship, CFM Training, Training Center, and Assessments.
- Communications includes Announcements, Events, Calendar, Notifications, Rank Advancement, and Recognition.
- Resources includes Documents, Videos, Recorded Webinars, and Zoom Links.
- Admin Management links are shown only to roles or permissions that can access the related admin areas. Checklist administration starts at `/admin/checklists` (Types, Instructions, Items).
- The topbar includes global search, notifications, and avatar/profile actions.

## Open Workflow Decisions

- Which remaining rank-advancement evidence and approval steps still need a GWTrack workflow beyond the published Greatway income, NBA, and Field Leader leg qualifications.
- Exact licensing workflow by province or state.
- Mandatory apprenticeship step list.
- CFM certification approval criteria.
- Whether mentor notes can be visible to apprentices.
- Badge automation rules.
- Notification delivery channels.
- Event registration and attendance requirements.
- Whether users can belong to multiple teams.
