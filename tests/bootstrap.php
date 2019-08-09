<?php

ini_set('error_reporting', E_ALL); // or error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Include Composer autoload.
include 'vendor/autoload.php';

// Load Cora core files.
include 'vendor/cora/cora-framework/core.php';

// Grab config options to pass in to the autoloader
$al_config = include('config/autoload.php');

// This register's Cora's autoload functions.
$autoload = new \Cora\Autoload($al_config);
$autoload->register();