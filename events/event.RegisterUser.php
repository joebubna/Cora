<?php
namespace Event;

class RegisterUser extends \Cora\Event
{
    public $user;
    
    public function __construct(\User $user)
    {
        $this->user = $user;
    }
}