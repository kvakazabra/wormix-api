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
        'missions' => [
            'tutorial_max_level' => 5,
            'delay' => 120,
            'max' => 5,
            'awards' => [

                'loose' => [
                    'money' => 5,
                    'experience' => 3
                ],

                'draw' => [
                    'money' => [
                        'low' => 20,
                        'medium' => 25,
                        'high' => 30
                    ],
                    'experience' => [
                        'low' => 4,
                        'medium' => 6,
                        'high' => 8
                    ]
                ],

                'win' => [
                    'money' => [
                        'low' => 30,
                        'medium' => 35,
                        'high' => 40
                    ],
                    'experience' => [
                        'low' => 8,
                        'medium' => 10,
                        'high' => 12
                    ]
                ]
            ],

            'buy' => [
                'money' => 100,
                'real_money' => 1,
            ]
        ],
        'buy' => [
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
        ],
        'race' => [
            'free_change_interval' => 43200, // in seconds, 12 hours by default
            'change_real_price' => 5,
            'skin_real_price' => 50,
        ],
        'search_keys_per_day' => 10,
        'next_level_award' => [
            'money' => 150,
            'real_money' => 0,
        ]
    ],
    'vk_balance' => 10
];
