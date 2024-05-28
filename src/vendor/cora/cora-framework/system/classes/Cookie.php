<?php
namespace Cora;

class Cookie
{   
    protected $expires;
    
    public function __construct($expireTime = false, $domain = '/')
    {
        if ($expireTime) {
            $this->expires = $expireTime;
        }
        else {
            $this->expires = time()+60*60*24*30;
        }
        
        $this->domain = $domain;
    }
    
    public function __get($name)
    {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        }
        return false;
    }
    
    public function __set($name, $value)
    {
        setcookie($name, $value, $this->expires, $this->domain);
    }
    
    public function setRaw($name, $value)
    {
        setrawcookie($name, $value);    
    }
    
    public function delete($name)
    {
        setcookie($name, '', time() - 3600, $this->domain);
    }
}