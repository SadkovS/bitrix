<?php
return [
    'GET' => [
        'v1/skd/checkToken' => [
            'description'   => 'Проверка токена контроллера',
            'active'        => true,
            'controller'    => 'Local\Api\Controllers\V1\Skd@checkToken',
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
            'parameters'    => [],
            'documentation' => [
                'exclude' => [
                    'admin'  => false,
                    'public' => false,
                ]
            ]
        ],
        'v1/skd/getInfo' => [
	        'description'   => 'Получение информации о мероприятии для контроллера',
	        'active'        => true,
	        'controller'    => 'Local\Api\Controllers\V1\Skd@getInfo',
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
	        'parameters'    => [],
	        'documentation' => [
		        'exclude' => [
			        'admin'  => false,
			        'public' => false,
		        ]
	        ]
        ],
        'v1/skd/search' => [
	        'description'   => 'Поиск участников мероприятия',
	        'active'        => true,
	        'controller'    => 'Local\Api\Controllers\V1\Skd@search',
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
	        'parameters'    => [],
	        'documentation' => [
		        'exclude' => [
			        'admin'  => false,
			        'public' => false,
		        ]
	        ]
        ],
        'v1/skd/getTickets' => [
	        'description'   => 'Получить список билетов',
	        'active'        => true,
	        'controller'    => 'Local\Api\Controllers\V1\Skd@getTickets',
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
	        'parameters'    => [],
	        'documentation' => [
		        'exclude' => [
			        'admin'  => false,
			        'public' => false,
		        ]
	        ]
        ],
    ],
    'POST' => [
	    'v1/skd/checkTicket' => [
		    'description'   => 'Проверка билета',
		    'active'        => true,
		    'controller'    => 'Local\Api\Controllers\V1\Skd@checkTicket',
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
			   /* 'code'     => [
				    'required'    => true,
				    'type'        => 'string',
				    'description' => 'Штрихкод билета'
			    ],*/
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