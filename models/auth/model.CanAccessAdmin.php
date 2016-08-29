<?php
namespace Auth;

class CanAccessAdmin
{
    public function handle($auth, $user = false)
    {
        if ($auth->hasPermission($user, 'isAdmin')) {
            return true;
        }
        else {
            return false;
        }
    }
}