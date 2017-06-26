<?php
namespace Cora;
/**
*   Has dependency on \Cora\ServiceFactory class.
*/
class ServiceLocator extends Collection
{
    public function __construct($parent = false, $data = false, $dataKey = false, $returnClosure = false)
    {
        parent::__construct($data, $dataKey, $parent, $returnClosure);
    }

    public function getFactory($class)
    {
        return new ServiceFactory($this, $this->find($class));
    }
 }