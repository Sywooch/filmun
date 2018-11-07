<?php
return [
    'guest' => [
        'type' => 1,
    ],
    'user' => [
        'type' => 1,
        'children' => [
            'guest',
        ],
    ],
    'admin' => [
        'type' => 1,
        'children' => [
            'user',
        ],
    ],
];
