<?php
namespace Listener;

class NewRegistrationEmail extends \Cora\Listener
{
    public function handle($event)
    {
        echo 'Emailing out a welcome message!<br>';
    }
}