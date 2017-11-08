<?php
namespace Cora\Data;

class DbFloat
{
    public $value;
    
    public function __construct($number)
    {
        $this->value = $number;
    }

    public function __toString()
    {
        return $this->value;
    }
}