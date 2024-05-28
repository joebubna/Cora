<?php
namespace Events;

class PasswordReset extends \Cora\Event
{
    public $user;
    
    public function __construct($user)
    {
        $this->user = $user;
    }
}