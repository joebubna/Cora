<?php
namespace Cora\Data;

class DbFunction
{
    public $name;
    public $args;
    
    public function __construct($functionName)
    {
        $this->name = $functionName;

        // Get array of arguments passed in
        $args = func_get_args();

        // Remove the function name (aka - the first argument)
        array_shift($args);
        
        $this->args = $args;
    }

    public function __toString()
    {
        return $this->value;
    }
}