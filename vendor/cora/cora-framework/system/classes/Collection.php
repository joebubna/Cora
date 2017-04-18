<?php
namespace Cora;
/**
* 
*/
class Collection extends Container
{
    public function __construct($data = false, $dataKey = false, $parent = false, $returnClosure = false)
    {
        parent::__construct($parent, $data, $dataKey, $returnClosure);
    }
 }