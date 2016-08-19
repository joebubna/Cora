<?php
namespace Event;

class PasswordReset extends \Cora\Event
{
    public $user;
    
    public function __construct(\User $user)
    {
        $this->user = $user;
    }
}