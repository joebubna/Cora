<?php
/**
 *  Debugging options.
 */
$config['debug'] = false;
$config['debugHide'] = false; // Hides debug info in HTML comments so you have to view source to see it.
$config['mode'] = 'development';

/**
 *  This is your base URL.
 *  I.E. www.MySite.com
 */
$config['base_url'] = 'localhost';

/**
 *  If site URL is www.MySite.com
 *  set this to '/'
 *  If site URL is www.MySite.com/app/
 *  set this to '/app/'
 *
 *  DONT FORGET ENDING SLASH!!!
 *
 *  Note: This has nothing (directly) to do with the directory structure on your server.
 *  This tells Cora where in the URL to start looking for a controller.
 *  WWW.MYSITE.COM/APP/controller/method/id
 *  If site_url is set to '/app/' then the uppercase part of the url above will be ignored
 *  by the router.
 */
$config['site_url'] = '/cora/';

/**
 *  Should URLs be converted to lowercase?
 *  If your project is hosted on a case-sensitive system (e.g. Linux) and you want a URL to
 *  work regardless of whether a user typed part of it as uppercase, then having this option on
 *  can be useful. If however you want your URLs to be case sensitive, then turn this off.
 */
$config['lowercase_url'] = true;


/**
 *  Email settings. Need to set these if you want to send emails using Cora's Mailer class.
 */
$config['smtp_host'] = '';
$config['smtp_port'] = 587;
$config['smtp_secure'] = 'tls';
$config['smtp_auth'] = 'true';
$config['smtp_username'] = '';
$config['smtp_password'] = '';

/**
 *  If you want to extend the base Cora controller class, add
 *  a <name>.php file to the Cora\Extensions directory that is
 *  a class that extends Cora. Then enter <name> below.
 */
//$config['cora_extension'] = 'MyApp';

/**
 *  On by default, this causes Cora to use full PSR-4 namespacing rules such that all classes
 *  have a namespace. This prevents almost all namespace conflicts. This means all your controllers
 *  will be in a "Controller" namespace, all your models in a "Model" namespace, etc.
 *
 *  Optionally, you can turn this off. Doing so will allow you to keep your classes un-namespaced
 *  (so long as they aren't in a subfolder), but you will be unable to have classes with the name
 *  name. For instance, you wouldn't be able to have a "User" controller and a "User" model. Instead,
 *  you would have to have a "Users" (plural) controller and a "User" model. The benefit to having
 *  this off is mostly if you are working on integrating Cora with a legacy project that didn't use
 *  namespaces much. Additionally, having this off can declutter your code by making your class
 *  instantiations shorter and ridding you of a ton of "use" statements at the top of your code.
 *  Just make sure you understand the consequences before disabling this.
 *  
 *  HISTORY:
 *  When Cora was first conceived, its purpose was to integrate with an existing legacy project.
 *  In order to do that, controllers and models were left un-namespaced so long as they weren't
 *  in a subfolder. Support for this limited namespacing mode is now turned off by default, but
 *  can optionally be turned on if you want it.
 */
$config['psr4_namespaces'] = TRUE;

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
 *  ONLY CHANGE THIS VARIABLE IF YOU KNOW WHAT YOU ARE DOING. READ BELOW.
 *  
 *  The RELATIVE filepath to this app's base directory on the server from where this file is located.
 *  Note that this should NOT need to get changed!
 *  The only conceivable reason you might want to change this is if you aren't using composer or the demo
 *  project to install Cora, and instead are placing the Cora system files in some custom place.
 *
 *  This takes the location of this file which is normally at:
 *  /project_root/vendor/cora/cora-framework/system/config/config.php
 *  and backtracks through the filesystem to the project_root, saving a ref to it.
 */
$config['basedir'] = realpath(dirname(__FILE__).'/../../../../../').'/';

/**
 *  The model namespace is used by the RepositoryFactory class to save you
 *  having to type in 'models/user' to create a user repository.
 */
if (!defined('CORA_MODEL_NAMESPACE')) {
    define('CORA_MODEL_NAMESPACE', '\\Models\\');
}

/**
 *  Path to models directory relative to this file. Used by Database Builder.
 */
$config['pathToModels'] = $config['basedir'].'models/';

/**
 *  Path to views directory relative to this file.
 */
$config['pathToViews'] = $config['basedir'].'views/';

/**
 *  Path to controllers directory relative to this file.
 */
$config['pathToControllers'] = $config['basedir'].'controllers/';

/**
 *  Model/Class file prefix. I.e. If your class files are named "class.MyClass.inc.php"
 *  then enter 'class.' for Prefix and '.inc' for postfix.
 */
$config['modelsPrefix'] = 'model.';
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
$config['librariesPrefix'] = 'lib.';
$config['librariesPostfix'] = '';

/**
 *  Event file prefix / postfix.
 */
$config['eventsPrefix'] = 'event.';
$config['eventsPostfix'] = '';

/**
 *  Listener file prefix / postfix.
 */
$config['listenerPrefix'] = 'listen.';
$config['listenerPostfix'] = '';