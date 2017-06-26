<?php
namespace Cora;
/**
* 
*/
class ServiceLocator extends Collection
{
    public function __construct($parent = false, $data = false, $dataKey = false, $returnClosure = false)
    {
        parent::__construct($data, $dataKey, $parent, $returnClosure);
    }

    // public function getFactory($class)
    // {
    //     re
    // }
 }