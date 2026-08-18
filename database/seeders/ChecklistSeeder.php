<?php

namespace Database\Seeders;

use App\Models\Checklist;
use App\Models\ChecklistType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ChecklistSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedOnboarding();
        $this->seedLicensing();
        $this->seedFap();
        $this->seedCfmTraining();
        $this->seedCfmMentoring();
    }

    private function typeId(string $code): int
    {
        return (int) ChecklistType::query()->where('code', $code)->value('id');
    }

    private function upsertItem(string $typeCode, string $title, array $attributes): void
    {
        Checklist::query()->updateOrCreate(
            [
                'checklist_type_id' => $this->typeId($typeCode),
                'title' => $title,
            ],
            array_merge([
                'is_active' => true,
                'requires_confirmation' => true,
            ], $attributes),
        );
    }

    private function seedOnboarding(): void
    {
        $steps =         [
          0 => 
          [
            'title' => 'Email Verified',
            'description' => 'Check your email to find your GWTrack verification link and click it.',
            'sort_order' => 5,
            'nth_day' => 1,
            'is_required' => true,
            'country' => NULL,
          ],
          1 => 
          [
            'title' => 'First GWTrack Login Successful',
            'description' => 'After email verification, try to login using your GWTrack credentials (email and password) and check this item upon successful login.',
            'sort_order' => 8,
            'nth_day' => 1,
            'is_required' => true,
            'country' => NULL,
          ],
          2 => 
          [
            'title' => 'Complete Member Profile',
            'description' => 'Add contact details, location, phone number, time zone, GW Associate ID, and basic profile information.',
            'sort_order' => 10,
            'nth_day' => 1,
            'is_required' => true,
            'country' => NULL,
          ],
          3 => 
          [
            'title' => 'Confirm Sponsor And Team Placement',
            'description' => 'Verify the sponsoring member, inherited team, starting FA rank, and first point of contact.',
            'sort_order' => 10,
            'nth_day' => 1,
            'is_required' => true,
            'country' => NULL,
          ],
          4 => 
          [
            'title' => 'Watch GWTrack Welcome Orientation',
            'description' => 'Review the portal purpose, dashboard, trackers, resources, communications, and team visibility areas.',
            'sort_order' => 30,
            'nth_day' => 3,
            'is_required' => true,
            'country' => NULL,
          ],
          5 => 
          [
            'title' => 'Join Primary Team Communication Channels',
            'description' => 'Join the team chat, announcement channel, email list, calendar, and recurring Zoom meeting links.',
            'sort_order' => 40,
            'nth_day' => 4,
            'is_required' => true,
            'country' => NULL,
          ],
          6 => 
          [
            'title' => 'Schedule Sponsor Welcome Call',
            'description' => 'Book the first welcome call with the sponsor to review expectations, goals, availability, and next steps.',
            'sort_order' => 50,
            'nth_day' => 5,
            'is_required' => true,
            'country' => NULL,
          ],
          7 => 
          [
            'title' => 'Receive Certified Field Mentor Assignment',
            'description' => 'Confirm the assigned Certified Field Mentor who will guide the Field Apprenticeship Program.',
            'sort_order' => 60,
            'nth_day' => 6,
            'is_required' => true,
            'country' => NULL,
          ],
          8 => 
          [
            'title' => 'Complete Business Standards Review',
            'description' => 'Review conduct expectations, client-first standards, compliance awareness, and professional communication guidelines.',
            'sort_order' => 70,
            'nth_day' => 7,
            'is_required' => true,
            'country' => NULL,
          ],
          9 => 
          [
            'title' => 'Review Licensing Roadmap',
            'description' => 'Understand licensing requirements, province or state expectations, study timeline, exam steps, and submission workflow.',
            'sort_order' => 80,
            'nth_day' => 8,
            'is_required' => true,
            'country' => NULL,
          ],
          10 => 
          [
            'title' => 'Canada: Review Provincial Licensing Path',
            'description' => 'Review the provincial licensing path, regulators, education requirements, and exam process that apply in Canada.',
            'sort_order' => 85,
            'nth_day' => 8,
            'is_required' => true,
            'country' => 'Canada',
          ],
          11 => 
          [
            'title' => 'United States: Review State Licensing Path',
            'description' => 'Review the state licensing path, regulators, education requirements, and exam process that apply in the United States.',
            'sort_order' => 85,
            'nth_day' => 8,
            'is_required' => true,
            'country' => 'United States',
          ],
          12 => 
          [
            'title' => 'Start Licensing Tracker',
            'description' => 'Open the licensing tracker, review required steps, and update the initial licensing status.',
            'sort_order' => 90,
            'nth_day' => 9,
            'is_required' => true,
            'country' => NULL,
          ],
          13 => 
          [
            'title' => 'Complete Starter Training Module',
            'description' => 'Finish the first training module covering Greatway basics, field expectations, products, and client conversations.',
            'sort_order' => 100,
            'nth_day' => 10,
            'is_required' => true,
            'country' => NULL,
          ],
          14 => 
          [
            'title' => 'Review Resource Library',
            'description' => 'Open Documents, Videos, Recorded Webinars, and Zoom Links to understand where key support materials live.',
            'sort_order' => 110,
            'nth_day' => 11,
            'is_required' => false,
            'country' => NULL,
          ],
          15 => 
          [
            'title' => 'Begin Field Apprenticeship Program',
            'description' => 'Meet with the assigned CFM and review apprenticeship steps, mentor sessions, expectations, and approval workflow.',
            'sort_order' => 120,
            'nth_day' => 12,
            'is_required' => true,
            'country' => NULL,
          ],
          16 => 
          [
            'title' => 'Set First 30-Day Activity Goals',
            'description' => 'Define initial prospecting, training, licensing, mentorship, and team meeting goals with the sponsor or leader.',
            'sort_order' => 130,
            'nth_day' => 13,
            'is_required' => true,
            'country' => NULL,
          ],
          17 => 
          [
            'title' => 'Review Rank Advancement Path',
            'description' => 'Review FA through EP rank path, rank requirements, recognition milestones, and leadership development expectations.',
            'sort_order' => 140,
            'nth_day' => 14,
            'is_required' => false,
            'country' => NULL,
          ],
          18 => 
          [
            'title' => 'Attend First Team Event Or Training',
            'description' => 'Attend a live team event, webinar, huddle, or training session and record any follow-up tasks.',
            'sort_order' => 150,
            'nth_day' => 15,
            'is_required' => true,
            'country' => NULL,
          ],
        ];

        $responsibleParties =         [
          'Email Verified' => 'Self',
          'First GWTrack Login Successful' => 'Self',
          'Complete Member Profile' => 'Self',
          'Confirm Sponsor And Team Placement' => 'SP, AO',
          'Watch GWTrack Welcome Orientation' => 'Self, SP',
          'Join Primary Team Communication Channels' => 'Self, SP',
          'Schedule Sponsor Welcome Call' => 'Self, SP',
          'Receive Certified Field Mentor Assignment' => 'SP, AO',
          'Complete Business Standards Review' => 'Self, SP, TL',
          'Review Licensing Roadmap' => 'Self, SP, CFM',
          'Canada: Review Provincial Licensing Path' => 'Self, SP, CFM',
          'United States: Review State Licensing Path' => 'Self, SP, CFM',
          'Start Licensing Tracker' => 'Self, SP, CFM',
          'Complete Starter Training Module' => 'Self, SP, TR',
          'Review Resource Library' => 'Self, SP',
          'Begin Field Apprenticeship Program' => 'Self, SP, CFM',
          'Set First 30-Day Activity Goals' => 'Self, SP, TL',
          'Review Rank Advancement Path' => 'Self, SP, TL',
          'Attend First Team Event Or Training' => 'Self, SP, TL',
        ];

        $notifiedParties =         [
          'Email Verified' => 'SP, TR',
          'First GWTrack Login Successful' => 'SP, TR',
          'Complete Member Profile' => 'SP',
          'Confirm Sponsor And Team Placement' => 'SP, AO, TL',
          'Watch GWTrack Welcome Orientation' => 'SP',
          'Join Primary Team Communication Channels' => 'SP, TL',
          'Schedule Sponsor Welcome Call' => 'SP',
          'Receive Certified Field Mentor Assignment' => 'SP, CFM, AO',
          'Complete Business Standards Review' => 'SP, TL',
          'Review Licensing Roadmap' => 'SP, CFM',
          'Canada: Review Provincial Licensing Path' => 'SP, CFM',
          'United States: Review State Licensing Path' => 'SP, CFM',
          'Start Licensing Tracker' => 'SP, CFM',
          'Complete Starter Training Module' => 'SP, TR',
          'Review Resource Library' => 'SP',
          'Begin Field Apprenticeship Program' => 'SP, CFM',
          'Set First 30-Day Activity Goals' => 'SP, TL',
          'Review Rank Advancement Path' => 'SP, TL',
          'Attend First Team Event Or Training' => 'SP, TL',
        ];

        foreach ($steps as $step) {
            $this->upsertItem('onboarding', $step['title'], [
                'description' => $step['description'],
                'sort_order' => $step['sort_order'],
                'nth_day' => $step['nth_day'],
                'responsible_parties' => $responsibleParties[$step['title']] ?? 'Self',
                'notified_parties' => $notifiedParties[$step['title']] ?? null,
                'is_required' => $step['is_required'],
                'requires_confirmation' => $step['requires_confirmation'] ?? true,
                'country' => $step['country'],
            ]);
        }
    }

    private function seedLicensing(): void
    {
        $steps =         [
          0 => 
          [
            'title' => 'Confirm Licensing Jurisdiction',
            'description' => 'Confirm the country, province, state, or territory where the associate will begin licensing.',
            'sort_order' => 10,
            'nth_day' => 1,
            'is_required' => true,
          ],
          1 => 
          [
            'title' => 'Review Licensing Requirements',
            'description' => 'Review regulator requirements, pre-licensing education, exam expectations, timelines, fees, and required documents.',
            'sort_order' => 20,
            'nth_day' => 2,
            'is_required' => true,
          ],
          2 => 
          [
            'title' => 'Create Licensing Study Plan',
            'description' => 'Set study schedule, exam target date, weekly accountability rhythm, and support contact.',
            'sort_order' => 30,
            'nth_day' => 3,
            'is_required' => true,
          ],
          3 => 
          [
            'title' => 'Enroll In Required Licensing Course',
            'description' => 'Enroll in the required licensing course or approved education provider for the selected jurisdiction.',
            'sort_order' => 40,
            'nth_day' => 4,
            'is_required' => true,
          ],
          4 => 
          [
            'title' => 'Complete Pre-Licensing Education',
            'description' => 'Complete required coursework, practice modules, quizzes, and provider completion requirements.',
            'sort_order' => 50,
            'nth_day' => 5,
            'is_required' => true,
          ],
          5 => 
          [
            'title' => 'Submit Exam Registration',
            'description' => 'Register for the licensing exam and confirm the exam date, time, location, or online exam details.',
            'sort_order' => 60,
            'nth_day' => 6,
            'is_required' => true,
          ],
          6 => 
          [
            'title' => 'Complete Exam Prep Review',
            'description' => 'Complete final practice exams, weak-area review, and mentor check-in before the licensing exam.',
            'sort_order' => 70,
            'nth_day' => 7,
            'is_required' => true,
          ],
          7 => 
          [
            'title' => 'Pass Licensing Exam',
            'description' => 'Record successful exam completion and upload or note proof of passing when available.',
            'sort_order' => 80,
            'nth_day' => 8,
            'is_required' => true,
          ],
          8 => 
          [
            'title' => 'Submit License Application',
            'description' => 'Submit the license application, required forms, fees, disclosures, and supporting documents.',
            'sort_order' => 90,
            'nth_day' => 9,
            'is_required' => true,
          ],
          9 => 
          [
            'title' => 'Complete Background Or Compliance Requirements',
            'description' => 'Complete any background check, compliance questionnaire, E&O, or jurisdiction-specific suitability requirement.',
            'sort_order' => 100,
            'nth_day' => 10,
            'is_required' => true,
          ],
          10 => 
          [
            'title' => 'Receive License Approval',
            'description' => 'Confirm license approval and record license number or approval confirmation in the member profile.',
            'sort_order' => 110,
            'nth_day' => 11,
            'is_required' => true,
          ],
          11 => 
          [
            'title' => 'Complete Carrier Or Product Appointment Steps',
            'description' => 'Complete required appointment, contracting, product training, or access setup steps before field activity.',
            'sort_order' => 120,
            'nth_day' => 12,
            'is_required' => false,
          ],
          12 => 
          [
            'title' => 'Notify Sponsor And CFM Of Licensing Status',
            'description' => 'Notify the sponsor and assigned CFM that licensing status has changed and update the next field activity plan.',
            'sort_order' => 130,
            'nth_day' => 13,
            'is_required' => true,
          ],
        ];

        $responsibleParties =         [
          'Confirm Licensing Jurisdiction' => 'Self, SP, CFM',
          'Review Licensing Requirements' => 'Self, SP, CFM',
          'Create Licensing Study Plan' => 'Self, SP, CFM',
          'Enroll In Required Licensing Course' => 'Self, SP, CFM',
          'Complete Pre-Licensing Education' => 'Self, SP, CFM',
          'Submit Exam Registration' => 'Self, SP, CFM',
          'Complete Exam Prep Review' => 'Self, SP, CFM',
          'Pass Licensing Exam' => 'Self, SP, CFM',
          'Submit License Application' => 'Self, SP, AO',
          'Complete Background Or Compliance Requirements' => 'Self, SP, AO',
          'Receive License Approval' => 'Self, SP, AO, CFM',
          'Complete Carrier Or Product Appointment Steps' => 'Self, SP, AO',
          'Notify Sponsor And CFM Of Licensing Status' => 'Self, SP, CFM',
        ];

        $notifiedParties =         [
          'Confirm Licensing Jurisdiction' => 'SP, CFM',
          'Review Licensing Requirements' => 'SP, CFM',
          'Create Licensing Study Plan' => 'SP, CFM',
          'Enroll In Required Licensing Course' => 'SP, CFM',
          'Complete Pre-Licensing Education' => 'SP, CFM',
          'Submit Exam Registration' => 'SP, CFM',
          'Complete Exam Prep Review' => 'SP, CFM',
          'Pass Licensing Exam' => 'SP, CFM, AO',
          'Submit License Application' => 'SP, AO, CFM',
          'Complete Background Or Compliance Requirements' => 'SP, AO',
          'Receive License Approval' => 'SP, AO, CFM',
          'Complete Carrier Or Product Appointment Steps' => 'SP, AO',
          'Notify Sponsor And CFM Of Licensing Status' => 'SP, CFM',
        ];

        foreach ($steps as $step) {
            $this->upsertItem('licensing', $step['title'], [
                'description' => $step['description'],
                'sort_order' => $step['sort_order'],
                'nth_day' => $step['nth_day'],
                'responsible_parties' => $responsibleParties[$step['title']] ?? 'Self',
                'notified_parties' => $notifiedParties[$step['title']] ?? null,
                'is_required' => $step['is_required'],
                'requires_confirmation' => $step['requires_confirmation'] ?? true,
            ]);
        }
    }

    private function seedFap(): void
    {
        $groupLabel = 'Field Apprenticeship Program';

        $steps =         [
          0 => 
          [
            'title' => 'FAP Orientation With Sponsor And CFM',
            'description' => 'Review the purpose of FAP, expected timeline, meeting cadence, apprentice responsibilities, and approval requirements.',
            'sort_order' => 10,
            'nth_day' => 1,
            'is_required' => true,
          ],
          1 => 
          [
            'title' => 'Complete Field Readiness Review',
            'description' => 'Confirm professional profile, communication standards, calendar availability, technology setup, and readiness for supervised field activity.',
            'sort_order' => 20,
            'nth_day' => 2,
            'is_required' => true,
          ],
          2 => 
          [
            'title' => 'Observe First Client Conversation',
            'description' => 'Attend or observe a qualified client conversation led by the sponsor or CFM and record learning notes.',
            'sort_order' => 30,
            'nth_day' => 3,
            'is_required' => true,
          ],
          3 => 
          [
            'title' => 'Practice Needs Analysis Conversation',
            'description' => 'Role-play the discovery and needs analysis conversation with the CFM or sponsor.',
            'sort_order' => 40,
            'nth_day' => 4,
            'is_required' => true,
          ],
          4 => 
          [
            'title' => 'Review Product And Solution Positioning',
            'description' => 'Review core solution categories, suitability mindset, client-first language, and when to escalate questions.',
            'sort_order' => 50,
            'nth_day' => 5,
            'is_required' => true,
          ],
          5 => 
          [
            'title' => 'Complete Compliance And Documentation Walkthrough',
            'description' => 'Review documentation expectations, disclosure standards, privacy awareness, and compliant follow-up practices.',
            'sort_order' => 60,
            'nth_day' => 6,
            'is_required' => true,
          ],
          6 => 
          [
            'title' => 'Attend Team Training Or Field Huddle',
            'description' => 'Attend a live team training, huddle, or webinar and capture action items.',
            'sort_order' => 70,
            'nth_day' => 7,
            'is_required' => true,
          ],
          7 => 
          [
            'title' => 'Prepare First Prospect List',
            'description' => 'Build an initial prospect list, segment warm market contacts, and review outreach language with mentor support.',
            'sort_order' => 80,
            'nth_day' => 8,
            'is_required' => true,
          ],
          8 => 
          [
            'title' => 'Complete Supervised Outreach Session',
            'description' => 'Complete an outreach or appointment-setting session with sponsor or CFM coaching.',
            'sort_order' => 90,
            'nth_day' => 9,
            'is_required' => true,
          ],
          9 => 
          [
            'title' => 'Co-Host A Client Appointment',
            'description' => 'Participate in a client appointment with the CFM or sponsor and complete a post-meeting debrief.',
            'sort_order' => 100,
            'nth_day' => 10,
            'is_required' => true,
          ],
          10 => 
          [
            'title' => 'Complete Follow-Up And Service Review',
            'description' => 'Practice post-meeting follow-up, client service expectations, next-step communication, and CRM or tracking updates.',
            'sort_order' => 110,
            'nth_day' => 11,
            'is_required' => true,
          ],
          11 => 
          [
            'title' => 'Review Licensing And Field Activity Alignment',
            'description' => 'Confirm what field activities are appropriate based on the apprentice licensing status and local requirements.',
            'sort_order' => 120,
            'nth_day' => 12,
            'is_required' => true,
          ],
          12 => 
          [
            'title' => 'Submit FAP Completion Review',
            'description' => 'Submit completion notes for CFM and agency owner review, including readiness, strengths, and development needs.',
            'sort_order' => 130,
            'nth_day' => 13,
            'is_required' => true,
          ],
          13 => 
          [
            'title' => 'Receive FAP Approval',
            'description' => 'Agency owner or authorized reviewer approves completion and confirms next growth path.',
            'sort_order' => 140,
            'nth_day' => 14,
            'is_required' => true,
          ],
        ];

        $responsibleParties =         [
          'FAP Orientation With Sponsor And CFM' => 'Self, SP, CFM',
          'Complete Field Readiness Review' => 'Self, SP, CFM',
          'Observe First Client Conversation' => 'Self, SP, CFM',
          'Practice Needs Analysis Conversation' => 'Self, SP, CFM',
          'Review Product And Solution Positioning' => 'Self, SP, CFM',
          'Complete Compliance And Documentation Walkthrough' => 'Self, SP, CFM',
          'Attend Team Training Or Field Huddle' => 'Self, SP, TL',
          'Prepare First Prospect List' => 'Self, SP, CFM',
          'Complete Supervised Outreach Session' => 'Self, SP, CFM',
          'Co-Host A Client Appointment' => 'Self, SP, CFM',
          'Complete Follow-Up And Service Review' => 'Self, SP, CFM',
          'Review Licensing And Field Activity Alignment' => 'Self, SP, CFM, AO',
          'Submit FAP Completion Review' => 'Self, SP, CFM',
          'Receive FAP Approval' => 'SP, AO, CFM',
        ];

        $notifiedParties =         [
          'FAP Orientation With Sponsor And CFM' => 'SP, CFM',
          'Complete Field Readiness Review' => 'SP, CFM',
          'Observe First Client Conversation' => 'SP, CFM',
          'Practice Needs Analysis Conversation' => 'SP, CFM',
          'Review Product And Solution Positioning' => 'SP, CFM',
          'Complete Compliance And Documentation Walkthrough' => 'SP, CFM',
          'Attend Team Training Or Field Huddle' => 'SP, TL',
          'Prepare First Prospect List' => 'SP, CFM',
          'Complete Supervised Outreach Session' => 'SP, CFM',
          'Co-Host A Client Appointment' => 'SP, CFM',
          'Complete Follow-Up And Service Review' => 'SP, CFM',
          'Review Licensing And Field Activity Alignment' => 'SP, CFM, AO',
          'Submit FAP Completion Review' => 'SP, CFM, AO',
          'Receive FAP Approval' => 'SP, CFM, AO',
        ];

        foreach ($steps as $step) {
            $this->upsertItem('fap', $step['title'], [
                'description' => $step['description'],
                'sort_order' => $step['sort_order'],
                'nth_day' => $step['nth_day'],
                'group_label' => $groupLabel,
                'responsible_parties' => $responsibleParties[$step['title']] ?? 'Self',
                'notified_parties' => $notifiedParties[$step['title']] ?? null,
                'is_required' => $step['is_required'],
                'requires_confirmation' => $step['requires_confirmation'] ?? true,
            ]);
        }
    }

    private function seedCfmTraining(): void
    {
        $steps =         [
          0 => 
          [
            'title' => 'CFM Role And Responsibility Orientation',
            'description' => 'Understand the Certified Field Mentor role, apprentice expectations, boundaries, reporting cadence, and leadership responsibilities.',
            'sort_order' => 10,
            'nth_day' => 1,
            'is_required' => true,
          ],
          1 => 
          [
            'title' => 'Mentorship Standards And Ethics',
            'description' => 'Review professional standards, confidentiality, compliant communication, client-first conduct, and escalation expectations.',
            'sort_order' => 20,
            'nth_day' => 2,
            'is_required' => true,
          ],
          2 => 
          [
            'title' => 'FAP Coaching Framework',
            'description' => 'Learn how to guide apprentices through Field Apprenticeship Program milestones with structured coaching and documentation.',
            'sort_order' => 30,
            'nth_day' => 3,
            'is_required' => true,
          ],
          3 => 
          [
            'title' => 'Apprentice Readiness Assessment',
            'description' => 'Learn how to evaluate apprentice readiness, identify development gaps, and recommend next actions.',
            'sort_order' => 40,
            'nth_day' => 4,
            'is_required' => true,
          ],
          4 => 
          [
            'title' => 'Mentor Session Planning',
            'description' => 'Build effective mentor session agendas, follow-up rhythm, accountability actions, and milestone reviews.',
            'sort_order' => 50,
            'nth_day' => 5,
            'is_required' => true,
          ],
          5 => 
          [
            'title' => 'Field Observation And Debriefing',
            'description' => 'Practice observing field activity, giving feedback, debriefing client appointments, and reinforcing best practices.',
            'sort_order' => 60,
            'nth_day' => 6,
            'is_required' => true,
          ],
          6 => 
          [
            'title' => 'Licensing And Activity Boundaries',
            'description' => 'Understand how licensing status affects apprentice activities and when to involve sponsor, agency owner, or compliance support.',
            'sort_order' => 70,
            'nth_day' => 7,
            'is_required' => true,
          ],
          7 => 
          [
            'title' => 'Mentor Notes And Progress Documentation',
            'description' => 'Learn standards for mentor notes, progress updates, approval recommendations, and privacy-conscious documentation.',
            'sort_order' => 80,
            'nth_day' => 8,
            'is_required' => true,
          ],
          8 => 
          [
            'title' => 'Conflict Resolution And Escalation',
            'description' => 'Handle missed commitments, performance concerns, conduct issues, and escalation paths with professionalism.',
            'sort_order' => 90,
            'nth_day' => 9,
            'is_required' => true,
          ],
          9 => 
          [
            'title' => 'CFM Certification Review',
            'description' => 'Complete final review and confirm readiness to request Certified Field Mentor approval.',
            'sort_order' => 100,
            'nth_day' => 10,
            'is_required' => true,
          ],
          10 => 
          [
            'title' => 'Leadership Development Bonus Module',
            'description' => 'Optional leadership material for mentors preparing to support larger teams and future trainers.',
            'sort_order' => 110,
            'nth_day' => 11,
            'is_required' => false,
          ],
        ];

        $responsibleParties =         [
          'CFM Role And Responsibility Orientation' => 'Self, SP, TR',
          'Mentorship Standards And Ethics' => 'Self, SP, TR',
          'FAP Coaching Framework' => 'Self, SP, TR',
          'Apprentice Readiness Assessment' => 'Self, SP, TR',
          'Mentor Session Planning' => 'Self, SP, TR',
          'Field Observation And Debriefing' => 'Self, SP, TR',
          'Licensing And Activity Boundaries' => 'Self, SP, TR',
          'Mentor Notes And Progress Documentation' => 'Self, SP, TR',
          'Conflict Resolution And Escalation' => 'Self, SP, TR',
          'CFM Certification Review' => 'SP, AO, TR',
          'Leadership Development Bonus Module' => 'Self, SP',
        ];

        $notifiedParties =         [
          'CFM Role And Responsibility Orientation' => 'SP, TR',
          'Mentorship Standards And Ethics' => 'SP, TR',
          'FAP Coaching Framework' => 'SP, TR',
          'Apprentice Readiness Assessment' => 'SP, TR',
          'Mentor Session Planning' => 'SP, TR',
          'Field Observation And Debriefing' => 'SP, TR',
          'Licensing And Activity Boundaries' => 'SP, TR',
          'Mentor Notes And Progress Documentation' => 'SP, TR',
          'Conflict Resolution And Escalation' => 'SP, TR',
          'CFM Certification Review' => 'SP, AO, TR',
          'Leadership Development Bonus Module' => 'SP',
        ];

        foreach ($steps as $step) {
            $this->upsertItem('cfm-training', $step['title'], [
                'description' => $step['description'],
                'sort_order' => $step['sort_order'],
                'nth_day' => $step['nth_day'],
                'responsible_parties' => $responsibleParties[$step['title']] ?? 'Self',
                'notified_parties' => $notifiedParties[$step['title']] ?? null,
                'is_required' => $step['is_required'],
                'requires_confirmation' => $step['requires_confirmation'] ?? true,
            ]);
        }
    }

    private function seedCfmMentoring(): void
    {
        $phases = require __DIR__.'/data/cfm_mentoring_phases.php';
        $sortOrder = 0;

        foreach ($phases as $phase) {
            foreach ($phase['sections'] as $sectionTitle => $items) {
                foreach ($items as $title) {
                    $sortOrder++;
                    $slug = Str::slug('phase_'.$phase['phase_number'].'_'.Str::slug($title));

                    Checklist::query()->updateOrCreate(
                        ['slug' => $slug],
                        [
                            'checklist_type_id' => $this->typeId('cfm-mentoring'),
                            'title' => $title,
                            'phase_number' => $phase['phase_number'],
                            'phase_title' => $phase['phase_title'],
                            'phase_target' => $phase['phase_target'],
                            'section_title' => $sectionTitle,
                            'sort_order' => $sortOrder,
                            'is_required' => true,
                            'requires_confirmation' => true,
                            'is_active' => true,
                        ],
                    );
                }
            }
        }
    }
}
