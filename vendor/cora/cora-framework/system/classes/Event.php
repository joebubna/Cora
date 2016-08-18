<?php
namespace Cora;

class Event
{
    public $name;
    public $input;
    public $type;
    public $tags;
    
    public function __construct($name, $input, $type = false, $tags = [])
    {
        $this->name = $name;
        $this->input = $input;
        $this->type = $type;
        $this->tags = $tags;
    }
}