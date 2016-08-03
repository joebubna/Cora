<?php

class EventMapping extends \Cora\EventMapping
{
    protected $listeners = [
        'Event\\RegisterUser' => [
            ['Listener\\ThankYouForRegistering'],
            ['Listener\\NewRegistrationEmail']
        ]
    ];
}