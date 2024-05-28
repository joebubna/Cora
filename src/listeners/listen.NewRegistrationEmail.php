<?php
namespace Listeners;

class NewRegistrationEmail extends \Cora\Listener
{
    public function handle($event)
    {
        echo 'Emailing out a welcome message!<br>';
    }
}