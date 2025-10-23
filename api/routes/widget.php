<?php
return [
    'POST' => [
        'v1/widget/addAnaliticsView' => [
            'description'   => 'Добавление просмотра виджету в аналитику',
            'active'        => true,
            'controller'    => 'Local\Api\Controllers\V1\Widget@addAnaliticsView',
            'contentType'   => 'application/json',
            'security'      => [
                'auth'  => [
                    'required' => false, // true || false
                    'type'     => 'token',
                ],
                'token' => [
                    'whitelist' => [
                        // '408f4f2e-d5a6e4a7-06930a16-8301b343'
                    ]
                ]
            ],
            'parameters'    => [
                'id'     => [
                    'required'    => true,
                    'type'        => 'string',
                    'description' => 'UUID виджета'
                ],
            ],
            'documentation' => [
                'exclude' => [
                    'admin'  => false,
                    'public' => false,
                ]
            ]
        ],

    ],
];