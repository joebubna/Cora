<?php
$dbConfig['defaultConnection'] = 'MySQL2';
$dbConfig['connections'] = [
    'MySQL' => [
        'adaptor'   => 'MySQL',
        'host'      => 'localhost:3306',
        'dbName'    => 'cora',
        'dbUser'    => 'root',
        'dbPass'    => 'root'
    ],
    'MySQL2' => [
        'adaptor'   => 'MySQL',
        'host'      => 'localhost:3306',
        'dbName'    => 'cora2',
        'dbUser'    => 'root',
        'dbPass'    => 'root'
    ],
    'MySQL3' => [
        'adaptor'   => 'MySQL',
        'host'      => 'localhost:3306',
        'dbName'    => 'cora3',
        'dbUser'    => 'root',
        'dbPass'    => 'root'
    ]
];