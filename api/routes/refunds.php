<?php
return [
    'PUT' => [
        'v1/refunds/approve' => [
            'description'   => 'Подтверждение/отклонение заявки на возврат',
            'active'        => true,
            'controller'    => 'Local\Api\Controllers\V1\Refunds@setApprove',
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
                'id'     => [
                    'required'    => true,
                    'type'        => 'integer',
                    'description' => 'ID Заявки на возврат'
                ],
                'approved'           => [
                    'required'    => true,
                    'type'        => 'bool',
                    'description' => 'Одобрение/Отказ'
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