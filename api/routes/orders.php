<?php
return [
    'PUT' => [
        'v1/orders/setPaid' => [
            'description'   => 'Подтверждение оплаты заказа(Юр. лица)',
            'active'        => true,
            'controller'    => 'Local\Api\Controllers\V1\Orders@setPaid',
            'contentType'   => 'application/json',
            'security'      => [
                'auth'  => [
                    'required' => true, // true || false
                    'type'     => 'token',
                ],
                'token' => [
                    'whitelist' => [
                    ]
                ]
            ],
            'parameters'    => [
                'account_number'     => [
                    'required'    => true,
                    'type'        => 'string',
                    'description' => 'Номер заказа'
                ],
            ],
            'documentation' => [
                'exclude' => [
                    'admin'  => false,
                    'public' => false,
                ]
            ]
        ]
    ]
];
