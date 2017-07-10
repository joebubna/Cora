<?php
namespace Cora;
/**
*   Has dependency on \Cora\AbstractFactory class.
*/
class Container extends Collection
{
    public function __construct($parent = false, $data = false, $dataKey = false, $returnClosure = false)
    {
        parent::__construct($data, $dataKey, $parent, $returnClosure);
    }

    public function getFactory($class, $closure = false)
    {
        if ($closure) {
            return new AbstractFactory($this, $closure);
        }
        return new AbstractFactory($this, $this->find($class));
    }
 }