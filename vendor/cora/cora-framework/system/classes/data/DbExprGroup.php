<?php
namespace Cora\Data;

class DbExprGroup
{
    public $group;
    public $delimiter;
    public $conjunction;
    
    public function __construct($group = [], $delimiter = '', $conjunction = 'AND')
    {
        $this->group = $group;
        $this->delimiter = $delimiter;
        $this->conjunction = $conjunction;
    }

    public function add($exp) 
    {
        $this->group[] = $exp;
    }
}