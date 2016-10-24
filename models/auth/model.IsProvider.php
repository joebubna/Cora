<?php
namespace Models\Auth;

class IsProvider
{
    public function handle($auth, $user = false)
    {
        if ($auth->hasPermission($user, 'isProvider')) {
            return true;
        }
        else {
            return false;
        }
    }
}