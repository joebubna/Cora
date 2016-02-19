<?php
/**
 *  Debugging and error reporting options.
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);
$config['debug']    = false;

/**
 *  If site URL is www.MySite.com
 *  set this to '/'
 *  If site URL is www.MySite.com/app/
 *  set this to '/app/'
 */
$config['site_url'] = '/cora/';

/**
 *  Default Controller to try and load if one's not specified. This is also the default Method
 *  that will try to be called on the controller.
 */
$config['default_controller'] = 'index';

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