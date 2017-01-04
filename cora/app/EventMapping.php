<?php
namespace Cora\App;

class EventMapping extends \Cora\EventMapping
{   
    public function setListeners()
    {
        return [
            'Events\\UserRegistered' => [
                ['Listeners\\ThankYouForRegistering'],
                ['Listeners\\NewRegistrationEmail']
            ],
            'Events\\PasswordReset' => [
                [$this->app->listeners->emails->sendPasswordResetToken]
            ]
        ];
    }
}