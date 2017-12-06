<?php
namespace Cora\Data;

class DbExprCompound extends DbExpr
{
    public $leftExpr;
    public $connector;
    public $rightExpr;
    public $conjunction;
    
    public function __construct($leftExpr, $connector = ', ', $rightExpr = '', $conjunction = 'AND')
    {
        $this->leftExpr = $leftExpr;
        $this->connector = $connector;
        $this->rightExpr = $rightExpr;
        $this->conjunction = $conjunction;
    }
}