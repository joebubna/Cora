<?php
namespace Cora\Data;

class DbInt
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