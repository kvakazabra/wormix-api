<?php
return [
    'security' => [
        // Checks and clamps 'count' of weapons
        // Like finite weapons should not be -1
        // Or complex weapons cannot go beyond their maxLevel and etc.
        'validate_users_items' => false,
    ],
    'starter' => [
        'money' => 670000,
        'real_money' => 6700,
        'missions' => 10,
        'race' => 2,
        'weapons' => [1, 2, 4, 5, 9, 11, 17, 39, 49]
    ],
    'ids' => [
        'tutorial_missions' => [
            'min' => -1,
            'max' => -5
        ],
        'solo_missions' => [
            'min' => 0,
            'max' => 100,
        ],
        'coop_missions' => [
            'min' => 100,
            'max' => 200,
        ],
        'weapons' => [
            'min' => 0,
            'max' => 300,
            // Droppable weapons can be obtained exclusively in battle
            'droppable_min' => 10000,
            // Base for multi-purchase weapons
            'level_base' => -10
        ],
        'upgrades' => [
            'min' => 300,
            'max' => 1000,
        ],
        'hats' => [
            'min' => 1000,
            'max' => 2000
        ],
        'artifacts' => [
            'min' => 2000,
            'max' => 3000
        ],
        // Merge hats, artifacts ids
        'stuff' => [
            'min' => 1000,
            'max' => 3000
        ],
    ],
    'game' => [
        'race' => [
            'free_change_interval' => 43200, // in seconds, 12 hours by default
            'change_real_price' => 5,
            'skin_real_price' => 50,
        ],
        'max_level' => 30,
        'search_keys_per_day' => 10,
        'next_level_award' => [
            'money' => 150,
            'real_money' => 0,
        ],
        'missions' => [
            'delay' => 120,
            'max' => 5,

            'star_money_factor' => 4,
            'boss_opening_level' => 6,

            'types' => [
                2 => 'low',
                0 => 'medium',
                1 => 'high',
                3 => 'pvp',
            ],
            'result_types' => [
                1 => 'win',
                -1 => 'loss',
                0 => 'draw',
                -4 => 'loss',
            ],
            'awards' => [
                'experience' => [
                    'win' => [
                        'low' => 8,
                        'medium' => 10,
                        'high' => 12,
                        'pvp' => 0,
                    ],
                    'draw' => [
                        'low' => 4,
                        'medium' => 5,
                        'high' => 8,
                        'pvp' => 0,
                    ],
                    'loss' => [
                        'low' => 3,
                        'medium' => 3,
                        'high' => 3,
                        'pvp' => 0,
                    ]
                ],
                'money' => [
                    'win' => [
                        'low' => 30,
                        'medium' => 40,
                        'high' => 50,
                        'pvp' => 0,
                    ],
                    'draw' => [
                        'low' => 20,
                        'medium' => 25,
                        'high' => 30,
                        'pvp' => 0,
                    ],
                    'loss' => [
                        'low' => 5,
                        'medium' => 5,
                        'high' => 5,
                        'pvp' => 0,
                    ]
                ]
            ],
        ],
        'buy' => [
            'real_rate' => 100, // Money to real rate
            'boss_mission' => 10,
            'reset_stats' => [
                'money' => 300,
                'real_money' => 3,
            ],
            'teammate' => [
                'money' => 950,
                'real_money' => 10,
            ],
            'downgrade' => [
                'real_money' => 5,
                'return_rate' => 0.8,
            ],
        ]
    ],
    'vk_balance' => 10
];
