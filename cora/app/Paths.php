<?php
$paths = new \Cora\Container();

/**
 *  Shows use of a passive route...
 *  This route will get executed, but not end the search for other custom routes.
 *  It WILL however add this route to a list so it can't accidentally be executed a 2nd time.
 */
$path = new \Cora\Path();
    $path->url = '/coolbeans/{anything}';
    $path->route = 'tests/{anything}';
    $path->passive = true;
$paths->add($path);


/**
 *  Shows use of the special {anything} variable.
 */
$path = new \Cora\Path();
    $path->url = '/tests/{anything}';
    $path->route = '/home/view/{anything}';
$paths->add($path);


/**
 *  Example of flexibility of custom routes.
 *  ID variable is specified to be a number via regex.
 */
$path = new \Cora\Path();
    $path->url = '/users/{action}-{subaction}/{id}';
    $path->def['{id}'] = '[0-9]+';
    $path->route = '/users/forgotPassword/';
$paths->add($path);


/**
 *  Example of rearranging variables in a route.
 *  Also limiting to specific HTTP actions and thirdly disabling RESTful routing for this path.
 */
$path = new \Cora\Path();
    $path->url = '/users/test/{action}-{action2}';
    $path->route = '/home/view/{action2}/{action}';
    $path->actions = 'GET|POST';
    $path->RESTful = false;
$paths->add($path);


/**
 *  Example of protecting a route with authentication.
 */
$path = new \Cora\Path();
    $path->url = '/home/private';
    $path->route = '/home/view/protected/area';
    $path->preExec = function($app) {
        if (!$app->auth->access(new \Models\Auth\LoggedIn)) { return false; }
        return true;
    };
$paths->add($path);


/**
 *  Make a path unreachable by normal automatic routing.
 */
// $path = new \Cora\Path();
//     $path->url = 'home/view';
//     $path->preExec = function() {
//         return false;
//     };
// $paths->add($path);
