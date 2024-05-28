<?php
namespace Models\Auth;

class CanEditUser
{
    protected $userId;
    
    public function __construct($userId)
    {
        $this->userId = $userId;    
    }
    
    public function handle($auth, $user = false)
    {
        if ($auth->hasPermission($user, 'isAdmin') || $this->userId == $auth->userGetCurrent()->id) {
            return true;
        }
        else {
            return false;
        }
    }
}