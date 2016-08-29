<?php
namespace Event;

class ProviderCreated extends \Cora\Event
{
    public $user;
    public $mailer;
    public $load;
    
    public function __construct(\User $user, $mailer, $load)
    {
        $this->user = $user;
        $this->mailer = $mailer;
        $this->load = $load;
    }
}