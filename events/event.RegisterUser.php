<?php
namespace Events;

class RegisterUser extends \Cora\Event
{
    public $user;
    
    public function __construct($user)
    {
        $this->user = $user;
    }
}