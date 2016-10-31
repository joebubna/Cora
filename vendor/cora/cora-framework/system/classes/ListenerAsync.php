<?php
namespace Cora;

class ListenerAsync extends FrameworkAsync
{
    protected $data;
    
    public function __construct()
    {
        parent::__construct();
        
        $this->data = new \stdClass();
    }
    
    public function __call($name, $args)
    {
        if ($name == 'handle') {
            $this->start();
        }
    }
    
    public function run()
    {
        echo 'HI';
    }
    
    public function handle($event)
    {
        // Implemented by children.
    }
}