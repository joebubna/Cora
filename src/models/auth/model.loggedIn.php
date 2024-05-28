<?php
namespace Models\Auth;

class LoggedIn
{
    public function handle($auth, $user = false)
    {
        if ($user) {
            return true;
        }
        else {
            return false;
        }
    }
}