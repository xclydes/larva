<?php

return [
    'view' => [
        'app' => 'layouts.app',
        'form' => 'xclydes-larva::form'
    ],
    'edit' => [
        'footer' => [
            'cancel' => false
        ],
        'columns' => [
            'count' => 2,
            'max' => 12
        ],
        'wrapper' => [
            'open' => '<div class="container">',
            'close' => '</div>',
        ],
        'rows' =>[
            'wrapper' => [
                'open' => '<div class="row">',
                'close' => '</div><br />'
            ]
        ],
        'fields' => [
            'weight' => [
                '*' => 0,
                'textarea' => 999
            ]
        ],
        'container' => [
            'is_group' => true,
            'wrapper' => [
                'class' => 'row '
            ]
        ],
       'actions' => [
            'wrapper' => [
                'class' => 'col-md-6'
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
            'empty' => '<Empty>',
            'count' => 10
        ],
        'page' => [
            'size' => 10,
            'steps' => [10, 20, 50, 100]
        ]
    ],
    'kris' => [

    ]
];
