<?php
return [
    'POST' => [
        'v1/tgGroup/addGroupInfo' => [
            'description'   => 'Добавление данных в аналитику',
            'active'        => true,
            'controller'    => 'Local\Api\Controllers\V1\TgGroup@addGroupInfo',
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
                    'required'    => false,
                    'type'        => 'string',
                    'description' => 'ID группы'
                ],
                'title'     => [
                    'required'    => false,
                    'type'        => 'string',
                    'description' => 'Название группы'
                ],
                'type'     => [
                    'required'    => false,
                    'type'        => 'string',
                    'description' => 'тип группы'
                ],
                'addedAt'     => [
                    'required'    => false,
                    'type'        => 'string',
                    'description' => 'Дата добавления'
                ],
                'addedBy'     => [
                    'required'    => false,
                    'type'        => 'string',
                    'description' => 'ID добавившего'
                ],
                'userTgId'     => [
                    'required'    => false,
                    'type'        => 'string',
                    'description' => 'ID пользователя в TG'
                ],
            ],
            'documentation' => [
                'exclude' => [
                    'admin'  => false,
                    'public' => false,
                ]
            ]
        ],

        'v1/tgGroup/delGroupInfo' => [
            'description'   => 'Удаление данных из аналитики',
            'active'        => true,
            'controller'    => 'Local\Api\Controllers\V1\TgGroup@delGroupInfo',
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
                    'required'    => false,
                    'type'        => 'string',
                    'description' => 'ID группы'
                ],
                'title'     => [
                    'required'    => false,
                    'type'        => 'string',
                    'description' => 'Название группы'
                ],
                'type'     => [
                    'required'    => false,
                    'type'        => 'string',
                    'description' => 'тип группы'
                ],
                'addedAt'     => [
                    'required'    => false,
                    'type'        => 'string',
                    'description' => 'Дата добавления'
                ],
                'addedBy'     => [
                    'required'    => false,
                    'type'        => 'string',
                    'description' => 'ID добавившего'
                ],
                'userTgId'     => [
                    'required'    => false,
                    'type'        => 'string',
                    'description' => 'ID пользователя в TG'
                ],
            ],
            'documentation' => [
                'exclude' => [
                    'admin'  => false,
                    'public' => false,
                ]
            ]
        ],

        'v1/tgGroup/getGroupInfo' => [
            'description'   => 'Получение данных из аналитики',
            'active'        => true,
            'controller'    => 'Local\Api\Controllers\V1\TgGroup@getGroupInfo',
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
                    'required'    => false,
                    'type'        => 'string',
                    'description' => 'ID группы'
                ],
                'title'     => [
                    'required'    => false,
                    'type'        => 'string',
                    'description' => 'Название группы'
                ],
                'type'     => [
                    'required'    => false,
                    'type'        => 'string',
                    'description' => 'тип группы'
                ],
                'addedAt'     => [
                    'required'    => false,
                    'type'        => 'string',
                    'description' => 'Дата добавления'
                ],
                'addedBy'     => [
                    'required'    => false,
                    'type'        => 'string',
                    'description' => 'ID добавившего'
                ],
                'userTgId'     => [
                    'required'    => false,
                    'type'        => 'string',
                    'description' => 'ID пользователя в TG'
                ],
            ],
            'documentation' => [
                'exclude' => [
                    'admin'  => false,
                    'public' => false,
                ]
            ]
        ],

        'v1/tgGroup/addWidget' => [
            'description'   => 'Добавить виджет',
            'active'        => true,
            'controller'    => 'Local\Api\Controllers\V1\TgGroup@addWidget',
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
                'ticket_id'     => [
                    'required'    => false,
                    'type'        => 'string',
                    'description' => 'ID мероприятия (Битрикс)'
                ],
                'name'     => [
                    'required'    => false,
                    'type'        => 'string',
                    'description' => 'Название виджета'
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