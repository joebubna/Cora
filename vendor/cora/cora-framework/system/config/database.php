<?php
$dbConfig['defaultConnection'] = 'MySQL';
$dbConfig['connections'] = [
    'MySQL' => [
        'adaptor'   => 'MySQL',
        'host'      => 'localhost',
        'dbName'    => 'someDatabase',
        'dbUser'    => 'root',
        'dbPass'    => 'root'
    ],
    
    'MySQL2' => [
        'adaptor'   => 'MySQL',
        'host'      => 'localhost',
        'dbName'    => 'someOtherDatabase',
        'dbUser'    => 'root',
        'dbPass'    => 'root'
    ],
    
    'MongoDB' => [
        'adaptor'   => 'MongoDB',
        'host'      => 'localhost',
        'dbName'    => '',
        'dbUser'    => 'root',
        'dbPass'    => 'root'
    ]
];