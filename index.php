<?php
// Error Reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load Cora framework.
require('vendor/cora/cora-framework/core.php');

// Load app container.
require('includes/container.php');

$Route = new Cora\Route($container);
$Route->routeFind();
$Route->routeExec();