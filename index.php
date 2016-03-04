<?php
// Error Reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load Cora mini framework.
require('cora/core.php');


$Route = new Cora\Route();
$Route->routeFind();
if ( $Route->exists() ) {
    $Route->routeExec();
}
else {
    // Do legacy routing.
    echo 'No Controller found!';
}