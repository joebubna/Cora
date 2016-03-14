<?php
/**
 *  Debugging options.
 */
$config['debug'] = false;
$config['debugHide'] = false; // Hides debug info in HTML comments so you have to view source to see it.

/**
 *  If site URL is www.MySite.com
 *  set this to '/'
 *  If site URL is www.MySite.com/app/
 *  set this to '/app/'
 *
 *  DONT FORGET ENDING SLASH!!!
 */
$config['site_url'] = '/cora/';


/**
 *  If you want to extend the base Cora controller class, add
 *  a <name>.php file to the Cora\Extensions directory that is
 *  a class that extends Cora. Then enter <name> below.
 */
$config['cora_extension'] = 'MyApp';

/**
 *  Default Controller to try and load if one's not specified.
 */
$config['default_controller'] = 'Home';

/**
 *  Default Method within a controller to try and load if one's not specified.
 */
$config['default_method'] = 'index';

// Default template to use.
$config['template'] = 'template';

/**
 *  Enable RESTful routing?
 *
 *  If turned on:
 *  POST request to "Users" class and "index" method will get routed to the "indexPOST" method within Users.
 *  PUT request to "Users" class and "index" method will get routed to the "indexPUT" method within Users.
 */
$config['enable_RESTful'] = true;


/**
 *  Path to models/classes directory relative to this file.
 */
$config['pathToModels'] = dirname(__FILE__).'/../../../classes/';

/**
 *  Path to views directory relative to this file.
 */
$config['pathToViews'] = dirname(__FILE__).'/../../../views/';

/**
 *  Path to controllers directory relative to this file.
 */
$config['pathToControllers'] = dirname(__FILE__).'/../../../controllers/';

/**
 *  Path to libraries directory relative to this file.
 */
$config['pathToLibraries'] = dirname(__FILE__).'/../../../libraries/';

/**
 *  Path to Extensions directory relative to this file.
 */
$config['pathToExtensions'] = dirname(__FILE__).'/../../extensions/';


/**
 *  Model/Class file prefix. I.e. If your class files are named "class.MyClass.inc.php"
 *  then enter 'class.' for Prefix and '.inc' for postfix.
 */
$config['modelsPrefix'] = 'class.';
$config['modelsPostfix'] = '';

/**
 *  View file prefix / postfix.
 */
$config['viewsPrefix'] = 'view.';
$config['viewsPostfix'] = '';

/**
 *  Controller file prefix / postfix.
 */
$config['controllersPrefix'] = 'controller.';
$config['controllersPostfix'] = '';

/**
 *  Library file prefix / postfix.
 */
$config['librariesPrefix'] = '';
$config['librariesPostfix'] = '';