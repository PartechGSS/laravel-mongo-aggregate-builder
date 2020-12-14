<?php

$mongoHost = env('MONGO_HOST', 'mongodb');
$mongoPort = env('MONGO_PORT') ? (int)env('MONGO_PORT') : 27017;

return [

    'connections' => [

        'mongodb' => [
            'name' => 'mongodb',
            'driver' => 'mongodb',
            'host' => $mongoHost,
            'database' => env('MONGO_DATABASE', 'testing'),
            'dsn' => 'mongodb://mongo:27017/testing',
        ]

    ],

];