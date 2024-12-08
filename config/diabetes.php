<?php

return [
    'bloodGlucose' => [
        'targets' => [
            'veryHigh' => 250,
            'high' => 180,
            'range' => 140,
            'tightRange' => 70,
            'low' => 54,
            'veryLow' => 0
        ],
    ],
    'variation' => [
        'targets' => [
            'good' => 34,
            'high' => 36
        ],
    ],
    'agp' => [
        'insulin' => [
            'minutesBetweenInjections' => 60 * 4
        ]
    ],
    'notes' => [
        'filter' => ['PEN']
    ],
    'treatments' => [
        'relativeAxisHeight' => 1/3
    ],
    'lunchTypes' => [
        '0700' => 'night',
        '1100' => 'breakfast',
        '1500' => 'lunch',
        '1800' => 'afternoonsnack',
        '2200' => 'diner',
        '2359' => 'night'
    ]
];
