<?php
namespace Cora;
/**
* 
*/
class Container extends Collection
{
    public function __construct($parent = false, $data = false, $dataKey = false, $returnClosure = false)
    {
        parent::__construct($data, $dataKey, $parent, $returnClosure);
    }
 }