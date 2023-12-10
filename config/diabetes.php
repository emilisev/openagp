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
];
