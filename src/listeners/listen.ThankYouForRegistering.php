<?php
namespace Listeners;

class ThankYouForRegistering extends \Cora\Listener
{
    public function handle($event)
    {
        echo 'Welcome new user!<br>';
    }
}