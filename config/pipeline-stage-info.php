<?php

/**
 * Optional detailed copy for pipeline board stage info modals.
 * Keys match prospect_funnel_stages.slug per funnel.
 */
return [
    'insurance' => [
        'new-lead' => [
            'summary' => 'A new prospect has entered your pipeline but has not yet been contacted.',
            'objectives' => ['Make first contact within 24 hours', 'Confirm basic contact details', 'Identify initial interest and urgency'],
            'recommended_actions' => ['Place an introductory call', 'Send a welcome text or email', 'Schedule the first conversation'],
            'advance_when' => 'Move after a contact attempt is logged or you reach the prospect and confirm next steps.',
        ],
        'contact-attempted' => [
            'summary' => 'You have tried to reach the prospect but have not connected live yet.',
            'objectives' => ['Use multiple contact methods', 'Leave professional voicemails', 'Set a follow-up reminder'],
            'recommended_actions' => ['Try alternate phone times', 'Send a short follow-up message', 'Log each attempt'],
            'advance_when' => 'Move to Connected once you speak with the prospect, or keep here while attempts continue.',
        ],
        'connected' => [
            'summary' => 'You have spoken with the prospect and confirmed they are reachable.',
            'objectives' => ['Build rapport', 'Confirm they are open to a conversation', 'Identify basic needs'],
            'recommended_actions' => ['Ask discovery questions', 'Confirm best contact time', 'Schedule a needs conversation'],
            'advance_when' => 'Move when interest is confirmed and basic qualification criteria are met.',
        ],
        'qualified' => [
            'summary' => 'The prospect appears to be a fit for a needs analysis or next sales conversation.',
            'objectives' => ['Confirm budget/timeline fit', 'Identify decision makers', 'Set expectations for next meeting'],
            'recommended_actions' => ['Book a discovery or FNA appointment', 'Send pre-meeting materials', 'Document household details'],
            'advance_when' => 'Move when qualification is complete and a discovery meeting is planned.',
        ],
        'needs-analysis' => [
            'summary' => 'You are uncovering the prospect\'s financial needs and protection gaps.',
            'objectives' => ['Complete fact-finding', 'Document goals and obligations', 'Identify suitable solutions'],
            'recommended_actions' => ['Run a structured needs analysis', 'Capture notes in the prospect profile', 'Prepare recommendation options'],
            'advance_when' => 'Move when analysis is complete and an appointment to present solutions is scheduled.',
        ],
        'appointment-scheduled' => [
            'summary' => 'A client meeting is booked but not yet confirmed.',
            'objectives' => ['Confirm date, time, and location', 'Send calendar invite', 'Prepare meeting materials'],
            'recommended_actions' => ['Send confirmation message', 'Review prospect notes before the meeting', 'Prepare presentation materials'],
            'advance_when' => 'Move to Appointment Confirmed once the client confirms attendance.',
        ],
        'appointment-confirmed' => [
            'summary' => 'The upcoming meeting is confirmed with the prospect.',
            'objectives' => ['Reconfirm 24 hours before meeting', 'Prepare tailored agenda', 'Gather required documents'],
            'recommended_actions' => ['Send reminder', 'Practice presentation flow', 'Log any pre-meeting questions'],
            'advance_when' => 'Move after the meeting occurs or presentation stage is scheduled.',
        ],
        'presentation-scheduled' => [
            'summary' => 'A product or solution presentation is scheduled.',
            'objectives' => ['Align presentation to identified needs', 'Confirm attendees', 'Prepare quotes or illustrations'],
            'recommended_actions' => ['Review needs analysis notes', 'Prepare visual aids', 'Confirm meeting logistics'],
            'advance_when' => 'Move after the presentation is delivered.',
        ],
        'presentation-completed' => [
            'summary' => 'The solution presentation has been delivered to the prospect.',
            'objectives' => ['Capture objections and questions', 'Confirm understanding', 'Schedule follow-up review'],
            'recommended_actions' => ['Log presentation activity with outcome', 'Send recap email', 'Schedule post-presentation follow-up'],
            'advance_when' => 'Move when the prospect understands the recommendation and next decision step is clear.',
        ],
        'application-submitted' => [
            'summary' => 'An application has been submitted and carrier processing may be underway.',
            'objectives' => ['Track underwriting requirements', 'Collect missing documents', 'Keep client informed'],
            'recommended_actions' => ['Confirm documents received', 'Follow up with underwriting', 'Update client on status'],
            'advance_when' => 'Move when underwriting review is actively in progress or requirements are outstanding.',
        ],
        'closed-won' => [
            'summary' => 'The sale completed successfully and the prospect became a client.',
            'objectives' => ['Deliver policy and onboarding', 'Schedule client orientation', 'Ask for referrals'],
            'recommended_actions' => ['Complete customer orientation', 'Add servicing follow-ups', 'Request introductions'],
            'advance_when' => 'Keep here as the final won outcome unless the relationship re-enters an active stage.',
        ],
        'closed-lost' => [
            'summary' => 'The opportunity did not move forward or was declined.',
            'objectives' => ['Document loss reason', 'Leave door open for future contact', 'Clean up open tasks'],
            'recommended_actions' => ['Log reason codes in notes', 'Set long-term nurture if appropriate', 'Archive active follow-ups'],
            'advance_when' => 'Keep here unless the prospect re-engages and returns to an active stage.',
        ],
    ],

    'recruiting' => [
        'lead-generated' => [
            'summary' => 'A recruiting candidate has been identified but not yet contacted.',
            'objectives' => ['Make first outreach quickly', 'Confirm interest in the opportunity', 'Learn their background'],
            'recommended_actions' => ['Schedule an introduction call', 'Send career overview materials', 'Log first contact attempt'],
            'advance_when' => 'Move after first outreach is attempted or contact is made.',
        ],
        'contacted' => [
            'summary' => 'Initial recruiting outreach has started with the candidate.',
            'objectives' => ['Build rapport', 'Gauge career interest', 'Identify timing and motivation'],
            'recommended_actions' => ['Ask about their current role and goals', 'Share high-level opportunity overview', 'Schedule a deeper conversation'],
            'advance_when' => 'Move when the candidate is engaged and open to learning more.',
        ],
        'business-presentation' => [
            'summary' => 'The career or business presentation has been delivered.',
            'objectives' => ['Answer questions', 'Confirm interest level', 'Identify next onboarding step'],
            'recommended_actions' => ['Log presentation outcome', 'Schedule follow-up meeting', 'Send invitation or next-step materials'],
            'advance_when' => 'Move when the candidate understands the opportunity and next milestone is defined.',
        ],
        'contract-submitted' => [
            'summary' => 'Contracting or licensing paperwork has been submitted.',
            'objectives' => ['Track contracting status', 'Support licensing steps', 'Remove blockers quickly'],
            'recommended_actions' => ['Check carrier or agency requirements', 'Follow up on missing items', 'Keep candidate informed'],
            'advance_when' => 'Move when contracting is approved or active onboarding begins.',
        ],
        'active-partner' => [
            'summary' => 'The candidate is now an active associate in the business.',
            'objectives' => ['Support fast start activities', 'Confirm training progress', 'Set production goals'],
            'recommended_actions' => ['Connect with upline support', 'Review onboarding checklist', 'Schedule regular check-ins'],
            'advance_when' => 'Keep here as a successful terminal recruiting outcome.',
        ],
        'closed-lost' => [
            'summary' => 'The candidate declined or is no longer pursuing the opportunity.',
            'objectives' => ['Document reason', 'Maintain professional relationship', 'Close open recruiting tasks'],
            'recommended_actions' => ['Log outcome notes', 'Set long-term nurture if appropriate', 'Archive active follow-ups'],
            'advance_when' => 'Keep here unless the candidate re-engages later.',
        ],
    ],
];
