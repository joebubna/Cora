<?php
namespace Cora\App;

class EventMapping extends \Cora\EventMapping
{   
    public function setListeners()
    {
        return [
            'Events\\RegisterUser' => [
                ['Listeners\\ThankYouForRegistering'],
                ['Listeners\\NewRegistrationEmail']
            ],
            'Events\\PasswordReset' => [
                [$this->app->listeners->emails->sendPasswordResetToken]
            ],
            'Events\\ProviderCreated' => [
                [$this->app->listeners->emails->sendInitialPasswordResetToken]
            ]
        ];
    }
}