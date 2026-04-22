<?php

return [
    'conflicts' => [
        'sla_hours' => [
            'lt_1h' => 1,
            'lt_4h' => 4,
            'lt_24h' => 24,
        ],
        'capacity' => [
            'elevated_total' => 10,
            'elevated_high_priority' => 1,
            'critical_total' => 30,
            'critical_high_priority' => 5,
            'critical_stale_over_24h' => 1,
        ],
    ],
];
