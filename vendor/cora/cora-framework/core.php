<?php
require_once('system/classes/Framework.php');
require_once('system/classes/Autoload.php');
require_once('system/classes/Container.php');
require_once('system/classes/Route.php');
require_once('system/classes/Load.php');
require_once('system/classes/Input.php');


// Create some globals. These are necessary unfortunately, as getting rid of them would require dependency
// injection into the model class, which would prevent empty models getting created with no arguments.
// The ability to create model instances without dependencies is viewed as vital.
// Models instantiate RepositoryFactories in order to perform DB operations.
$GLOBALS['savedModelsList'] = array();
$GLOBALS['coraSaveStarted'] = false;