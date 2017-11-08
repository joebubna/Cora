<?php
namespace Cora\Data;

class DbExpr
{
    public $leftExpr;
    public $connector;
    public $rightExpr;
    
    public function __construct($rightExpr, $connector = ', ', $leftExpr = '')
    {
        $this->leftExpr = $leftExpr;
        $this->connector = $connector;
        $this->rightExpr = $rightExpr;
    }
}