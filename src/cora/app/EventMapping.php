<?php
namespace Cora\App;

class EventMapping extends \Cora\EventMapping
{   
    public function setListeners()
    {
        return [
            'Events\\UserRegistered' => [
                ['Listeners\\ThankYouForRegistering'],
                ['Listeners\\NewRegistrationEmail'],
                [$this->app->listeners->displayGreeting]
            ],
            'Events\\PasswordReset' => [
                [$this->app->listeners->emails->sendPasswordResetToken]
            ]
        ];
    }
}