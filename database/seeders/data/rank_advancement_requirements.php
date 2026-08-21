<?php

/**
 * Greatway rank advancement requirements.
 *
 * Keyed by target rank code (the rank a member is advancing toward).
 * Source: docs/greatway-promotion-compensation-guidelines.md
 */
return [
    'ASC' => [
        [
            'title' => 'Complete new-agent onboarding as a Training Associate',
            'description' => 'Finish assigned onboarding, licensing, and Field Apprenticeship milestones while coded as a Training Associate.',
            'category' => 'training',
            'sort_order' => 10,
        ],
        [
            'title' => 'Recruit 3 New Business Associates (NBAs)',
            'description' => 'Qualify for Associate with 3 registered New Business Associates.',
            'category' => 'recruiting',
            'sort_order' => 20,
        ],
        [
            'title' => 'Maintain active licensing in good standing',
            'description' => 'Keep required licenses, appointments, and compliance items current.',
            'category' => 'licensing',
            'sort_order' => 30,
        ],
        [
            'title' => 'Demonstrate consistent field activity',
            'description' => 'Show ongoing client service and personal production activity as a new agent.',
            'category' => 'field_activity',
            'sort_order' => 40,
        ],
    ],
    'SA' => [
        [
            'title' => 'Achieve $10,000 Lifetime Income (LTI)',
            'description' => 'Meet the Senior Associate income requirement of $10,000 LTI.',
            'category' => 'production',
            'sort_order' => 10,
        ],
        [
            'title' => 'Sustain Associate-level personal production',
            'description' => 'Remain active as an Associate while accumulating Lifetime Income toward Senior Associate.',
            'category' => 'production',
            'sort_order' => 20,
        ],
        [
            'title' => 'Maintain licensing and compliance in good standing',
            'description' => 'Licenses, carrier appointments, and compliance attestations must remain current.',
            'category' => 'licensing',
            'sort_order' => 30,
        ],
    ],
    'MB' => [
        [
            'title' => 'Achieve $40,000 Lifetime Income (LTI)',
            'description' => 'Meet the Managing Broker income requirement of $40,000 LTI.',
            'category' => 'production',
            'sort_order' => 10,
        ],
        [
            'title' => 'Build a developing personal business',
            'description' => 'Show consistent client service and personal production as a Senior Associate advancing to Managing Broker.',
            'category' => 'production',
            'sort_order' => 20,
        ],
        [
            'title' => 'Maintain licensing and compliance in good standing',
            'description' => 'Licenses, carrier appointments, and compliance attestations must remain current.',
            'category' => 'licensing',
            'sort_order' => 30,
        ],
    ],
    'SMB' => [
        [
            'title' => 'Achieve $80,000 Lifetime Income (LTI)',
            'description' => 'Meet the Senior Managing Broker income requirement of $80,000 LTI.',
            'category' => 'production',
            'sort_order' => 10,
        ],
        [
            'title' => 'Sustain Managing Broker production standards',
            'description' => 'Keep personal production and persistency on track while advancing to Senior Managing Broker.',
            'category' => 'production',
            'sort_order' => 20,
        ],
        [
            'title' => 'Maintain licensing and compliance in good standing',
            'description' => 'Licenses, carrier appointments, and compliance attestations must remain current.',
            'category' => 'licensing',
            'sort_order' => 30,
        ],
    ],
    'AVP' => [
        [
            'title' => 'Achieve $100,000 12-Month Income (12MI)',
            'description' => 'Meet the Associate Vice President income requirement of $100,000 12MI. This is the top Producer promotion.',
            'category' => 'production',
            'sort_order' => 10,
        ],
        [
            'title' => 'Sustain Senior Managing Broker performance',
            'description' => 'Demonstrate consistent personal production while qualifying for AVP.',
            'category' => 'production',
            'sort_order' => 20,
        ],
        [
            'title' => 'Maintain licensing and compliance in good standing',
            'description' => 'Licenses, carrier appointments, and compliance attestations must remain current.',
            'category' => 'licensing',
            'sort_order' => 30,
        ],
    ],
    'SVP' => [
        [
            'title' => 'Achieve $60,000 12-Month Income (12MI)',
            'description' => 'Meet the Senior Vice President income requirement of $60,000 12MI.',
            'category' => 'production',
            'sort_order' => 10,
        ],
        [
            'title' => 'Develop 3 Managing Broker legs or 1 SVP',
            'description' => 'Qualify as a Field Leader with 3 Managing Broker legs, or 1 SVP, in your downline.',
            'category' => 'recruiting',
            'sort_order' => 20,
        ],
        [
            'title' => 'Demonstrate Field Leader readiness',
            'description' => 'Show the ability to recruit, develop, and lead a baseshop of agents who are not yet Field Leaders.',
            'category' => 'leadership',
            'sort_order' => 30,
        ],
        [
            'title' => 'Maintain licensing and compliance in good standing',
            'description' => 'Licenses, carrier appointments, and compliance attestations must remain current.',
            'category' => 'licensing',
            'sort_order' => 40,
        ],
    ],
    'EVP' => [
        [
            'title' => 'Achieve $150,000 12-Month Income (12MI)',
            'description' => 'Meet the Executive Vice President income requirement of $150,000 12MI.',
            'category' => 'production',
            'sort_order' => 10,
        ],
        [
            'title' => 'Develop 3 SVP legs',
            'description' => 'Qualify with 3 Senior Vice President legs in your downline.',
            'category' => 'recruiting',
            'sort_order' => 20,
        ],
        [
            'title' => 'Sustain Field Leader / Superbase development',
            'description' => 'Continue developing first-generation Field Leaders while leading a baseshop.',
            'category' => 'leadership',
            'sort_order' => 30,
        ],
        [
            'title' => 'Maintain monthly settled-policy consistency (or exemption)',
            'description' => 'SVP and above must maintain 2 settled policies per month for generational override, unless exempt (10 years tenure and 5 years in a leadership role).',
            'category' => 'field_activity',
            'sort_order' => 40,
        ],
    ],
    'EVC' => [
        [
            'title' => 'Achieve $300,000 12-Month Income (12MI)',
            'description' => 'Meet the Executive Vice Chairman income requirement of $300,000 12MI.',
            'category' => 'production',
            'sort_order' => 10,
        ],
        [
            'title' => 'Develop 7 SVP legs',
            'description' => 'Qualify with 7 Senior Vice President legs in your downline.',
            'category' => 'recruiting',
            'sort_order' => 20,
        ],
        [
            'title' => 'Lead a Superbase of Field Leaders',
            'description' => 'Demonstrate first-generation Field Leader development and Super Team leadership.',
            'category' => 'leadership',
            'sort_order' => 30,
        ],
        [
            'title' => 'Maintain monthly settled-policy consistency (or exemption)',
            'description' => 'SVP and above must maintain 2 settled policies per month for generational override, unless exempt.',
            'category' => 'field_activity',
            'sort_order' => 40,
        ],
    ],
    'SEVC' => [
        [
            'title' => 'Achieve $500,000 12-Month Income (12MI)',
            'description' => 'Meet the Senior Executive Vice Chairman income requirement of $500,000 12MI.',
            'category' => 'production',
            'sort_order' => 10,
        ],
        [
            'title' => 'Develop 12 SVP legs',
            'description' => 'Qualify with 12 Senior Vice President legs in your downline.',
            'category' => 'recruiting',
            'sort_order' => 20,
        ],
        [
            'title' => 'Qualify Superbase NetFYC for revenue sharing',
            'description' => 'SEVC revenue-sharing pool requires $75,000 Superbase NetFYC and 40 Superbase NBAs.',
            'category' => 'production',
            'sort_order' => 30,
        ],
        [
            'title' => 'Maintain monthly settled-policy consistency (or exemption)',
            'description' => 'SVP and above must maintain 2 settled policies per month for generational override, unless exempt.',
            'category' => 'field_activity',
            'sort_order' => 40,
        ],
    ],
    'NEVC' => [
        [
            'title' => 'Achieve $800,000 12-Month Income (12MI)',
            'description' => 'Meet the National Executive Vice Chairman income requirement of $800,000 12MI.',
            'category' => 'production',
            'sort_order' => 10,
        ],
        [
            'title' => 'Develop 18 SVP legs',
            'description' => 'Qualify with 18 Senior Vice President legs in your downline.',
            'category' => 'recruiting',
            'sort_order' => 20,
        ],
        [
            'title' => 'Qualify Superbase NetFYC for national revenue sharing',
            'description' => 'NEVC revenue-sharing pool requires $100,000 Superbase NetFYC and 60 Superbase NBAs.',
            'category' => 'production',
            'sort_order' => 30,
        ],
        [
            'title' => 'National Field Leader stewardship',
            'description' => 'Lead a Super Team spanning baseshop, Superbase, and up to 6 generations of Field Leaders.',
            'category' => 'leadership',
            'sort_order' => 40,
        ],
        [
            'title' => 'Maintain monthly settled-policy consistency (or exemption)',
            'description' => 'SVP and above must maintain 2 settled policies per month for generational override, unless exempt.',
            'category' => 'field_activity',
            'sort_order' => 50,
        ],
    ],
];
