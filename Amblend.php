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
    case 'dbBuild':
        echo "\n\n\n\n\n\n";
        $dataFile = $argv[2];
        $builder->dbBuild($dataFile);
        break;
    case 'dbEmpty':
        echo "\n\n\n\n\n\n";
        $connection = $argv[2];
        $builder->dbEmpty($connection);
        break;
    default:
        echo "The command '".$argv[1]."' is not recognized. See Cora's /Amblend/DatabaseBuilder documentation.\r\n";
}

// Create newline for cleaner console output.
echo "\r\n";