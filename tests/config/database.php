<?php

$mongoHost = env('MONGO_HOST', 'mongodb');
$mongoPort = env('MONGO_PORT') ? (int)env('MONGO_PORT') : 27017;

return [

    'connections' => [

        'mongodb' => [
            'name' => 'mongodb',
            'driver' => 'mongodb',
            'host' => $mongoHost,
            'database' => env('MONGO_DATABASE', 'unittest'),
        ],

        'dsn_mongodb' => [
            'driver' => 'mongodb',
            'dsn' => "mongodb://$mongoHost:$mongoPort",
            'database' => env('MONGO_DATABASE', 'unittest'),
        ],

        'dsn_mongodb_db' => [
            'driver' => 'mongodb',
            'dsn' => "mongodb://$mongoHost:$mongoPort/" . env('MONGO_DATABASE', 'unittest'),
        ],

    ],

];