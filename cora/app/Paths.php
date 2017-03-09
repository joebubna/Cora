<?php
$paths = new \Cora\Container();

$path = new \Cora\Path();
    $path->url = 'users/{action}-{subaction}/{id}';
    $path->def['{id}'] = '[0-9]+';
    $path->route = '/users/forgotPassword/';
    $path->preExec = function() {
        return true;
    };
$paths->add($path);

// $path = new \Cora\Path();
//     $path->url = 'home/view';
//     $path->preExec = function() {
//         return false;
//     };
// $paths->add($path);


$path = new \Cora\Path();
    $path->url = 'users/test/{action}-{action2}';
    $path->route = '/home/view/{action2}/{action}';
    $path->actions = 'GET|POST';
    $path->RESTful = false;
$paths->add($path);