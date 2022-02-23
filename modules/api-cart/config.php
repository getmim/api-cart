<?php

return [
    '__name' => 'api-cart',
    '__version' => '0.1.0',
    '__git' => 'git@github.com:getmim/api-cart.git',
    '__license' => 'MIT',
    '__author' => [
        'name' => 'Iqbal Fauzi',
        'email' => 'iqbalfawz@gmail.com',
        'website' => 'http://iqbalfn.com/'
    ],
    '__files' => [
        'modules/api-cart' => ['install','update','remove']
    ],
    '__dependencies' => [
        'required' => [
            [
                'cart' => NULL
            ],
            [
                'api' => NULL
            ]
        ],
        'optional' => []
    ],
    'autoload' => [
        'classes' => [
            'ApiCart\\Controller' => [
                'type' => 'file',
                'base' => 'modules/api-cart/controller'
            ]
        ],
        'files' => []
    ],
    'routes' => [
        'api' => [
            'apiCartSingle' => [
                'path' => [
                    'value' => '/cart'
                ],
                'method' => 'GET',
                'handler' => 'ApiCart\\Controller\\Cart::single'
            ],
            'apiCartAssign' => [
                'path' => [
                    'value' => '/cart'
                ],
                'method' => 'PUT',
                'handler' => 'ApiCart\\Controller\\Cart::assign'
            ],
            'apiCartItemIndex' => [
                'path' => [
                    'value' => '/cart/item',
                    'params' => [
                        'id' => 'number'
                    ]
                ],
                'method' => 'GET',
                'handler' => 'ApiCart\\Controller\\CartItem::index'
            ],
            'apiCartItemAdd' => [
                'path' => [
                    'value' => '/cart/item'
                ],
                'method' => 'POST',
                'handler' => 'ApiCart\\Controller\\CartItem::create'
            ],
            'apiCartItemRemove' => [
                'path' => [
                    'value' => '/cart/item/(:id)',
                    'params' => [
                        'id' => 'number'
                    ]
                ],
                'method' => 'DELETE',
                'handler' => 'ApiCart\\Controller\\CartItem::remove'
            ]
        ]
    ],
    'libForm' => [
        'forms' => [
            'api-cart.item.create' => [
                'product' => [
                    'rules' => [
                        'required' => true,
                        'exists' => [
                            'model' => 'Product\\Model\\Product',
                            'field' => 'id',
                            'where' => ['status' => 2]
                        ]
                    ]
                ],
                'quantity' => [
                    'rules' => [
                        'required' => true,
                        'numeric' => [
                            'min' => 1
                        ]
                    ]
                ]
            ]
        ]
    ]
];
