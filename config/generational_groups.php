<?php

return [
    'Children Service' => [
        'min_age' => 0,
        'max_age' => 12,
        'gender' => 'any',
        'next' => 'Junior Youth',
    ],
    'Junior Youth' => [
        'min_age' => 13,
        'max_age' => 18,
        'gender' => 'any',
        'next' => 'YOUNG PEOPLES GUILD',
    ],
    'YOUNG PEOPLES GUILD' => [
        'min_age' => 18,
        'max_age' => 29,
        'gender' => 'any',
        'next' => 'YOUNG ADULTS FELLOWSHIP',
    ],
    'YOUNG ADULTS FELLOWSHIP' => [
        'min_age' => 30,
        'max_age' => 39,
        'gender' => 'any',
        'next_by_gender' => [
            'male' => 'MENS FELLOWSHIP',
            'female' => 'WOMENS FELLOWSHIP',
            'other' => 'MENS FELLOWSHIP',
        ],
    ],
    'MENS FELLOWSHIP' => [
        'min_age' => 40,
        'max_age' => null,
        'gender' => 'male',
        'next' => null,
    ],
    'WOMENS FELLOWSHIP' => [
        'min_age' => 40,
        'max_age' => null,
        'gender' => 'female',
        'next' => null,
    ],
];
