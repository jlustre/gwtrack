<?php

namespace Database\Seeders;

use App\Models\PortalResource;
use App\Models\User;
use App\Services\DocumentationPdfService;
use App\Services\ResourcePdfService;
use Illuminate\Database\Seeder;

class ResourceDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $creatorId = User::query()->value('id');

        $documents =         [
          0 => 
          [
            'title' => 'GWTrack Welcome Orientation',
            'description' => 'Portal purpose, dashboard, trackers, resources, communications, team visibility, and your first-week path.',
            'category' => 'guides',
            'sort_order' => 1,
            'type' => 'document',
            'is_featured' => true,
            'is_published' => true,
            'url' => 'resources/welcome-orientation.pdf',
            'file_format' => 'PDF',
          ],
          1 => 
          [
            'title' => 'GWTrack Application User Guide',
            'description' => 'Complete application overview — navigation, menus, workflows, and links to module guides.',
            'category' => 'guides',
            'sort_order' => 2,
            'type' => 'document',
            'is_featured' => true,
            'is_published' => true,
            'url' => 'resources/application.pdf',
            'file_format' => 'PDF',
          ],
          2 => 
          [
            'title' => 'Goals & Performance User Guide',
            'description' => 'Goal planning, scorecards, coaching workflows, and performance reports.',
            'category' => 'guides',
            'sort_order' => 3,
            'type' => 'document',
            'is_featured' => true,
            'is_published' => true,
            'url' => 'resources/goals-and-performance.pdf',
            'file_format' => 'PDF',
          ],
          3 => 
          [
            'title' => 'Prospects & Sales Funnel User Guide',
            'description' => 'Prospect CRM, funnel board, follow-ups, and conversion tracking.',
            'category' => 'guides',
            'sort_order' => 4,
            'type' => 'document',
            'is_featured' => true,
            'is_published' => true,
            'url' => 'resources/prospect-sales-funnel.pdf',
            'file_format' => 'PDF',
          ],
          4 => 
          [
            'title' => 'FNA Management User Guide',
            'description' => 'Financial needs analysis records, client invites, and CFM review.',
            'category' => 'guides',
            'sort_order' => 5,
            'type' => 'document',
            'is_featured' => true,
            'is_published' => true,
            'url' => 'resources/fna-management.pdf',
            'file_format' => 'PDF',
          ],
          5 => 
          [
            'title' => 'Training Academy User Guide',
            'description' => 'Courses, learning paths, certifications, assignments, and achievements.',
            'category' => 'guides',
            'sort_order' => 6,
            'type' => 'document',
            'is_featured' => true,
            'is_published' => true,
            'url' => 'resources/training-academy.pdf',
            'file_format' => 'PDF',
          ],
          6 => 
          [
            'title' => 'AML Training Acknowledgement',
            'description' => 'Anti-money laundering acknowledgement and annual refresher reference.',
            'category' => 'compliance',
            'sort_order' => 10,
            'type' => 'document',
            'is_featured' => false,
            'is_published' => true,
            'url' => 'https://example.com/resources/aml-acknowledgement.pdf',
            'file_format' => 'PDF',
          ],
          7 => 
          [
            'title' => 'Associate Participation Agreement',
            'description' => 'Complete and sign the associate participation agreement online. Your profile details are pre-filled automatically.',
            'category' => 'forms',
            'sort_order' => 10,
            'type' => 'document',
            'is_featured' => false,
            'is_published' => true,
            'url' => 'resources/forms/associate-participation-agreement',
            'file_format' => 'FORM',
          ],
          8 => 
          [
            'title' => 'Associate Welcome Packet',
            'description' => 'Overview of your first 30 days, key contacts, and portal navigation.',
            'category' => 'onboarding',
            'sort_order' => 10,
            'type' => 'document',
            'is_featured' => true,
            'is_published' => true,
            'content' => '<div class="packet">
                        <h2>🎉 Welcome to the Team</h2>
                        <p><strong>Congratulations and welcome!</strong> You are now part of a community committed to your growth, leadership, and long-term success.</p>
                        <p>As a new associate, you have access to <strong>training, mentorship</strong>, and the <strong>GWTrack</strong> portal to build a strong foundation.</p>
                        <h2>🌟 Our Mission</h2>
                        <p>Help families make informed financial decisions while providing associates with <strong>growth, leadership, and business success</strong> through learning, consistency, and mentorship.</p>
                        <h2>📅 Your First 30 Days</h2>
                        <h3>✅ Week 1: Getting Started</h3>
                        <ul>
                        <li><strong>Objectives:</strong> Account setup, meet sponsor &amp; CFM, learn GWTrack</li>
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
                        <h2>💻 GWTrack Portal &ndash; Quick Guide</h2>
                        <table>
                        <tbody>
                        <tr>
                        <th>Section</th>
                        <th>Purpose</th>
                        </tr>
                        <tr>
                        <td><strong>Dashboard</strong></td>
                        <td>Daily tasks, events, progress</td>
                        </tr>
                        <tr>
                        <td><strong>My Tasks</strong></td>
                        <td>Deadlines &amp; completion</td>
                        </tr>
                        <tr>
                        <td><strong>Training Center</strong></td>
                        <td>Videos, docs, assessments</td>
                        </tr>
                        <tr>
                        <td><strong>Licensing Center</strong></td>
                        <td>Exam &amp; document tracking</td>
                        </tr>
                        <tr>
                        <td><strong>FAP Section</strong></td>
                        <td>Apprenticeship progress</td>
                        </tr>
                        <tr>
                        <td><strong>Calendar</strong></td>
                        <td>Sessions, mentor meetings</td>
                        </tr>
                        <tr>
                        <td><strong>Resource Library</strong></td>
                        <td>Guides, templates, FAQ</td>
                        </tr>
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
                        <li>Log into <strong>GWTrack</strong> daily</li>
                        <li>Complete assigned tasks</li>
                        <li>Attend training &amp; communicate with mentors</li>
                        <li>Review goals &amp; track progress</li>
                        </ul>
                        <h2>❓ Common Questions</h2>
                        <ul>
                        <li><strong>What if I don\'t know what to do next?</strong><br>➜ Check dashboard/tasks, then ask your mentor.</li>
                        <li><strong>How often meet with my mentor?</strong><br>➜ Weekly during first 30&ndash;90 days.</li>
                        <li><strong>What if I fall behind?</strong><br>➜ Tell your mentor immediately.</li>
                        <li><strong>Who to contact first?</strong><br>1. CFM &rarr; 2. Sponsor &rarr; 3. Agency Leader &rarr; 4. Support Team</li>
                        </ul>
                        <div class="success-box">
                        <p>🔑 <strong>Keys to Success</strong></p>
                        <p>Stay coachable, consistent, and positive.&nbsp;Follow the system, complete training, and take action daily.</p>
                        </div>
                        <h2>💪 Welcome Again</h2>
                        <p><strong>Your journey starts today.</strong> Stay committed, stay coachable.</p>
                        <div class="footer"><strong>GWTrack.com</strong></div>
                        <div class="footer">Training, Mentorship &amp; Performance Tracking</div>
                        </div>',
            'url' => 'resources/welcome-packet.pdf',
            'file_format' => 'PDF',
          ],
          9 => 
          [
            'title' => 'Business Card & Profile Setup Guide',
            'description' => 'How to complete your public business profile and contact materials.',
            'category' => 'general',
            'sort_order' => 10,
            'type' => 'document',
            'is_featured' => false,
            'is_published' => true,
            'url' => 'https://example.com/resources/profile-setup.pdf',
            'file_format' => 'PDF',
          ],
          10 => 
          [
            'title' => 'How To Invite',
            'description' => 'A Practical Guide to Introducing Your Business Opportunity and
                        Services to Warm Contacts Using Proven Relationship-Marketing and Posture Architecture',
            'category' => 'guides',
            'sort_order' => 10,
            'type' => 'document',
            'is_featured' => true,
            'is_published' => true,
            'url' => 'storage/resources/documents/how-to-invite-6.pdf',
            'file_format' => 'PDF',
          ],
          11 => 
          [
            'title' => 'Prospecting Weekly Activity Guide',
            'description' => 'Weekly prospecting targets, tracking tips, and follow-up cadence.',
            'category' => 'guides',
            'sort_order' => 10,
            'type' => 'document',
            'is_featured' => false,
            'is_published' => true,
            'url' => 'https://example.com/resources/prospecting-guide.pdf',
            'file_format' => 'PDF',
          ],
          12 => 
          [
            'title' => 'Building Your Prospects List',
            'description' => 'A Complete Guide to Developing, Expanding, and Managing Your
                        Prospect Pipeline to Ensure Sustainable Financial Advisory and Agency Expansion Velocity',
            'category' => 'guides',
            'sort_order' => 20,
            'type' => 'document',
            'is_featured' => true,
            'is_published' => true,
            'url' => 'storage/resources/documents/building-your-prospects-list-20.pdf',
            'file_format' => 'PDF',
          ],
          13 => 
          [
            'title' => 'Client Fact Finder Worksheet',
            'description' => 'Printable fact finder used during initial client meetings.',
            'category' => 'forms',
            'sort_order' => 20,
            'type' => 'document',
            'is_featured' => false,
            'is_published' => true,
            'url' => 'https://example.com/resources/fact-finder.pdf',
            'file_format' => 'PDF',
          ],
          14 => 
          [
            'title' => 'Code of Conduct Summary',
            'description' => 'High-level summary of conduct, privacy, and regulatory expectations.',
            'category' => 'compliance',
            'sort_order' => 20,
            'type' => 'document',
            'is_featured' => true,
            'is_published' => true,
            'content' => \App\Support\ResourceDocumentContents::codeOfConductSummary(),
            'url' => 'resources/code-of-conduct.pdf',
            'file_format' => 'PDF',
          ],
          15 => 
          [
            'title' => 'Field Apprenticeship Program Overview',
            'description' => 'Explains FAP phases, mentor expectations, and completion requirements.',
            'category' => 'onboarding',
            'sort_order' => 20,
            'type' => 'document',
            'is_featured' => true,
            'is_published' => true,
            'content' => '<div class="packet">
                                <h1>Field Apprenticeship Program Overview</h1>
                                <p>The Field Apprenticeship Program (FAP) pairs you with a Certified Field Mentor who guides licensing progress, field activity, and milestone completion.</p>
                                <h2>Program Phases</h2>
                                <ol>
                                <li><strong>Orientation</strong> &mdash; Portal setup, expectations, and mentor introduction.</li>
                                <li><strong>Foundation</strong> &mdash; Licensing prep, product basics, and compliance fundamentals.</li>
                                <li><strong>Field Activity</strong> &mdash; Prospecting rhythm, presentations, and mentor shadowing.</li>
                                <li><strong>Advancement Readiness</strong> &mdash; Rank requirements, leadership habits, and next-step planning.</li>
                                </ol>
                                <h2>Live Support Links</h2>
                                <ul>
                                <li><a href="https://zoom.us/j/10000000003">CFM Office Hours</a> &mdash; Ask apprenticeship and field-coaching questions.</li>
                                <li><a href="https://zoom.us/j/10000000004">Product Training Room</a> &mdash; Reinforce product knowledge between mentor sessions.</li>
                                <li><a href="https://example.com/book/mentor">Mentor Scheduling Page</a> &mdash; Book structured one-on-one mentor time.</li>
                                </ul>
                                </div>',
            'url' => 'resources/fap-overview.pdf',
            'file_format' => 'PDF',
          ],
          16 => 
          [
            'title' => 'Licensing Exam Prep Checklist',
            'description' => 'Step-by-step checklist from enrollment through license issuance.',
            'category' => 'guides',
            'sort_order' => 20,
            'type' => 'document',
            'is_featured' => false,
            'is_published' => true,
            'content' => '<div class="packet">
                                <h1>Licensing Exam Prep Checklist</h1>
                                <p>Use this checklist from enrollment through license issuance.</p>
                                <ol>
                                <li>Confirm jurisdiction requirements with your CFM.</li>
                                <li>Complete pre-licensing coursework on schedule.</li>
                                <li>Schedule and pass the licensing exam.</li>
                                <li>Submit appointment paperwork and background materials.</li>
                                <li>Track approval status in GWTrack.</li>
                                </ol>
                                <h2>Helpful Sessions</h2>
                                <ul>
                                <li><a href="https://zoom.us/j/10000000002">New Associate Fast Start</a> &mdash; Licensing overview for new associates.</li>
                                <li><a href="https://zoom.us/j/10000000007">Compliance Refresher Session</a> &mdash; Regulatory expectations after licensing.</li>
                                </ul>
                                </div>',
            'url' => 'https://example.com/resources/licensing-checklist.pdf',
            'file_format' => 'PDF',
          ],
          17 => 
          [
            'title' => 'Needs Analysis Presentation Outline',
            'description' => 'Structured outline for conducting a needs analysis presentation.',
            'category' => 'scripts',
            'sort_order' => 20,
            'type' => 'document',
            'is_featured' => false,
            'is_published' => true,
            'url' => 'https://example.com/resources/needs-analysis-outline.pdf',
            'file_format' => 'PDF',
          ],
          18 => 
          [
            'title' => 'The Professional Presentation System',
            'description' => 'A Step-by-Step Guide to Presenting Financial Services, Insurance Solutions, and Business Opportunities with Confidence',
            'category' => 'guides',
            'sort_order' => 20,
            'type' => 'document',
            'is_featured' => true,
            'is_published' => true,
            'url' => 'storage/resources/documents/the-professional-presentation-system-21.pdf',
            'file_format' => 'PDF',
          ],
          19 => 
          [
            'title' => 'The Follow-Up System',
            'description' => 'A Complete Guide to Following Up with Prospects Professionally, Consistently, and Effectively to Maximize Capital Expansion and Recruiting Pipelines',
            'category' => 'guides',
            'sort_order' => 30,
            'type' => 'document',
            'is_featured' => true,
            'is_published' => true,
            'url' => 'storage/resources/documents/the-follow-up-system-22.pdf',
            'file_format' => 'PDF',
          ],
          20 => 
          [
            'title' => 'Helping Prospects Become Clients',
            'description' => 'A Professional Guide to Understanding Needs, Solving Problems, and Creating Value-Based Client Relationships.',
            'category' => 'guides',
            'sort_order' => 40,
            'type' => 'document',
            'is_featured' => true,
            'is_published' => true,
            'url' => 'storage/resources/documents/helping-prospects-become-clients-23.pdf',
            'file_format' => 'PDF',
          ],
          21 => 
          [
            'title' => 'Helping Clients Become  GW Associates',
            'description' => 'A Professional Guide to Identifying, Developing, and Transitioning Happy Clients into Successful Team Members',
            'category' => 'guides',
            'sort_order' => 50,
            'type' => 'document',
            'is_featured' => true,
            'is_published' => true,
            'url' => 'storage/resources/documents/helping-clients-become-gw-associates-24.pdf',
            'file_format' => 'PDF',
          ],
          22 => 
          [
            'title' => 'Mastering Objection  Handling',
            'description' => 'A Complete Guide to Understanding, Preventing, and Responding to Objections Throughout the Prospect-to-Client-to-Associate Journey',
            'category' => 'onboarding',
            'sort_order' => 70,
            'type' => 'document',
            'is_featured' => true,
            'is_published' => true,
            'url' => 'storage/resources/documents/mastering-objection-handling-25.pdf',
            'file_format' => 'PDF',
          ],
          23 => 
          [
            'title' => 'GOAL SETTING FOR  SUCCESS',
            'description' => 'A Complete Guide to Achieving Personal, Professional, Financial, and Leadership Success Through Strategic Goal Setting',
            'category' => 'onboarding',
            'sort_order' => 80,
            'type' => 'document',
            'is_featured' => true,
            'is_published' => true,
          ],
          24 => 
          [
            'title' => 'Goal Setting For Success',
            'description' => 'A Complete Guide to Achieving Personal, Professional, Financial, and Leadership Success Through Strategic Goal Setting',
            'category' => 'guides',
            'sort_order' => 80,
            'type' => 'document',
            'is_featured' => true,
            'is_published' => true,
            'url' => 'storage/resources/documents/goal-setting-for-success-19.pdf',
            'file_format' => 'PDF',
          ],
        ];

        foreach ($documents as $document) {
            PortalResource::query()->updateOrCreate(
                [
                    'title' => $document['title'],
                    'type' => $document['type'] ?? 'document',
                ],
                [
                    'created_by' => $creatorId,
                    'description' => $document['description'] ?? null,
                    'content' => $document['content'] ?? null,
                    'category' => $document['category'] ?? 'general',
                    'sort_order' => $document['sort_order'] ?? 0,
                    'is_featured' => $document['is_featured'] ?? false,
                    'url' => $document['url'] ?? null,
                    'file_format' => $document['file_format'] ?? null,
                    'is_published' => $document['is_published'] ?? true,
                ],
            );
        }

        app(ResourcePdfService::class)->ensureAssociateWelcomePacketPdf();
        app(ResourcePdfService::class)->ensureFapOverviewPdf();
        app(ResourcePdfService::class)->ensureCodeOfConductPdf();
        app(DocumentationPdfService::class)->ensurePortalResource(
            'welcome-orientation',
            'GWTrack Welcome Orientation',
            'resources/welcome-orientation.pdf',
        );
        app(DocumentationPdfService::class)->ensurePortalResource(
            'application',
            'GWTrack Application User Guide',
            'resources/application.pdf',
        );
        app(DocumentationPdfService::class)->ensurePortalResource(
            'goals-and-performance',
            'Goals & Performance User Guide',
            'resources/goals-and-performance.pdf',
        );
        app(DocumentationPdfService::class)->ensurePortalResource(
            'prospect-sales-funnel',
            'Prospects & Sales Funnel User Guide',
            'resources/prospect-sales-funnel.pdf',
        );
        app(DocumentationPdfService::class)->ensurePortalResource(
            'fna-management',
            'FNA Management User Guide',
            'resources/fna-management.pdf',
        );
        app(DocumentationPdfService::class)->ensurePortalResource(
            'training-academy',
            'Training Academy User Guide',
            'resources/training-academy.pdf',
        );
    }
}
