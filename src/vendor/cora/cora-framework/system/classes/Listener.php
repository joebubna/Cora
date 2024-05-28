<?php
namespace Cora;

class Listener extends Framework
{
    protected $data;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->data = new \stdClass();
    }
    
    public function handle($event)
    {
        // Implemented by children.
    }
}