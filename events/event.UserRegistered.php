<?php
namespace Events;

class UserRegistered extends \Cora\Event
{
    public $user;
    
    public function __construct($user)
    {
        $this->user = $user;
    }
}