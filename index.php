<?php
// Error Reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load Cora framework.
require('vendor/cora/cora-framework/core.php');


$Route = new Cora\Route();
$Route->routeFind();
$Route->routeExec();