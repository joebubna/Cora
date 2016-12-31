<?php
namespace Cora\Events;
/**
 *  When a lock error occurs when trying to save a model,
 *  the model gets grabbed and this event fired.
 */
class DbLockError extends \Cora\Event
{
    public $model;
    
    public function __construct($model)
    {
        $this->model = $model;
    }
}