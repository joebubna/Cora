<?php
// Error Reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include Composer autoload
include('../vendor/autoload.php');

// OPTIONAL: grab config options to pass in to the autoloader
$config = include('../config/autoload.php');

// This register's Cora's autoload functions.
$autoload = new \Cora\Autoload($config);
$autoload->register();

// Utilize the new autoload example #1
$c = new Controllers\Test();
$c->sayHi();

// Example #2
$c = new Controllers\Api\Test();
$c->sayHi();

// Example #3
$c = new Classes\Test();
$c->sayHi();