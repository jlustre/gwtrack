<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates =         [
          0 => 
          [
            'key' => 'agency_owner_new_member_welcome',
            'name' => 'Agency Owner New Member Welcome',
            'subject' => 'New Associate, {{ member_name }}, Has Joined Your Team And Ready for CFM Assignment',
            'body' => '<p class="isSelectedEnd">Hello {{ agency_owner_name }},</p>
                <p class="isSelectedEnd">A new associate has successfully registered in <strong>{{ app_name }}</strong> and is ready to begin their onboarding journey.</p>
                <p class="isSelectedEnd"><strong>New Associate Information</strong></p>
                <p class="isSelectedEnd"><strong>Name</strong>: {{ member_name }}<br><strong>Email</strong>: {{ member_email }}<br><strong>Phone</strong>:&nbsp;{{ member_phone }}<br><strong>Sponsor</strong>: {{ sponsor_name }}<br><strong>Profile</strong>: <a href="{{%20base_url%20}}/team/member/{{%20member_id%20}}/profile">{{ base_url }}/team/member/{{ member_id }}/profile</a></p>
                <h3>Action Required</h3>
                <p class="isSelectedEnd">To help ensure a successful start, please assign a Certified Field Mentor (CFM) to support this associate through the Field Apprenticeship Program (FAP).</p>
                <p class="isSelectedEnd">Assigning a mentor early helps new associates:</p>
                <ul data-spread="false">
                <li>Navigate licensing and onboarding requirements</li>
                <li>Complete training and assessments</li>
                <li>Stay accountable to key milestones</li>
                <li>Accelerate field readiness and rank advancement</li>
                <li>Receive guidance and support from an experienced leader</li>
                </ul>
                <h3>Assign a Certified Field Mentor</h3>
                <p class="isSelectedEnd">Access your CFM Management Dashboard here:</p>
                <p class="isSelectedEnd"><a href="{{ base_url }}/team/cfms" target="_blank" rel="noopener">{{ base_url }}/team/cfms</a></p>
                <p class="isSelectedEnd">Thank you for helping our newest team members build a strong foundation for success.</p>
                <p class="isSelectedEnd">Best regards,</p>
                <p>{{ app_name }}<br>Training, Mentorship &amp; Performance Tracking Platform</p>',
            'token_values' => NULL,
            'is_active' => true,
          ],
          1 => 
          [
            'key' => 'cfm_assignment_confirmation_request',
            'name' => 'CFM Assignment Confirmation Request',
            'subject' => 'Please confirm your new trainee assignment for {{ member_name }}',
            'body' => '<p class="isSelectedEnd">Hello {{ cfm_name }},</p>
                <p class="isSelectedEnd">You have been selected to serve as the Certified Field Mentor (CFM) for a new associate entering the Field Apprenticeship Program (FAP) within {{ app_name }}.</p>
                <h3>New Trainee Information</h3>
                <p class="isSelectedEnd"><strong>Member Name</strong>: {{ member_name }}<br><strong>Sponsored By</strong>: {{ sponsor_name }}<strong><br>Member Email:</strong> {{ member_email }}<br><strong>Profile Link</strong>: <a href="{{ base_url }}/team/member/{{ member_id }}/profile" target="_blank" rel="noopener">View {{ member_name }}&rsquo;s profile</a></p>
                <h3>Action Required</h3>
                <p class="isSelectedEnd">Please review and confirm this mentorship assignment so the associate\'s onboarding, training, and field development activities can begin.</p>
                <p class="isSelectedEnd">Confirm Assignment:</p>
                <p class="isSelectedEnd"><a href="{{ confirmation_url }}" target="_blank" rel="noopener">Confirm this mentorship assignment</a></p>
                <h3>Your Role as a Certified Field Mentor</h3>
                <p class="isSelectedEnd">As a CFM, you play a vital role in helping new associates:</p>
                <ul data-spread="false">
                <li>Complete onboarding requirements</li>
                <li>Navigate licensing and compliance milestones</li>
                <li>Participate in training and field activities</li>
                <li>Develop confidence and professional skills</li>
                <li>Progress toward rank advancement and long-term success</li>
                </ul>
                <h3>Review Pending Assignments</h3>
                <p class="isSelectedEnd">You may also access your CFM Dashboard to review this and any other pending mentorship assignments:</p>
                <p class="isSelectedEnd"><a href="{{ base_url }}{{ cfm_portal_url }}" target="_blank" rel="noopener">Open CFM Dashboard</a></p>
                <p class="isSelectedEnd">Thank you for your leadership, commitment, and willingness to invest in the success of our newest associates.</p>
                <p class="isSelectedEnd">Together, we build stronger leaders, stronger teams, and a stronger organization.</p>
                <p class="isSelectedEnd">Best regards,</p>
                <p>{{ app_name }}<br>Training, Mentorship &amp; Performance Tracking Platform</p>',
            'token_values' => NULL,
            'is_active' => true,
          ],
          2 => 
          [
            'key' => 'cfm_assignment_confirmed_cfm',
            'name' => 'CFM Assignment Confirmed (CFM)',
            'subject' => '{{ member_name }} is now your active trainee',
            'body' => '<p class="isSelectedEnd">Hello {{ cfm_name }},</p>
                <p class="isSelectedEnd">Thank you for confirming your mentorship assignment.</p>
                <p class="isSelectedEnd">{{ member_name }} has now been successfully added to your trainee roster in {{ app_name }} and is ready to begin their Field Apprenticeship Program (FAP) journey under your guidance.</p>
                <h3>Next Recommended Steps</h3>
                <p class="isSelectedEnd">To help your trainee get off to a strong start:</p>
                <ul data-spread="false">
                <li>Send a welcome message and introduce yourself</li>
                <li>Review their profile and onboarding status</li>
                <li>Schedule an introductory meeting or orientation call</li>
                <li>Discuss their goals, expectations, and next steps</li>
                <li>Begin monitoring their training, licensing, and apprenticeship progress</li>
                </ul>
                <h3>Access Your Trainee Profile</h3>
                <p class="isSelectedEnd">Open your CFM Portal to review the trainee\'s information and initiate your first contact:</p>
                <p class="isSelectedEnd"><a href="{{%20base_url%20}}/cfm/portal" target="_blank" rel="noopener">{{ base_url }}/cfm/portal</a></p>
                <h3>Your Impact Matters</h3>
                <p class="isSelectedEnd">As a Certified Field Mentor, your leadership plays a critical role in helping new associates build confidence, develop skills, complete onboarding requirements, and achieve long-term success within the organization.</p>
                <p class="isSelectedEnd">Thank you for your commitment to mentoring and supporting the next generation of leaders.</p>
                <p class="isSelectedEnd">Best regards,</p>
                <p>{{ app_name }}<br>Training, Mentorship &amp; Performance Tracking Platform</p>',
            'token_values' => NULL,
            'is_active' => true,
          ],
          3 => 
          [
            'key' => 'cfm_assignment_confirmed_member',
            'name' => 'CFM Assignment Confirmed (Member)',
            'subject' => 'Your Certified Field Mentor is {{ cfm_name }} – Your Apprenticeship Journey Begins',
            'body' => '<p class="isSelectedEnd">Hello {{ member_name }},</p>
                <p class="isSelectedEnd">Great news! A Certified Field Mentor has been assigned to support you throughout your onboarding and development journey.</p>
                <h3>Your Certified Field Mentor</h3>
                <p class="isSelectedEnd">Mentor: <strong>{{ cfm_name }}</strong></p>
                <p class="isSelectedEnd">Your mentor will serve as your guide and resource as you progress through the Field Apprenticeship Program (FAP), licensing requirements, training activities, and early field development milestones.</p>
                <h3>What Happens Next?</h3>
                <p class="isSelectedEnd">Over the coming days, your mentor will reach out to:</p>
                <ul data-spread="false">
                <li>Welcome you to the team</li>
                <li>Introduce the Field Apprenticeship Program</li>
                <li>Review your onboarding progress</li>
                <li>Discuss licensing and training requirements</li>
                <li>Help you develop a success plan</li>
                <li>Answer questions and provide ongoing support</li>
                </ul>
                <h3>Continue Your Progress</h3>
                <p class="isSelectedEnd">Sign in to your dashboard to complete onboarding tasks, track licensing progress, access training resources, and stay up to date with your apprenticeship activities:</p>
                <p class="isSelectedEnd"><a href="{{%20base_url%20}}/dashboard">{{ base_url }}/dashboard</a></p>
                <h3>Your Success Starts Here</h3>
                <p class="isSelectedEnd">The most successful associates stay engaged, complete their assigned activities promptly, and maintain regular communication with their mentor. We encourage you to take full advantage of the support, training, and resources available to you.</p>
                <p class="isSelectedEnd">We are excited to be part of your journey and look forward to seeing your growth and success.</p>
                <p class="isSelectedEnd">Welcome aboard, and best wishes as you begin this exciting new chapter!</p>
                <p class="isSelectedEnd">Warm regards,</p>
                <p>{{ app_name }}<br>Training, Mentorship &amp; Performance Tracking Platform</p>',
            'token_values' => NULL,
            'is_active' => true,
          ],
          4 => 
          [
            'key' => 'cfm_assignment_confirmed_sponsor',
            'name' => 'CFM Assignment Confirmed (Sponsor)',
            'subject' => '{{ member_name }} now has a Certified Field Mentor',
            'body' => '<p class="isSelectedEnd">Hello {{ sponsor_name }},</p>
                <p class="isSelectedEnd">We are pleased to inform you that <strong>{{ cfm_name }}</strong> has officially confirmed their mentorship assignment for <strong>{{ member_name }}</strong>.</p>
                <p class="isSelectedEnd">As a result, the associate\'s <strong>Field Apprenticeship Program (FAP)</strong> has now been activated within {{ app_name }}, and their onboarding, training, licensing, and field development journey is officially underway.</p>
                <h3>What\'s Happening Now?</h3>
                <p class="isSelectedEnd">Your recruit will now begin working with their Certified Field Mentor to:</p>
                <ul data-spread="false">
                <li>Complete onboarding requirements</li>
                <li>Progress through licensing milestones</li>
                <li>Participate in training and field development activities</li>
                <li>Track apprenticeship progress and achievements</li>
                <li>Build the skills and confidence needed for long-term success</li>
                </ul>
                <h3>Stay Involved in Their Success</h3>
                <p class="isSelectedEnd">As the sponsor, your encouragement and support remain important throughout the associate\'s development journey. We encourage you to regularly monitor their progress, celebrate milestones, and stay engaged as they advance through the program.</p>
                <h3>View Progress Dashboard</h3>
                <p class="isSelectedEnd">You can track your recruit\'s progress, completed activities, mentorship status, and upcoming milestones directly from your dashboard:</p>
                <p class="isSelectedEnd"><a href="{{%20base_url%20}}/dashboard">{{ base_url }}/dashboard</a></p>
                <p class="isSelectedEnd">Thank you for your leadership and commitment to helping new associates succeed. Together, we are building stronger teams, stronger leaders, and a stronger organization.</p>
                <p class="isSelectedEnd">Best regards,</p>
                <p>{{ app_name }}<br>Training, Mentorship &amp; Performance Tracking Platform</p>',
            'token_values' => NULL,
            'is_active' => true,
          ],
          5 => 
          [
            'key' => 'cfm_first_contact_member',
            'name' => 'CFM First Contact (Member)',
            'subject' => 'Welcome to Field Apprenticeship with {{ cfm_name }}',
            'body' => '<p class="isSelectedEnd">Hello {{ member_name }},</p>
                                <p class="isSelectedEnd">Welcome to the team!</p>
                                <p class="isSelectedEnd">My name is <strong>{{ cfm_name }}</strong>, and I have been assigned as your <strong>Certified Field Mentor (CFM)</strong> through {{ app_name }}. I am excited to work alongside you and support you as you begin your journey through the Field Apprenticeship Program.</p>
                                <p class="isSelectedEnd">As your mentor, I will help guide you through:</p>
                                <ul data-spread="false">
                                <li>Onboarding and orientation activities</li>
                                <li>Licensing and compliance milestones</li>
                                <li>Training and skill development</li>
                                <li>Field apprenticeship requirements</li>
                                <li>Business-building and professional growth opportunities</li>
                                <li>Goal setting, accountability, and progress tracking</li>
                                </ul>
                                <h3>Your Next Steps</h3>
                                <p class="isSelectedEnd">Please sign in to your dashboard to review your assigned tasks, training materials, and onboarding activities:</p>
                                <p class="isSelectedEnd">{{ dashboard_url }}</p>
                                <p class="isSelectedEnd">I encourage you to begin completing your onboarding requirements as soon as possible so we can build momentum toward your goals.</p>
                                <h3>Let\'s Get Connected</h3>
                                <p class="isSelectedEnd">Over the next few days, I will be reaching out to introduce myself, answer any questions you may have, and schedule our first mentoring session. During that meeting, we will discuss your goals, review your progress plan, and outline the steps needed to help you succeed.</p>
                                <p class="isSelectedEnd">Remember, you are not expected to do this alone. My role is to support, encourage, and guide you throughout the process.</p>
                                <p class="isSelectedEnd">I look forward to getting to know you and helping you achieve success.</p>
                                <p class="isSelectedEnd">Talk to you soon!</p>
                                <p class="isSelectedEnd">Warm regards,</p>
                                <p><strong>{{ cfm_name }}</strong><br>Certified Field Mentor<br>{{ app_name }}<br>Training, Mentorship &amp; Performance Tracking Platform</p>',
            'token_values' => NULL,
            'is_active' => true,
          ],
          6 => 
          [
            'key' => 'member_invitation',
            'name' => 'Member Invitation',
            'subject' => 'You are invited to join {{ app_name }}',
            'body' => '<p class="isSelectedEnd">Hello,</p>
                        <p class="isSelectedEnd"><strong>{{ sponsor_name }}</strong> has invited you to join <strong>{{ app_name }}</strong>, our private team portal designed to support your onboarding, training, licensing progress, mentorship, and professional development.</p>
                        <p class="isSelectedEnd">This platform will help you stay organized and connected as you begin your journey by providing access to:</p>
                        <ul data-spread="false">
                        <li>Onboarding and orientation activities</li>
                        <li>Licensing and compliance tracking</li>
                        <li>Field Apprenticeship Program (FAP) progress</li>
                        <li>Training resources and assessments</li>
                        <li>Mentor communication and support</li>
                        <li>Team updates and important announcements</li>
                        <li>Career development and rank advancement tracking</li>
                        </ul>
                        <h3>Activate Your Account</h3>
                        <p class="isSelectedEnd">To get started, simply click the secure registration link below:</p>
                        <p class="isSelectedEnd"><a href="{{ registration_link }}">{{ registration_link }}</a></p>
                        <h3>Registration Code Included</h3>
                        <p class="isSelectedEnd">Your unique registration code has already been embedded in the registration link and will be automatically pre-filled on the registration form for your convenience. No manual entry is required.</p>
                        <h3>Important Information</h3>
                        <ul data-spread="false">
                        <li>This invitation is intended exclusively for you.</li>
                        <li>The registration link can only be used once.</li>
                        <li>Your invitation and registration code will expire <strong>two (2) weeks</strong> from the date this email was sent.</li>
                        <li>We encourage you to complete your registration as soon as possible to avoid expiration.</li>
                        </ul>
                        <h3>Welcome to the Team</h3>
                        <p class="isSelectedEnd">We\'re excited to have you join our growing community. Once your account has been activated, you\'ll gain access to the tools, training, mentorship, and resources designed to help you build a strong foundation for success.</p>
                        <p class="isSelectedEnd">If you have any questions during the registration process, please reach out to <strong>{{ sponsor_name }}</strong> for assistance.</p>
                        <p class="isSelectedEnd">We look forward to welcoming you and supporting your journey.</p>
                        <p class="isSelectedEnd">Warm regards,</p>
                        <p class="isSelectedEnd"><strong>{{ sponsor_name }}</strong><br>Inviting Sponsor</p>
                        <p>{{ app_name }}<br>Training, Mentorship &amp; Performance Tracking Platform</p>',
            'token_values' => NULL,
            'is_active' => true,
          ],
          7 => 
          [
            'key' => 'new_member_email_verification',
            'name' => 'New Member Email Verification',
            'subject' => 'Verify your email for {{ app_name }}',
            'body' => '<p class="isSelectedEnd">Hello {{ member_name }},</p>
                                <p class="isSelectedEnd">Thank you for registering with <strong>{{ app_name }}</strong>. To help keep your account secure, please confirm that <strong>{{ member_email }}</strong> is your correct email address.</p>
                                <h3>Verify Your Email Address</h3>
                                <p class="isSelectedEnd">Click the link below to verify your email and complete this important account step:</p>
                                <p class="isSelectedEnd"><a href="{{ verification_url }}" target="_blank" rel="noopener">{{ verification_url }}</a></p>
                        <p class="isSelectedEnd">This verification link expires in {{ verification_expires_hours }} hours.</p>
                        <h3>After You Verify</h3>
                        <p class="isSelectedEnd">Once your email is verified, return to {{ app_name }} and sign in with the email and password you created during registration.</p>
                        <p class="isSelectedEnd">{{ dashboard_url }}</p>
                        <h3>Did Not Register?</h3>
                                <p class="isSelectedEnd">If you did not create an {{ app_name }} account, you can safely ignore this message. No changes will be made unless you use the verification link above.</p>
                                <p class="isSelectedEnd">If you need help, contact <strong>{{ sponsor_name }}</strong>, your sponsor.</p>
                                <p class="isSelectedEnd">Warm regards,</p>
                                <p><strong>The {{ app_name }} Team</strong><br>Training, Mentorship &amp; Performance Tracking Platform</p>',
            'token_values' => NULL,
            'is_active' => true,
          ],
          8 => 
          [
            'key' => 'new_member_welcome',
            'name' => 'New Member Welcome',
            'subject' => 'Welcome to {{ app_name }}, {{ member_name }}',
            'body' => '<p class="isSelectedEnd">Hello {{ member_name }},</p>
                                <p class="isSelectedEnd">Welcome to <strong>{{ app_name }}</strong>!</p>
                                <p class="isSelectedEnd">We\'re excited to have you join our team and begin this new chapter of growth, learning, and opportunity. Your account has been successfully created, and <strong>{{ sponsor_name }}</strong> has been designated as your sponsor to help support you throughout your journey.</p>
                                <h3>Your Account Is Ready</h3>
                                <p class="isSelectedEnd">You can now sign in to access your personal dashboard and begin working on your onboarding and development activities:</p>
                                <p class="isSelectedEnd">{{ dashboard_url }}</p>
                                <h3>What You\'ll Find Inside</h3>
                                <p class="isSelectedEnd">Your dashboard provides access to:</p>
                                <ul data-spread="false">
                                <li>Onboarding and orientation activities</li>
                                <li>Licensing and compliance tracking</li>
                                <li>Field Apprenticeship Program (FAP) milestones</li>
                                <li>Training resources and assessments</li>
                                <li>Mentor and team communications</li>
                                <li>Progress tracking and performance monitoring</li>
                                <li>Career development and rank advancement opportunities</li>
                                </ul>
                                <h3>What Happens Next?</h3>
                                <p class="isSelectedEnd">As you begin your journey, you will be assigned a Certified Field Mentor (CFM) who will help guide you through your onboarding, training, licensing, and field development activities. Be sure to regularly check your dashboard for updates, tasks, announcements, and important next steps.</p>
                                <h3>Your Success Matters</h3>
                                <p class="isSelectedEnd">The most successful associates stay engaged, complete their assigned activities promptly, and take full advantage of the training, mentorship, and support available to them. We encourage you to get started today and build momentum toward your goals.</p>
                                <p class="isSelectedEnd">We\'re honored to have you as part of the team and look forward to supporting your success every step of the way.</p>
                                <p class="isSelectedEnd">Welcome aboard!</p>
                                <p class="isSelectedEnd">Warm regards,</p>
                                <p><strong>The {{ app_name }} Team</strong><br>Training, Mentorship &amp; Performance Tracking Platform</p>',
            'token_values' => NULL,
            'is_active' => true,
          ],
          9 => 
          [
            'key' => 'sponsor_new_member_welcome',
            'name' => 'Sponsor New Member Welcome',
            'subject' => '{{ member_name }} joined {{ app_name }} under your sponsorship',
            'body' => '<p class="isSelectedEnd">Hello {{ sponsor_name }},</p>
                                <p class="isSelectedEnd">Great news! Your recruit, <strong>{{ member_name }}</strong> ({{ member_email }}), has successfully completed their registration and is now officially connected to you as their sponsor within <strong>{{ app_name }}</strong>.</p>
                                <h3>New Associate Details</h3>
                                <p class="isSelectedEnd"><strong>Name:</strong> {{ member_name }}<br><strong>Email:</strong> {{ member_email }}</p>
                                <h3>Next Steps</h3>
                                <p class="isSelectedEnd">As their sponsor, you play an important role in helping them get started successfully. We encourage you to:</p>
                                <ul data-spread="false">
                                <li>Welcome them to the team</li>
                                <li>Reach out and introduce yourself</li>
                                <li>Help them understand the onboarding process</li>
                                <li>Encourage them to complete their initial tasks and training</li>
                                <li>Stay engaged throughout their development journey</li>
                                </ul>
                                <h3>Review Their Profile</h3>
                                <p class="isSelectedEnd">You can view their profile and monitor their progress from your dashboard:</p>
                                <p class="isSelectedEnd">{{ profile_url }}</p>
                                <h3>Certified Field Mentor Assignment</h3>
                                <p class="isSelectedEnd">To ensure your recruit receives the guidance and support they need, <strong>{{ agency_owner_name }}</strong> will be assigning a Certified Field Mentor (CFM) to oversee their Field Apprenticeship Program (FAP), onboarding activities, licensing progress, and early development milestones.</p>
                                <p class="isSelectedEnd">If you have a preferred mentor in mind, you are welcome to recommend a Certified Field Mentor and coordinate with <strong>{{ agency_owner_name }}</strong> regarding the assignment.</p>
                                <h3>Help Set the Foundation for Success</h3>
                                <p class="isSelectedEnd">New associates are most successful when sponsors remain actively involved during the early stages of onboarding. Your encouragement, leadership, and support can make a meaningful difference in helping your recruit build momentum and confidence.</p>
                                <p class="isSelectedEnd">Thank you for your commitment to developing future leaders and growing a strong, successful team.</p>
                                <p class="isSelectedEnd">Warm regards,</p>
                                <p><strong>{{ app_name }} Team</strong><br>Training, Mentorship &amp; Performance Tracking Platform</p>',
            'token_values' => NULL,
            'is_active' => true,
          ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['key' => $template['key']],
                [
                    'name' => $template['name'],
                    'subject' => $template['subject'],
                    'body' => $template['body'],
                    'token_values' => $template['token_values'] ?? null,
                    'is_active' => $template['is_active'],
                ]
            );
        }
    }
}
