<?php

ini_set('error_reporting', E_ALL); // or error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Include Composer autoload.
require(__DIR__.'/../vendor/autoload.php');

// Load Cora Autoload
require(__DIR__.'/../Autoload.php');

$config = include(__DIR__.'/../config/Autoload.php');

// This register's Cora's autoload functions.
$autoload = new \Cora\Autoload($config);
$autoload->register();