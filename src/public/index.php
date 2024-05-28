<?php
// Error Reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include Composer autoload
$composerAuto = include('../vendor/autoload.php');
$results = $composerAuto->findFile("\Cora");

print "Found file for class at: $results";

// $composerAuto->loadClass("\Cora");
// print_r($composerAuto);

// Load Cora framework.
require('../vendor/cora/cora-framework/core.php');

// Grab config options to pass in to the autoloader
$al_config = include('../config/autoload.php');

// This register's Cora's autoload functions.
$autoload = new \Cora\Autoload($al_config);
$autoload->register();

// Load app container.
require('../includes/container.php');

// Create a router. 
$Route = new Cora\Route($container);

// Find route.
$Route->run();