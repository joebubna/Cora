<?php
namespace Cora\Data;

class DbString
{
    public $value;
    
    public function __construct($str)
    {
        $this->value = $str;
    }

    public function __toString()
    {
        return $this->value;
    }
}