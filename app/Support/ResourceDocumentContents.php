<?php

namespace App\Support;

class ResourceDocumentContents
{
    public static function associateWelcomePacket(): string
    {
        return <<<'HTML'
<div class="packet">
<h1>📘 Associate Welcome Packet</h1>
<h2>🎉 Welcome to the Team</h2>
<p><strong>Congratulations and welcome!</strong> You are now part of a community committed to your growth, leadership, and long-term success.</p>
<p>As a new associate, you have access to <strong>training, mentorship</strong>, and the <strong>GWTracks</strong> portal to build a strong foundation.</p>
<h2>🌟 Our Mission</h2>
<p>Help families make informed financial decisions while providing associates with <strong>growth, leadership, and business success</strong> through learning, consistency, and mentorship.</p>
<h2>📅 Your First 30 Days</h2>
<h3>✅ Week 1: Getting Started</h3>
<ul>
<li><strong>Objectives:</strong> Account setup, meet sponsor &amp; CFM, learn GWTracks</li>
<li><strong>Actions:</strong> Complete profile, upload docs, attend orientation, schedule mentoring</li>
</ul>
<div class="tip"><strong>💡 Tip:</strong> Focus on learning, not perfection.</div>
<h3>✅ Week 2: Licensing &amp; Learning</h3>
<ul>
<li><strong>Objectives:</strong> Understand licensing, products, compliance</li>
<li><strong>Actions:</strong> Enroll in courses, attend training, complete assessments</li>
</ul>
<div class="tip"><strong>💡 Tip:</strong> Consistency beats intensity.</div>
<h3>✅ Week 3: Field Apprenticeship Prep</h3>
<ul>
<li><strong>Objectives:</strong> Learn prospecting, observe mentors</li>
<li><strong>Actions:</strong> Attend mentorship, review strategies, set goals</li>
</ul>
<div class="tip"><strong>💡 Tip:</strong> Ask questions often.</div>
<h3>✅ Week 4: Building Momentum</h3>
<ul>
<li><strong>Objectives:</strong> Strengthen knowledge, build habits</li>
<li><strong>Actions:</strong> Complete tasks, review progress with mentor, prepare 60-day plan</li>
</ul>
<div class="tip"><strong>💡 Tip:</strong> Daily activity = success.</div>
<h2>🧭 Field Apprenticeship Program (FAP)</h2>
<p>Your <strong>Certified Field Mentor (CFM)</strong> guides you through systems, best practices, milestones, and real-world experience. FAP supports growth through <strong>structured learning</strong>.</p>
<h2>🤝 Your Support Team</h2>
<div class="grid-support">
<div class="support-card"><strong>Sponsor</strong> Initial guidance, motivation</div>
<div class="support-card"><strong>CFM</strong> Training, licensing, accountability</div>
<div class="support-card"><strong>Agency Owner</strong> Mentorship &amp; leadership oversight</div>
<div class="support-card"><strong>Support Team</strong> Admin, compliance, licensing, training</div>
</div>
<h2>💻 GWTracks Portal &ndash; Quick Guide</h2>
<table>
<tbody>
<tr><th>Section</th><th>Purpose</th></tr>
<tr><td><strong>Dashboard</strong></td><td>Daily tasks, events, progress</td></tr>
<tr><td><strong>My Tasks</strong></td><td>Deadlines &amp; completion</td></tr>
<tr><td><strong>Training Center</strong></td><td>Videos, docs, assessments</td></tr>
<tr><td><strong>Licensing Center</strong></td><td>Exam &amp; document tracking</td></tr>
<tr><td><strong>FAP Section</strong></td><td>Apprenticeship progress</td></tr>
<tr><td><strong>Calendar</strong></td><td>Sessions, mentor meetings</td></tr>
<tr><td><strong>Resource Library</strong></td><td>Guides, templates, FAQ</td></tr>
</tbody>
</table>
<h2>🔗 Essential Links</h2>
<p>Use these quick-access links for recurring calls, training sessions, and mentor support.</p>
<ul>
<li><a href="https://zoom.us/j/10000000001">Weekly Team Huddle</a> &mdash; Standing Monday team call for announcements, wins, and weekly priorities.</li>
<li><a href="https://zoom.us/j/10000000002">New Associate Fast Start</a> &mdash; Live onboarding session covering portal setup and first-week goals.</li>
<li><a href="https://zoom.us/j/10000000003">CFM Office Hours</a> &mdash; Drop-in mentor support for apprenticeship questions and field coaching.</li>
<li><a href="https://zoom.us/j/10000000004">Product Training Room</a> &mdash; Recurring product education and case study review sessions.</li>
<li><a href="https://zoom.us/j/10000000005">Leadership Development Call</a> &mdash; Monthly leadership call for rank advancement and team-building strategies.</li>
<li><a href="https://zoom.us/j/10000000006">National Training Broadcast</a> &mdash; Organization-wide training broadcast and Q&amp;A.</li>
<li><a href="https://zoom.us/j/10000000007">Compliance Refresher Session</a> &mdash; Quarterly compliance and AML refresher for licensed associates.</li>
<li><a href="https://example.com/book/mentor">Mentor Scheduling Page</a> &mdash; Book one-on-one time with your assigned CFM.</li>
</ul>
<h2>📌 Daily Habits for Success</h2>
<ul>
<li>Log into <strong>GWTracks</strong> daily</li>
<li>Complete assigned tasks</li>
<li>Attend training &amp; communicate with mentors</li>
<li>Review goals &amp; track progress</li>
</ul>
<h2>❓ Common Questions</h2>
<ul>
<li><strong>What if I don't know what to do next?</strong><br>➜ Check dashboard/tasks, then ask your mentor.</li>
<li><strong>How often meet with my mentor?</strong><br>➜ Weekly during first 30&ndash;90 days.</li>
<li><strong>What if I fall behind?</strong><br>➜ Tell your mentor immediately.</li>
<li><strong>Who to contact first?</strong><br>1. CFM &rarr; 2. Sponsor &rarr; 3. Agency Leader &rarr; 4. Support Team</li>
</ul>
<div class="success-box">
<p>🔑 <strong>Keys to Success</strong></p>
<p>Stay coachable, consistent, and positive.</p>
<p>Follow the system, complete training, and take action daily.</p>
</div>
<h2>💪 Welcome Again</h2>
<p><strong>Your journey starts today.</strong> Stay committed, stay coachable.</p>
<div class="footer"><strong>GWTracks.com</strong></div>
<div class="footer">Training, Mentorship &amp; Performance Tracking</div>
</div>
HTML;
    }

    public static function fieldApprenticeshipOverview(): string
    {
        return <<<'HTML'
<div class="packet">
<h1>Field Apprenticeship Program Overview</h1>
<p>The <strong>Field Apprenticeship Program (FAP)</strong> is your structured path from new associate to field-ready producer. You are paired with a <strong>Certified Field Mentor (CFM)</strong> who guides licensing progress, training, field activity, and milestone completion inside GWTracks.</p>

<h2>Who this program is for</h2>
<ul>
<li>New associates completing onboarding and licensing</li>
<li>Associates building consistent prospecting and presentation habits</li>
<li>Trainees preparing for rank advancement and future mentoring roles</li>
</ul>

<h2>Program goals</h2>
<ul>
<li>Complete required FAP checklist milestones with CFM confirmation where needed</li>
<li>Build licensing readiness and compliance habits</li>
<li>Practice real-world field activity with mentor coaching</li>
<li>Establish a sustainable weekly rhythm for growth</li>
</ul>

<h2>Program phases</h2>
<table>
<thead>
<tr>
<th>Phase</th>
<th>Focus</th>
<th>Typical outcomes</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>1. Orientation</strong></td>
<td>Portal setup, expectations, mentor introduction</td>
<td>Profile complete, first CFM meeting booked, FAP tracker open</td>
</tr>
<tr>
<td><strong>2. Foundation</strong></td>
<td>Licensing prep, product basics, compliance fundamentals</td>
<td>Licensing steps in motion, core training underway</td>
</tr>
<tr>
<td><strong>3. Field Activity</strong></td>
<td>Prospecting rhythm, presentations, mentor shadowing</td>
<td>Logged activities, observed presentations, follow-up discipline</td>
</tr>
<tr>
<td><strong>4. Advancement Readiness</strong></td>
<td>Rank requirements, leadership habits, next-step planning</td>
<td>FAP milestones complete, clear plan for continued growth</td>
</tr>
</tbody>
</table>

<h2>Your support team</h2>
<table>
<thead>
<tr>
<th>Role</th>
<th>How they help</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>CFM</strong></td>
<td>Weekly coaching, checklist confirmation, field guidance, accountability</td>
</tr>
<tr>
<td><strong>Sponsor</strong></td>
<td>Encouragement, team culture, recruiting context, early support</td>
</tr>
<tr>
<td><strong>Team Leader / Agency Owner</strong></td>
<td>Oversight, capacity, escalation support when blockers appear</td>
</tr>
</tbody>
</table>

<h2>How progress works in GWTracks</h2>
<ol>
<li>Open <strong>Field Apprenticeship</strong> under My Progress.</li>
<li>Complete each milestone and mark it done when finished.</li>
<li>If confirmation is required, your CFM (or other notified party) reviews and confirms.</li>
<li>Track overall FAP progress from your Dashboard widgets.</li>
<li>Use Training Center, Resources, Calendar, and Appointments to support each phase.</li>
</ol>

<h2>Weekly rhythm (recommended)</h2>
<ul>
<li><strong>Daily:</strong> Check Dashboard, tasks, and FAP next steps</li>
<li><strong>Several times per week:</strong> Training lessons and prospecting activity</li>
<li><strong>Weekly with CFM:</strong> Review blockers, confirmations, and field practice</li>
<li><strong>Ongoing:</strong> Keep licensing and compliance records current</li>
</ul>

<h2>Completion expectations</h2>
<ul>
<li>Required FAP checklist items must be completed (and confirmed when required)</li>
<li>Licensing and training milestones should stay current with your CFM plan</li>
<li>Field activity should show consistent effort, not one-time bursts</li>
<li>Ask for help early if you fall behind &mdash; your CFM is there to coach, not just evaluate</li>
</ul>

<h2>Helpful GWTracks links</h2>
<ul>
<li><strong>Field Apprenticeship tracker</strong> &mdash; <code>/field-apprenticeship</code></li>
<li><strong>Training Center</strong> &mdash; <code>/training</code></li>
<li><strong>Resource Library</strong> &mdash; <code>/resources/documents</code></li>
<li><strong>My Appointments / Mentor Scheduling</strong> &mdash; book structured CFM time</li>
<li><strong>My Calendar</strong> &mdash; keep coaching and training sessions visible</li>
</ul>

<h2>Live support sessions</h2>
<ul>
<li><a href="https://zoom.us/j/10000000003">CFM Office Hours</a> &mdash; Ask apprenticeship and field-coaching questions.</li>
<li><a href="https://zoom.us/j/10000000004">Product Training Room</a> &mdash; Reinforce product knowledge between mentor sessions.</li>
<li><a href="/bookings/my">Mentor Scheduling</a> &mdash; Book one-on-one time with your assigned CFM.</li>
</ul>

<p><strong>Remember:</strong> FAP is a guided apprenticeship. Stay coachable, complete the next milestone, and communicate early with your CFM.</p>
</div>
HTML;
    }

    public static function codeOfConductSummary(): string
    {
        return <<<'HTML'
<div class="packet">
<h1>Code of Conduct Summary</h1>
<p>This summary outlines the conduct, privacy, and regulatory expectations for GWTracks members and Greatway Financial Group associates. It supports a professional, ethical, and compliant culture across the team.</p>

<h2>Purpose</h2>
<p>Follow these standards when representing yourself, your team, and the agency in client work, recruiting, mentoring, training, and portal use.</p>

<h2>Professional conduct</h2>
<ul>
<li>Treat clients, prospects, teammates, mentors, and leaders with respect and honesty.</li>
<li>Communicate clearly and professionally in messages, calls, meetings, and presentations.</li>
<li>Do not misrepresent products, compensation, ranks, licensing status, or company affiliations.</li>
<li>Keep commitments you make to clients, mentees, sponsors, and CFMs.</li>
<li>Escalate concerns early to your CFM, sponsor, or agency leadership when issues arise.</li>
</ul>

<h2>Ethics and integrity</h2>
<ul>
<li>Put the client's best interest first in every recommendation and conversation.</li>
<li>Never pressure, deceive, or withhold material information to close a sale or recruit.</li>
<li>Avoid conflicts of interest, and disclose them when they cannot be avoided.</li>
<li>Do not falsify activity logs, production entries, checklist completions, or training records.</li>
<li>Protect the reputation of the team by modeling coachable, accountable behavior.</li>
</ul>

<h2>Licensing and compliance</h2>
<ul>
<li>Only discuss or sell products you are properly licensed and appointed to offer.</li>
<li>Keep licenses, E&amp;O, AML, CE, and carrier appointments current in GWTracks and with regulators.</li>
<li>Follow all applicable insurance, privacy, anti-spam, and advertising rules in your jurisdiction.</li>
<li>Use approved materials, scripts, and disclosures whenever required.</li>
<li>Report compliance questions or potential violations promptly to leadership.</li>
</ul>

<h2>Privacy and data protection</h2>
<ul>
<li>Collect only the personal information needed to serve a prospect, client, or trainee.</li>
<li>Store client and prospect data in approved systems &mdash; never in unsecured personal files or public channels.</li>
<li>Do not share private contact details, FNA data, or account information without authorization.</li>
<li>Use GWTracks access only for legitimate business purposes within your permission scope.</li>
<li>Report suspected data breaches or unauthorized access immediately.</li>
</ul>

<h2>Portal and team systems</h2>
<ul>
<li>Keep your GWTracks profile accurate, including contact details, licenses, and photo.</li>
<li>Use tasks, checklists, CRM, and mentoring tools honestly and on time.</li>
<li>Do not attempt to access records outside your sponsorship hierarchy or assigned permissions.</li>
<li>Protect your login credentials and never share your account.</li>
<li>Treat support tickets, messages, and coaching notes as professional business records.</li>
</ul>

<h2>Mentoring and leadership expectations</h2>
<ul>
<li>CFMs and leaders coach with clarity, fairness, and confidentiality.</li>
<li>Confirm checklist items only when the work is truly complete.</li>
<li>Provide constructive feedback and document decisions that affect trainee progress.</li>
<li>Do not use mentoring authority to pressure personal favors or unethical activity.</li>
</ul>

<table>
<thead>
<tr>
<th>Area</th>
<th>Expected standard</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>Client care</strong></td>
<td>Honest advice, suitable recommendations, respectful follow-up</td>
</tr>
<tr>
<td><strong>Recruiting</strong></td>
<td>Accurate opportunity framing, no income guarantees, clear next steps</td>
</tr>
<tr>
<td><strong>Training</strong></td>
<td>Complete assigned learning; no shortcut or false completion</td>
</tr>
<tr>
<td><strong>Records</strong></td>
<td>Accurate CRM, production, checklist, and compliance entries</td>
</tr>
</tbody>
</table>

<h2>Reporting concerns</h2>
<ol>
<li>Talk with your CFM or sponsor first when appropriate.</li>
<li>Escalate to your team leader or agency owner for unresolved or sensitive issues.</li>
<li>Use <strong>Help &amp; Support</strong> in GWTracks for system or process problems.</li>
<li>For serious compliance or ethics concerns, contact agency leadership immediately.</li>
</ol>

<p><strong>Acknowledgement:</strong> By participating as an active associate in this team portal, you agree to uphold this Code of Conduct Summary and the full policies provided by your agency and Greatway Financial Group.</p>
</div>
HTML;
    }

    public static function licensingExamPrepChecklist(): string
    {
        return <<<'HTML'
<div class="packet">
<h1>Licensing Exam Prep Checklist</h1>
<p>Use this checklist from enrollment through license issuance.</p>
<ol>
<li>Confirm jurisdiction requirements with your CFM.</li>
<li>Complete pre-licensing coursework on schedule.</li>
<li>Schedule and pass the licensing exam.</li>
<li>Submit appointment paperwork and background materials.</li>
<li>Track approval status in GWTracks.</li>
</ol>
<h2>Helpful Sessions</h2>
<ul>
<li><a href="https://zoom.us/j/10000000002">New Associate Fast Start</a> &mdash; Licensing overview for new associates.</li>
<li><a href="https://zoom.us/j/10000000007">Compliance Refresher Session</a> &mdash; Regulatory expectations after licensing.</li>
</ul>
</div>
HTML;
    }
}
