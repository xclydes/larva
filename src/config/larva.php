<?php

return [
    'view' => [
        'app' => 'app',
        'form' => 'xclydes-larva::form'
    ],
    'edit' => [
        'footer' => [
            'cancel' => false
        ],
        'columns' => [
            'count' => 2
        ],
        'wrapper' => [
            'open' => '<div class="container">',
            'close' => '</div>',
        ],
        'rows' =>[
            'wrapper' => [
                'open' => '<div class="row">',
                'close' => '</div>',
            ]
        ]
   ],
    'list' => [
        'wrapper' => [
            'open' => '<div class="container">',
            'close' => '</div>',
        ],
        'header' => [
            'new' => false
        ],
        'footer' => [
            'new' => false
        ],
        'row' => [
            'empty' => '<Empty>'
        ]
    ]
];
