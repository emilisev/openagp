<?php

return [
    'bloodGlucose' => [
            'targets' => [
            'veryHigh' => 250,
            'veryLow' => 54,
            'high' => 180,
            'low' => 70],
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
