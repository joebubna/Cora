<?php
namespace Cora;
/**
*   Has one public method "make" which takes a variable number of arguments and returns an object.
*/
class AbstractFactory
{
	protected $method;
    protected $container;

    public function __construct($container, \Closure $method)
    {
        $this->container = $container;
        $this->method = $method;
    }

    public function __call($name, $arguments)
    {
        if ($name == 'make') {
            // Add a Container reference as first argument.
            array_unshift($arguments, $this->container);

            // Call the callback with the provided arguments.
            return $this->assemble($arguments);
        } else {
            throw new \Exception('No such method');
        }
    }

    protected function assemble($args)
	{
        return call_user_func_array($this->method, $args);
	}
}
