<?php
namespace Cora\Data;

class DbExprGroup
{
    public $group;
    public $delimiter;
    
    public function __construct($group, $delimiter = ', ')
    {
        $this->group = $group;
        $this->delimiter = $delimiter;
    }
}