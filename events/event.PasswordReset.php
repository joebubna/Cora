<?php
namespace Event;

class PasswordReset extends \Cora\Event
{
    public $user;
    public $mailer;
    
    public function __construct(\User $user, $mailer)
    {
        $this->user = $user;
        $this->mailer = $mailer;
    }
}