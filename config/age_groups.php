<?php

return [
    // Age bands based on date of birth (as of today)
    'children_service' => [
        'label' => 'Children Service',
        'min_age' => 0,
        'max_age' => 12,
        'family_name' => 'Children Service',
        'family_names' => ['Children Service', 'Children Service Ministry'],
    ],
    'junior_youth' => [
        'label' => 'Junior Youth',
        'min_age' => 13,
        'max_age' => 18,
        'family_name' => 'Junior Youth',
        'family_names' => ['Junior Youth', 'Junior Youth Ministry'],
    ],
    'adults' => [
        'label' => 'Adults',
        'min_age' => 19,
        'max_age' => null,
    ],
];
