<?php

return [

    /*
    | Default off so existing portals stay free until an admin enables billing.
    */
    'enabled' => false,

    /*
    | Annual invoices bill this many months of the rank price in one charge.
    */
    'annual_months' => 12,

    /*
    | Rank codes with no price stay free even when licensed (TA, ASC).
    | Unlicensed associates are always free regardless of rank.
    */
    'price_groups' => [
        [
            'key' => 'sa',
            'label' => 'Senior Associate (SA)',
            'codes' => ['SA'],
            'amount' => 9.95,
        ],
        [
            'key' => 'mb',
            'label' => 'Managing Broker (MB)',
            'codes' => ['MB'],
            'amount' => 14.95,
        ],
        [
            'key' => 'smb',
            'label' => 'Senior Managing Broker (SMB)',
            'codes' => ['SMB'],
            'amount' => 19.95,
        ],
        [
            'key' => 'avp_svp',
            'label' => 'AVP & SVP',
            'codes' => ['AVP', 'SVP'],
            'amount' => 24.95,
        ],
        [
            'key' => 'evp',
            'label' => 'Executive Vice President (EVP)',
            'codes' => ['EVP'],
            'amount' => 29.95,
        ],
        [
            'key' => 'evc',
            'label' => 'Executive Vice Chairman (EVC)',
            'codes' => ['EVC'],
            'amount' => 34.95,
        ],
        [
            'key' => 'sevc',
            'label' => 'Senior Executive Vice Chairman (SEVC)',
            'codes' => ['SEVC'],
            'amount' => 39.95,
        ],
        [
            'key' => 'nevc',
            'label' => 'National Executive Vice Chairman (NEVC)',
            'codes' => ['NEVC'],
            'amount' => 49.95,
        ],
    ],

];
