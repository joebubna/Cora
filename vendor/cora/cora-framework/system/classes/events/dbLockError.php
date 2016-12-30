<?php
namespace Cora\Events;

class dbLockError extends \Cora\Event
{
    public $user;
    
    public function __construct($user)
    {
        $this->user = $user;
    }
}