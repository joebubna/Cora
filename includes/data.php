<?php
// Setup
date_default_timezone_set("America/Los_Angeles");
include('container.php');
$app = $container;

// Calculate the hash of the password "test".
$testPass = password_hash('test', PASSWORD_DEFAULT);

// Create repositories
$comments   = $app->comments;
$perms      = $container->repository('Permission');
$roles      = $container->repository('Role');
$users      = $container->repository('User');



/////////////////////////////////
// PERMISSIONS
/////////////////////////////////
$permsList = $app->container(false, [
    new \Models\Permission('isUser'),
    new \Models\Permission('isAdmin'),
    new \Models\Permission('isDev')
], 'name');
$perms->save($permsList);



/////////////////////////////////
// ROLES
/////////////////////////////////

//-------------------------------
// Declare roles
//-------------------------------
$rolesList = $app->container(false, [
    new \Models\Role('User'),
    new \Models\Role('Admin'),
    new \Models\Role('Developer')
], 'name');

//-------------------------------
// Assign Permissions to roles
//-------------------------------
$rolesList->User->permissions = $app->collection([
    $permsList->isUser
]);

$rolesList->Admin->permissions = $app->collection([
    $permsList->isAdmin
]);

$rolesList->Developer->permissions = $app->collection([
    $permsList->isAdmin,
    $permsList->isDev
]);

// Save to DB.
$roles->save($rolesList);



/////////////////////////////////
// USERS
/////////////////////////////////

//-------------------------------
// Declare users
//-------------------------------
$usersList = $app->container(false, [
    new \Models\User('coraTestUser1@gmail.com', $testPass, $rolesList->User->id),
    new \Models\User('coraTestAdmin1@gmail.com', $testPass, $rolesList->Admin->id),
    new \Models\User('coraTestDev1@gmail.com', $testPass, $rolesList->Developer->id),
    new \Models\User('coraTestUser2@gmail.com', $testPass, $rolesList->User->id)
], 'email');

//-------------------------------
// Assign non-required attributes
//-------------------------------
$curDate = date("Y-m-d");

// User1
$user = $usersList->{'coraTestUser1@gmail.com'};
$user->roles = $app->collection([$rolesList->User]);
$user->firstName = 'Bob';
$user->lastName = 'Ross';
$user->createdDate = $curDate;
$user->comments = $app->container(false, [
    new \Models\Comment($user, 'Test Comment 1'),
    new \Models\Comment($user, 'Test Comment 2'),
    new \Models\Comment($user, 'Test Comment 3')
]);

// User2
$user2 = $usersList->{'coraTestUser2@gmail.com'};
$user2->roles = $app->collection([$rolesList->User]);
$user2->firstName = 'Susan';
$user2->lastName = 'Wachoski';
$user2->createdDate = $curDate;
$user2->parent = $user;

// Admin
$admin = $usersList->{'coraTestAdmin1@gmail.com'};
$admin->roles = $app->collection([$rolesList->Admin]);
$admin->firstName = 'Captain';
$admin->lastName = 'America';
$admin->createdDate = $curDate;

// Developer
$developer = $usersList->{'coraTestDev1@gmail.com'};
$developer->roles = $app->collection([$rolesList->Developer]);
$developer->firstName = 'Josiah';
$developer->lastName = 'Bubna';
$developer->createdDate = $curDate;

//-------------------------------
// Large set of fake users for pagination.
//-------------------------------
for ($i = 3; $i < 1000; $i++) {
    $user = new \Models\User("coraTestUser$i@gmail.com", $testPass, $rolesList->User->id);
    $user->firstName = "Bob$i";
    $user->lastName = "Ross$i";
    $user->createdDate = $curDate;
    $usersList->add($user, false, 'email');
}

// Save to DB.
$users->save($usersList);



/////////////////////////////////
// Comments
/////////////////////////////////

////-------------------------------
//// Declare comments
////-------------------------------
//$commentsList = $app->container(false, [
//    new \Models\Comment($user, 'Test Comment 1'),
//    new \Models\Comment($user, 'Test Comment 2'),
//    new \Models\Comment($user, 'Test Comment 3')
//]);
//
//// Save to DB.
//$comments->save($commentsList);
