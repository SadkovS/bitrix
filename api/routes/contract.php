<?php
return [
    'POST' => [
        'v1/contract/organizationSetFields' => [
            'description'   => 'Обновление полей организации',
            'active'        => true,
            'controller'    => 'Local\Api\Controllers\V1\Contract@organizationSetFields',
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
                    'description' => 'ID организации'
                ],
                'fields'     => [
                    'required'    => true,
                    'type'        => 'array',
                    'description' => 'Поля организации',
                    'parameters'    => [
                        'UF_CONTRACT_PAY_FILE' => [
                            'required'    => false,
                            'type'        => 'string',
                            'description' => 'Файл счета на оплату подтверждения договора'
                        ],
                        'UF_COOPERATION_PERCENT' => [
                            'required'    => false,
                            'type'        => 'string',
                            'description' => 'Процент вознаграждения'
                        ],
                        'UF_COOPERATION_SERVICE_FEE' => [
                            'required'    => false,
                            'type'        => 'string',
                            'description' => 'Сервисный сбор (%)'
                        ],
                        'UF_ACTIVE' => [
                            'required'    => false,
                            'type'        => 'bool',
                            'description' => 'Активность'
                        ],
                        'UF_CONTRACT_SCAN_LINK' => [
                            'required'    => false,
                            'type'        => 'string',
                            'description' => 'Ссылка на скан договора'
                        ],
                        'UF_CONTRACT_DATE' => [
                            'required'    => false,
                            'type'        => 'string',
                            'description' => 'Дата подтверждения договора'
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
        'v1/contract/setContractFinishStatus' => [
            'description'   => 'Утсановка финального статуса для договора',
            'active'        => true,
            'controller'    => 'Local\Api\Controllers\V1\Contract@setContractFinishStatus',
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
                    'description' => 'ID организации'
                ],
            ],
            'documentation' => [
                'exclude' => [
                    'admin'  => false,
                    'public' => false,
                ]
            ]
        ],
        'v1/contract/addAdditionalAgreements' => [
            'description'   => 'Доп документы организации',
            'active'        => true,
            'controller'    => 'Local\Api\Controllers\V1\Contract@addAdditionalAgreements',
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
                    'description' => 'ID организации'
                ],
                'fields'     => [
                    'required'    => true,
                    'type'        => 'array',
                    'description' => 'Поля организации',
                    'parameters'    => [
                        'UF_NAME' => [
                            'required'    => false,
                            'type'        => 'string',
                            'description' => 'Наименование документа'
                        ],
                        'UF_LINK' => [
                            'required'    => false,
                            'type'        => 'array',
                            'description' => 'Ссылка на документ'
                        ],
                        'UF_DATE_SIGNED' => [
                            'required'    => false,
                            'type'        => 'string',
                            'description' => 'Дата подписания'
                        ],
                        'UF_DATE_LOAD' => [
                            'required'    => false,
                            'type'        => 'string',
                            'description' => 'Дата загрузки в систему'
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
        'v1/contract/actSetFields' => [
            'description'   => 'Обновление полей акта',
            'active'        => true,
            'controller'    => 'Local\Api\Controllers\V1\Contract@actSetFields',
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
                    'description' => 'ID акта'
                ],
                'fields'     => [
                    'required'    => true,
                    'type'        => 'array',
                    'description' => 'Поля акта',
                    'parameters'    => [
                        'UF_FILE_UPD' => [
                            'required'    => false,
                            'type'        => 'array',
                            'description' => 'Файл УПД'
                        ],
                        'UF_STATUS' => [
                            'required'    => false,
                            'type'        => 'string',
                            'description' => 'Статус акта'
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
        'v1/contract/makeAct' => [
            'description'   => 'Создать акт',
            'active'        => true,
            'controller'    => 'Local\Api\Controllers\V1\Contract@makeAct',
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
                    'description' => 'ID компании'
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