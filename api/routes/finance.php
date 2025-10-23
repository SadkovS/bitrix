<?php
return [
    'POST' => [
        'v1/finance/financeListSetFields' => [
            'description'   => 'Обновление полей записи запроса на вывод',
            'active'        => true,
            'controller'    => 'Local\Api\Controllers\V1\Finance@financeListSetFields',
            'contentType'   => 'application/json',
            'security'      => [
                'auth'  => [
                    'required' => true, // true || false
                    'type'     => 'token',
                ],
                'token' => [
                    'whitelist' => [
                        //'434337b6-f12691d2-47bf6fb9-c040ae6b'
                    ]
                ]
            ],
            'parameters'    => [
                'id'     => [
                    'required'    => true,
                    'type'        => 'integer',
                    'description' => 'ID запроса на вывод'
                ],
                'fields'     => [
                    'required'    => true,
                    'type'        => 'array',
                    'description' => 'Поля запроса на вывод',
                    'parameters'    => [
                        'UF_STATUS' => [
                            'required'    => false,
                            'type'        => 'string',
                            'description' => 'Статус запроса на вывод'
                        ],
                        'UF_DATE_SUCCESS' => [
                            'required'    => false,
                            'type'        => 'string',
                            'description' => 'Дата подтверждения'
                        ],
                        'UF_COMMENT' => [
                            'required'    => false,
                            'type'        => 'string',
                            'description' => 'Комментарий'
                        ],
                    ],
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