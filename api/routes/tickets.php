<?php
return [
    'POST' => [
        'v1/tickets/addTicketType' => [
            'description'   => 'Создание типа билета',
            'active'        => true,
            'controller'    => 'Local\Api\Controllers\V1\Tickets@addTicketType',
            'contentType'   => 'application/json',
            'security'      => [
                'auth'  => [
                    'required' => true, // true || false
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
                    'description' => 'UUID мероприятия'
                ],
                'name'           => [
                    'required'    => true,
                    'type'        => 'string',
                    'description' => 'Название типа билета'
                ],
                'total_quantity' => [
                    'required'    => true,
                    'type'        => 'integer',
                    'description' => 'Количество билетов'
                ],
                'max_quantity'   => [
                    'required'    => true,
                    'type'        => 'integer',
                    'description' => 'Максимальное количество билетов в корзине'
                ],
                'reserve_time'   => [
                    'required'    => true,
                    'type'        => 'integer',
                    'description' => 'Количество минут для бронирования билета'
                ],
                'price'   => [
                    'required'    => true,
                    'type'        => 'integer',
                    'description' => 'Цена билета'
                ],
            ],
            'documentation' => [
                'exclude' => [
                    'admin'  => false,
                    'public' => false,
                ]
            ]
        ],
        'v1/tickets/addToBasket' => [
            'controller'  => 'Local\Api\Controllers\V1\Tickets@addToBasket',
            'description' => 'Добавление в корзину по АПИ',
            'active'        => true,
            'contentType'   => 'application/json',
            'security'      => [
                'auth'  => [
                    'required' => true, // true || false
                    'type'     => 'token',
                ],
                'token' => [
                    'whitelist' => [
                        // '408f4f2e-d5a6e4a7-06930a16-8301b343'
                    ]
                ]
            ],
            'parameters'    => [
                'request'     => [
                    'required'    => true,
                    'type'        => 'array',
                    'description' => 'Данные передаваемые с сайта',
                    'parameters'    => [
		                'user_id'     => [
		                    'required'    => true,
		                    'type'        => 'integer',
		                    'description' => 'ID покупателя с сайта'
		                ],
		            ],
                ],
                'items'     => [
                    'required'    => true,
                    'type'        => 'array',
                    'description' => 'Данные о билетах',
                    'parameters'    => [
                    	[
			                'paymentGroup'     => [
			                    'required'    => true,
			                    'type'        => 'string',
			                    'description' => 'ID мероприятия'
			                ],
			                'quantity'     => [
			                    'required'    => true,
			                    'type'        => 'integer',
			                    'description' => 'Количество билетов'
			                ],
			                'props'     => [
			                    'required'    => true,
			                    'type'        => 'array',
			                    'description' => 'Свойства билета',
			                    'parameters'    => [
					                'seat_id'     => [
					                    'required'    => true,
					                    'type'        => 'string',
					                    'description' => 'ID билета'
					                ],
                                    'seat_map_id'     => [
                                        'required'    => true,
                                        'type'        => 'integer',
                                        'description' => 'ID места в SeatMap'
                                    ],
					                'place'     => [
					                    'type'        => 'integer',
					                    'description' => 'Место'
					                ],
					                'row'     => [
					                    'type'        => 'integer',
					                    'description' => 'Ряд'
					                ],
					            ],
			                ]
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