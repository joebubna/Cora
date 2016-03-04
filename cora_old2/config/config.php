<?php
/**
 *  Debugging options.
 */
$config['debug'] = false;

/**
 *  If site URL is www.MySite.com
 *  set this to '/'
 *  If site URL is www.MySite.com/app/
 *  set this to '/app/'
 */
$config['site_url'] = '/cora/';

/**
 *  Default Controller to try and load if one's not specified.
 */
$config['default_controller'] = 'home';

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
$config['pathToModels'] = dirname(__FILE__).'/../../classes/';

/**
 *  Path to views directory relative to this file.
 */
$config['pathToViews'] = dirname(__FILE__).'/../../views/';

/**
 *  Path to controllers directory relative to this file.
 */
$config['pathToControllers'] = dirname(__FILE__).'/../../controllers/';


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

