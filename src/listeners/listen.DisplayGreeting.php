<?php
namespace Listeners;

class DisplayGreeting extends \Cora\Listener
{
    public function handle($event)
    {
        echo 'Greetings '.$event->user->email.'!<br>';
    }
}