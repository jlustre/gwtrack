# CRM Dual Funnel and Calendar Integration Audit

## Blueprint Intent

The attached CRM blueprint describes a unified relationship platform where every person can move through either or both of these paths:

- Sales funnel: prospect to customer/client.
- Recruiting funnel: prospect to business partner/associate.

The requested direction is to develop this phase by phase while making Calendar a first-class integration point for appointments, presentations, follow-ups, recruiting events, sales meetings, reminders, and workflow automation.

## Current App Fit

GWTrack already has a strong foundation for this blueprint. The current app is not starting from zero; it already contains:

- Prospect CRM routes under `/team/prospects`.
- Recruiting Pipeline route under `/team/recruiting`.
- Dual funnel fields on `prospects`: `funnel_type`, `prospect_funnel_id`, `pipeline_stage_id`.
- Funnel catalogs: `prospect_funnels`, `prospect_funnel_stages`, `pipeline_stages`.
- Kanban board: `App\Livewire\Prospects\ProspectFunnelBoard`.
- Recruiting hub: `App\Livewire\Recruiting\RecruitingPipelineHub`.
- Prospect appointments linked to Calendar through `prospect_appointments.calendar_event_id`.
- Calendar events linked back to CRM through `calendar_events.related_prospect_id`.
- FNA integration through `calendar_events.related_fna_id`.
- Activity logs, communications, notes, files, follow-ups, shares, conversions, imports, and analytics.
- Follow-up task automation through `ProspectFollowUpEngine` and `ProspectTaskBridge`.

## Existing CRM/Funnel Architecture

### Core Models

- `App\Models\Prospect`
- `App\Models\ProspectFunnel`
- `App\Models\ProspectFunnelStage`
- `App\Models\PipelineStage`
- `App\Models\ProspectStageHistory`
- `App\Models\ProspectActivity`
- `App\Models\ProspectCommunication`
- `App\Models\ProspectAppointment`
- `App\Models\ProspectFollowUp`
- `App\Models\ProspectNote`
- `App\Models\ProspectFile`
- `App\Models\ProspectShare`
- `App\Models\ProspectConversion`

### Calendar Models

- `App\Models\CalendarEvent`
- `App\Models\CalendarEventType`
- `App\Models\CalendarCategory`
- `App\Models\CalendarEventAttendee`
- `App\Models\CalendarEventReminder`
- `App\Models\CalendarEventActivityLog`
- `App\Models\CalendarEventVisibilityRule`
- `App\Models\Booking`
- `App\Models\BookingEventType`

### Existing Integration Services

- `App\Services\Prospects\ProspectFunnelService`
- `App\Services\Prospects\ProspectCalendarBridge`
- `App\Services\Prospects\ProspectTaskBridge`
- `App\Services\Prospects\ProspectFollowUpEngine`
- `App\Services\Prospects\ProspectAnalyticsService`
- `App\Services\RecruitingPipelineService`
- `App\Services\Fna\FnaCalendarBridge`
- `App\Services\CalendarShareService`
- `App\Services\CalendarScheduleBlockService`

## Blueprint Coverage Matrix

| Blueprint Area | Current Status | Notes |
| --- | --- | --- |
| Contacts module | Partial | `prospects` currently stores contact details. No separate canonical `contacts` table yet. |
| Lead management | Partial | Source, interest, priority, funnel type exist. Lead scoring is partial through engagement/interest fields. |
| Prospect module | Strong | Personal info, notes, activity, appointments, follow-ups, pipeline, conversions, files, shares exist. |
| Sales funnel | Strong foundation | Insurance funnel exists through `funnel_type = insurance` and Kanban. Stage catalog may need expansion to match blueprint. |
| Recruiting funnel | Strong foundation | Recruiting funnel exists through `funnel_type = recruiting`, recruiting hub, and associate conversion flow. |
| Activity module | Strong | `prospect_activities`, `prospect_communications`, notes, task bridge exist. Activity type catalog should be expanded. |
| Task management | Strong | `tasks`, `task_users`, related prospect/FNA fields, dashboard modal, auto-created prospect tasks exist. |
| Calendar | Strong | Calendar module is mature and already has prospect/FNA links. Needs deeper CRM event semantics. |
| Notes | Partial | Prospect notes exist. Rich text, pinned, shared/private variants need refinement. |
| Documents | Partial | Prospect files exist. Document type taxonomy and richer storage workflows are needed. |
| Communication history | Strong foundation | Communications and activity timeline exist. Could be unified into a richer timeline. |
| Referral management | Partial | `referral_source_name` exists. Referral tree/rewards/status are not modeled yet. |
| Opportunity management | Missing/partial | No separate sales/recruiting opportunity entities yet. Current prospect stage acts as opportunity state. |
| Automations | Partial | Follow-up engine, stage history event, task bridge exist. Calendar/task/stage automation engine needs formalization. |
| Reports/analytics | Partial/strong foundation | Prospect analytics, recruiting stats, goals, dashboard metrics exist. More enterprise reports needed. |
| AI features | Partial | AI coach scaffolding exists for prospects. Prediction/scoring/drafting are future phases. |

## Calendar Integration Principle

Calendar should become the shared operational spine for CRM. Every scheduled CRM commitment should have one authoritative calendar representation:

- Prospect appointment.
- Sales presentation.
- FNA meeting.
- Recruiting presentation.
- Business opportunity meeting.
- Follow-up block.
- Training invitation.
- Orientation.
- Policy review.
- Customer review appointment.

Recommended rule:

> If a CRM action has a time, attendee, reminder, or meeting link, it should create or sync a `calendar_event`.

Current bridge:

- `ProspectAppointment` pushes to `CalendarEvent` through `ProspectCalendarBridge::pushAppointment`.
- Calendar updates can pull back into the prospect appointment through `ProspectCalendarBridge::pullChanges`.

Next improvement:

- Extend this bridge into a broader `CrmCalendarBridge` or keep specialized bridge classes but standardize event payloads and metadata.

## Identified Gaps

### 1. Canonical Contact Model

The blueprint says every person exists only once. Today, prospects are the person record. This works for the current system but will eventually limit unified customer/recruit/partner lifecycle tracking.

Recommendation:

- Phase in `contacts` later.
- Do not migrate immediately.
- First harden prospects as the CRM record, then introduce contacts when customer/business partner modules become distinct.

### 2. Sales and Recruiting Funnel Stage Depth

The app supports dual funnels but the stage list likely needs to be expanded to match the blueprint:

- Sales: New Lead, Contact Attempted, Connected, Qualified, Needs Analysis, Appointment Scheduled, Presentation Completed, Application Submitted, Underwriting, Approved, Policy Issued, Closed Won/Lost.
- Recruiting: Lead Generated, Contacted, Invitation Sent, Presentation Scheduled, Opportunity Explained, Follow-Up, Orientation, License Training, Contract Submitted, Active Partner, Leader, Closed Lost.

Recommendation:

- Keep stage tables as-is.
- Add better default seeders and stage metadata.
- Add `stage_type`, `automation_key`, or richer `auto_task_template` definitions if needed.

### 3. Calendar Event Semantics

Calendar has linked prospect/FNA IDs, but CRM event type semantics should be clearer.

Recommendation:

- Add CRM-specific calendar event types:
  - Sales appointment.
  - FNA appointment.
  - Policy review.
  - Recruiting presentation.
  - Recruiting follow-up.
  - Orientation/training invitation.
- Ensure each event type maps to a funnel action and dashboard KPI.

### 4. Follow-Up and Automation Engine

Existing `ProspectFollowUpEngine` is a good start, but the blueprint expects broader workflow automation.

Recommendation:

- Add explicit automation rules for:
  - Stage entered.
  - No activity for N days.
  - Calendar event completed/no-show.
  - Appointment scheduled/confirmed/cancelled.
  - FNA submitted/approved.
  - Recruiting invitation sent/accepted/expired.
  - License/application milestone updated.

### 5. Opportunities

The blueprint separates prospects from sales/recruiting opportunities. The current app uses prospect stage as opportunity state.

Recommendation:

- Delay standalone opportunities until funnels and calendar integration are polished.
- Add opportunity-like fields to prospects first:
  - `estimated_value`
  - `probability`
  - `expected_close_date`
  - `expected_recruitment_date`
  - `lost_reason`
  - `competitor`

### 6. Referral Management

Current fields can store source names and referral source, but there is no formal referral entity.

Recommendation:

- Add referral tables after the dual funnel baseline:
  - `contact_referrals` or `prospect_referrals`
  - referrer contact/prospect/user
  - referred prospect/contact
  - reward/status/conversion fields

## Recommended Phase Plan

### Phase 1: CRM/Funnel Inventory and Stage Normalization

Goal: make the existing dual funnel explicit and clean.

Deliverables:

- Audit and seed complete sales/recruiting stage catalogs.
- Ensure every prospect has correct `funnel_type`, `prospect_funnel_id`, and `pipeline_stage_id`.
- Improve Kanban labels and stage metadata.
- Add funnel-specific dashboard counts.
- Confirm `ProspectFunnelBoard` handles both sales and recruiting cleanly.

Calendar scope:

- No new calendar features yet.
- Only verify current prospect appointment sync does not break.

### Phase 2: Calendar-First Appointments

Goal: make CRM appointments and calendar events feel like one system.

Deliverables:

- Expand `ProspectCalendarBridge` into a standardized CRM calendar bridge.
- Add event type mapping for sales, recruiting, FNA, policy review, and follow-up.
- Add bidirectional sync tests:
  - prospect appointment to calendar event
  - calendar event update back to prospect appointment
  - cancellation sync
  - attendee/helper sync
- Display linked prospect details inside calendar event views.
- From prospect profile, show all related calendar events.

Calendar scope:

- Every scheduled prospect/recruiting meeting should create a `calendar_event`.
- Calendar event should always link back through `related_prospect_id`.

### Phase 3: Activity, Follow-Up, and Task Automation

Goal: make actions automatically produce the next operational step.

Deliverables:

- Formalize automation rules for funnel stage changes.
- Use `prospect_funnel_stages.auto_task_template` more consistently.
- When activity has `next_follow_up_at`, create/update:
  - `prospect_followups`
  - `task_users`
  - optional `calendar_events` for time-blocked follow-ups
- Add missed appointment rules:
  - mark no-show
  - create high-priority task
  - suggest reschedule calendar slot
- Add notification triggers for follow-ups and meetings.

Calendar scope:

- Follow-ups with date/time become calendar events.
- Follow-ups with date only remain task/follow-up records unless user chooses calendar block.

### Phase 4: Premium CRM Contact/Profile Experience

Goal: improve the user-facing CRM around the relationship timeline.

Deliverables:

- Unified prospect profile timeline:
  - stage changes
  - activities
  - communications
  - notes
  - appointments
  - FNA events
  - conversions
  - files
- Better contact details panel.
- Rich notes, pinned notes, private/shared note states.
- Document categories for prospect files.
- Better quick action panel:
  - call
  - SMS/email log
  - schedule meeting
  - create follow-up
  - create task
  - send invite
  - create FNA

Calendar scope:

- Prospect profile should show upcoming/past related calendar events.
- Scheduling from profile should use Calendar availability when applicable.

### Phase 5: Reporting and KPI Dashboards

Goal: create reliable CRM performance visibility.

Deliverables:

- Sales funnel report.
- Recruiting funnel report.
- Stage conversion report.
- Activity report.
- Appointment report.
- No-show/cancellation report.
- Follow-up SLA report.
- Lead source conversion report.
- Agent/CFM/AO/team rollups.

Calendar scope:

- Appointment volume, attendance, cancellation, and completion should be reportable from calendar-linked CRM data.

### Phase 6: Contact/Opportunity/Referral Expansion

Goal: graduate from prospect-centric CRM to enterprise relationship CRM.

Deliverables:

- Introduce canonical `contacts` table if still needed.
- Add `sales_opportunities`.
- Add `recruiting_opportunities`.
- Add formal referrals.
- Migrate prospect fields gradually.
- Maintain backward compatibility with current prospect UI.

Calendar scope:

- Calendar events link to contact, prospect, opportunity, and FNA where appropriate.

### Phase 7: AI and Advanced Automation

Goal: add intelligent assistance after the data model and workflows are stable.

Deliverables:

- Lead scoring.
- Recruiting probability.
- Next best action.
- Follow-up suggestions.
- Meeting summary.
- Conversation summary.
- Email/SMS draft assistance.
- Forecasting.

Calendar scope:

- AI should recommend scheduling actions and detect overdue calendar/follow-up patterns.

## First Recommended Build Step

Start with Phase 1, Step 1:

1. Create an implementation audit of current sales and recruiting funnel seed data.
2. Compare current stages against the blueprint stage list.
3. Update seeders/config to establish clean default sales and recruiting stage catalogs.
4. Add tests proving both funnels render and stage movement records history.

Why this first:

- It does not require risky table redesign.
- It strengthens what already exists.
- It gives Calendar integration stable stage/event semantics in Phase 2.

## Key Files to Work From

- `routes/web.php`
- `config/prospects.php`
- `config/recruiting-pipeline.php`
- `app/Models/Prospect.php`
- `app/Models/CalendarEvent.php`
- `app/Livewire/Prospects/ProspectFunnelBoard.php`
- `app/Livewire/Recruiting/RecruitingPipelineHub.php`
- `app/Services/Prospects/ProspectFunnelService.php`
- `app/Services/Prospects/ProspectCalendarBridge.php`
- `app/Services/Prospects/ProspectTaskBridge.php`
- `app/Services/Prospects/ProspectFollowUpEngine.php`
- `app/Services/RecruitingPipelineService.php`
- `database/migrations/0000_00_00_000015_create_prospect_management_tables.php`
- `database/migrations/0000_00_00_000017_create_calendar_module_tables.php`
- `database/migrations/0000_00_00_000037_extend_prospect_funnel_tables.php`
- `database/migrations/0000_00_00_000038_link_prospect_appointments_calendar.php`

