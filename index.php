<?php
// Error Reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load Cora framework.
require('vendor/cora/cora-framework/core.php');

// Load app container.
require('includes/container.php');

// Create a router. This register's Cora's autoload functions.
$Route = new Cora\Route($container);

// Register Composer Autoloader as fallback if Cora's don't find class.
require 'vendor/autoload.php';

// Find route.
$Route->routeFind();

// Execute route or display error.
$Route->routeExec();