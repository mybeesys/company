<?php 
return [
    'apps' => [
        [
            'id' => 'my-app-id',
            'name' => 'ReactApp',
            'key' => 'my-app-key',
            'secret' => 'my-app-secret',
            'enable_client_messages' => true,
            'enable_statistics' => false,
        ],
    ],

    'dashboard' => [
        'port' => 6001,
    ],

    'ssl' => [
        'local_cert' => null,
        'local_pk' => null,
        'passphrase' => null,
    ],
];