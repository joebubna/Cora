<?php
/**
 *  Below you should first define your database connection(s),
 *  followed by setting one of the connections as the default.
 *
 *  The format is as follows:
 *    'ConnectionName' => [
 *        ... connection settings ...
 *    ],
 *
 *  'defaultConnection' refers to a ConnectionName. These are the array keys you define below.
 *  
 *  'adaptor' needs to be the name of a Cora database adaptor. See here for more info on supported adaptors:
 *  http://joebubna.github.io/Cora/documentation/databaseclass/overview/#currently-supported-databases
 */

$dbConfig['defaultConnection'] = 'MySQL';
$dbConfig['connections'] = [
    'MySQL' => [
        'adaptor'   => 'MySQL',
        'host'      => 'localhost:3306',
        'dbName'    => 'cora',
        'dbUser'    => 'root',
        'dbPass'    => 'root',
        'testOn'    => 'MySQLTest'
    ],
    'MySQL2' => [
        'adaptor'   => 'MySQL',
        'host'      => 'localhost:3306',
        'dbName'    => 'cora2',
        'dbUser'    => 'root',
        'dbPass'    => 'root'
    ],
    'MySQLTest' => [
        'adaptor'   => 'MySQL',
        'host'      => 'localhost:3306',
        'dbName'    => 'cora_test',
        'dbUser'    => 'root',
        'dbPass'    => 'root'
    ],
    'EnterConnectionNameHere' => [
        'adaptor'   => 'MySQL',
        'host'      => 'localhost:3306',
        'dbName'    => 'cora3',
        'dbUser'    => 'root',
        'dbPass'    => 'root'
    ]
];