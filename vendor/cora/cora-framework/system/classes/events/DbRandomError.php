<?php
namespace Cora\Events;
/**
 *  This gets fired if some random error occurs when trying to save 
 *  to a database.
 */
class DbRandomError extends \Cora\Event
{
    public $model;
    public $exception;
    
    public function __construct($model, $exception)
    {
        $this->model = $model;
        $this->exception = $exception;
    }
}