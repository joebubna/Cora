<?php
namespace Event;

class PasswordReset extends \Cora\Event
{
    public $user;
    public $mailer;
    
    public function __construct(\User $user, $mailer, $load)
    {
        $this->user = $user;
        $this->mailer = $mailer;
        $this->load = $load;
    }
}