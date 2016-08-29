<?php
include('container.php');
$db = $container->db();
$testPass = password_hash('test', PASSWORD_DEFAULT);

/////////////////////////////////
// ROLES
/////////////////////////////////
$db ->insert('name')
    ->into('roles')
    ->values([
        ['Provider'], 
        ['Admin'],
        ['Developer']
    ])
    ->exec();

/////////////////////////////////
// PERMISSIONS
/////////////////////////////////
$db ->insert('name')
    ->into('permissions')
    ->values([
        ['isAdmin'], 
        ['isDev']
    ])
    ->exec();

/////////////////////////////////
// REF_ROLES_PERMISSIONS
/////////////////////////////////
$db ->insert('role, permission')
    ->into('ref_roles_permissions')
    ->values([
        [2, 1], 
        [3, 2]
    ])
    ->exec();

/////////////////////////////////
// USERS
/////////////////////////////////
$db ->insert('email, password, primaryRole')
    ->into('users')
    ->values([
        ['pmargason@fuelmedical.com', $testPass, 2], 
        ['jbubna@fuelmedical.com', $testPass, 3],
        ['mkeene@fuelmedical.com', $testPass, 3],
        ['bob@gmail.com', $testPass, 1], 
        ['john@gmail.com', $testPass, 1],
        ['susan@gmail.com', $testPass, 1]
    ])
    ->exec();

/////////////////////////////////
// REF_USERS_ROLES
/////////////////////////////////
$db ->insert('user, role')
    ->into('ref_users_roles')
    ->values([
        [1, 2], 
        [2, 3],
        [3, 3],
        [4, 1], 
        [5, 1],
        [6, 1]
    ])
    ->exec();