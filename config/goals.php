<?php

return [

  'hierarchy_levels' => [
      'vision' => 'Vision',
      'annual' => 'Annual',
      'quarterly' => 'Quarterly',
      'monthly' => 'Monthly',
      'weekly' => 'Weekly',
      'daily' => 'Daily',
  ],

  'measurement_types' => [
      'number' => 'Number',
      'currency' => 'Currency',
      'percentage' => 'Percentage',
      'completion' => 'Completion',
  ],

  'statuses' => [
      'draft' => 'Draft',
      'active' => 'Active',
      'completed' => 'Completed',
      'off_track' => 'Off Track',
      'paused' => 'Paused',
      'cancelled' => 'Cancelled',
  ],

  'coach_roles' => [
      'mentor' => 'Mentor',
      'sponsor' => 'Sponsor',
      'cfm' => 'CFM',
      'agency_owner' => 'Agency Owner',
  ],

  'badge_levels' => [
      'bronze' => 'Bronze',
      'silver' => 'Silver',
      'gold' => 'Gold',
      'platinum' => 'Platinum',
      'diamond' => 'Diamond',
  ],

  'views' => [
      'list' => 'List',
      'cards' => 'Cards',
      'timeline' => 'Timeline',
      'progress' => 'Progress',
      'calendar' => 'Calendar',
      'kanban' => 'Kanban',
  ],

  /*
  |--------------------------------------------------------------------------
  | Automated metric keys — resolved by GoalMetricResolver
  |--------------------------------------------------------------------------
  */
  'metrics' => [
      // Recruiting
      'recruits' => ['label' => 'Recruits', 'category' => 'recruiting', 'source' => 'prospects'],
      'invitations_sent' => ['label' => 'Invitations sent', 'category' => 'recruiting', 'source' => 'prospects'],
      'presentations' => ['label' => 'Presentations', 'category' => 'recruiting', 'source' => 'prospects'],
      'registrations' => ['label' => 'Registrations', 'category' => 'recruiting', 'source' => 'prospects'],
      'direct_recruits' => ['label' => 'Direct recruits', 'category' => 'recruiting', 'source' => 'downline'],

      // Production
      'annual_premium' => ['label' => 'Annual premium', 'category' => 'production', 'source' => 'production'],
      'monthly_premium' => ['label' => 'Monthly premium', 'category' => 'production', 'source' => 'production'],

      // Prospecting
      'contacts' => ['label' => 'Contacts', 'category' => 'prospecting', 'source' => 'prospects'],
      'new_prospects' => ['label' => 'New prospects', 'category' => 'prospecting', 'source' => 'prospects'],
      'appointments' => ['label' => 'Appointments', 'category' => 'prospecting', 'source' => 'prospects'],
      'applications' => ['label' => 'Applications', 'category' => 'prospecting', 'source' => 'prospects'],
      'followups_completed' => ['label' => 'Follow-ups completed', 'category' => 'prospecting', 'source' => 'prospects'],
      'presentation_invitations' => ['label' => 'Presentation invitations', 'category' => 'prospecting', 'source' => 'prospects'],

      // Financial review / FNA
      'fna_completed' => ['label' => 'FNAs completed', 'category' => 'financial_review', 'source' => 'fna'],
      'fna_approved' => ['label' => 'FNAs approved', 'category' => 'financial_review', 'source' => 'fna'],
      'fna_started' => ['label' => 'FNAs started', 'category' => 'financial_review', 'source' => 'prospects'],

      // FAP
      'fap_completion' => ['label' => 'FAP completion %', 'category' => 'fap', 'source' => 'fap', 'measurement_type' => 'percentage'],

      // Licensing
      'licensing_completion' => ['label' => 'Licensing completion %', 'category' => 'licensing', 'source' => 'licensing', 'measurement_type' => 'percentage'],

      // CFM development
      'trainees_assigned' => ['label' => 'Trainees assigned', 'category' => 'cfm_development', 'source' => 'cfm'],
      'mentoring_sessions' => ['label' => 'Mentoring sessions', 'category' => 'cfm_development', 'source' => 'calendar'],

      // Leadership
      'team_recruits' => ['label' => 'Team recruits', 'category' => 'leadership', 'source' => 'downline'],
      'team_production' => ['label' => 'Team production', 'category' => 'leadership', 'source' => 'production'],

      // Training
      'training_completion' => ['label' => 'Training completion %', 'category' => 'training', 'source' => 'training', 'measurement_type' => 'percentage'],
      'cfm_training_completion' => ['label' => 'CFM training %', 'category' => 'training', 'source' => 'cfm_training', 'measurement_type' => 'percentage'],

      // Rank
      'rank_requirements' => ['label' => 'Rank requirements %', 'category' => 'rank_advancement', 'source' => 'rank', 'measurement_type' => 'percentage'],

      // Income (manual until payroll integration)
      'monthly_income' => ['label' => 'Monthly income', 'category' => 'income', 'source' => 'manual'],
      'annual_income' => ['label' => 'Annual income', 'category' => 'income', 'source' => 'manual'],
  ],

  'ai_coaching' => [
      'enabled' => true,
      'thresholds' => [
          'behind_percent' => 80,
          'critical_days_remaining' => 7,
      ],
  ],

  /*
  |--------------------------------------------------------------------------
  | Badge award criteria — evaluated by GoalAchievementService
  |--------------------------------------------------------------------------
  */
  'badge_criteria' => [
      'first_recruit' => ['type' => 'metric', 'metric' => 'direct_recruits', 'min' => 1],
      'first_policy' => ['type' => 'production_entries', 'min' => 1],
      'first_licensed_associate' => ['type' => 'metric', 'metric' => 'licensing_completion', 'min' => 100],
      'fap_graduate' => ['type' => 'metric', 'metric' => 'fap_completion', 'min' => 100],
      'top_producer' => ['type' => 'metric', 'metric' => 'annual_premium', 'min' => 100000],
      'leadership_builder' => ['type' => 'metric', 'metric' => 'team_recruits', 'min' => 5],
  ],

];
