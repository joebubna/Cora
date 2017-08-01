<?php
namespace Cora\Database;

class DbString
{
    public $str;
    
    public function __construct($str)
    {
        $this->str = $str;
    }

    public function __toString()
    {
        return $this->str;
    }
}