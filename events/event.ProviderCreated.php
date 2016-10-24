<?php
namespace Events;

class ProviderCreated extends \Cora\Event
{
    public $user;
    
    public function __construct($user)
    {
        $this->user = $user;
    }
}