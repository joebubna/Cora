<?php
namespace Cora;

class SessionStub extends Session
{
    protected $session;
    
    public function __construct($data = false)
    {
        $this->session = $data ?: array();    
    }
    
    public function __get($name)
    {
        if (isset($this->session[$name])) {
            return $this->session[$name];
        }
        return false;
    }
    
    public function __set($name, $value)
    {
        $this->session[$name] = $value;
    }
    
    public function start()
    {
        
    }
    
    public function delete($name)
    {
        unset($this->session[$name]);
    }
    
    public function close()
    {
        
    }
    
    public function destroy()
    {
        
    }
}