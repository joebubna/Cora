<?php
// Error Reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load Cora framework.
require('vendor/cora/cora-framework/core.php');

// This register's Cora's autoload functions.
$autoload = new Cora\Autoload();

// Register Composer Autoloader as fallback if Cora's don't find class.
require 'vendor/autoload.php';

// Load app container.
require('includes/container.php');

// Create a router. 
$Route = new Cora\Route($container);

// Find route.
$Route->run();