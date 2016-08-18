<?php 
namespace Cora;

interface Strategy
{
 
    /**
     * returns 0 if a == b, -1 if a < b,
     * 1 if a > b
     * @return int
     */
    public function compareTo($a, $b);
 
}