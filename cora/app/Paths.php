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


$path = new \Cora\Path();
$path->url = 'users/test/{action}-{action2}';
//$path->def['{action2}'] = '[0-9]+';
$path->route = '/home/view/{action}/{action2}';
$path->preExec = function() {
    return true;
};
$paths->add($path);