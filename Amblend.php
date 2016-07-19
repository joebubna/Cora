<?php

// Grab DatabaseBuilder class
require('vendor/cora/cora-framework/system/classes/Framework.php');
require('vendor/cora/cora-framework/system/classes/DatabaseBuilder.php');

// Create instance
$builder = new \Cora\DatabaseBuilder();

// Make sure a command was passed in.
if (!isset($argv[1])) {
    echo "You must give a command to run. See Cora's /Amblend/DatabaseBuilder documentation.\r\n";
    exit;
}

// Try executing command.
switch ($argv[1]) {
    case 'build':
        $builder->build();
        break;
    default:
        echo "The command '".$argv[1]."' is not recognized. See Cora's /Amblend/DatabaseBuilder documentation.\r\n";
}

// Create newline for cleaner console output.
echo "\r\n";