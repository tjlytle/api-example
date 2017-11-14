<?php
return [
    'driver' => [
        'class' => \WorldSpeakers\ReadOnly\Source::class,
        'args' => [
            __DIR__ . '/data.json'
        ]
    ],
    'tokens' => [
        'really_hard_to_guess'
    ],
    'default' => [
        'paging' => [
            'size' => 10,
            'page' => 0
        ]
    ]
];