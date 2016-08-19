<?php
namespace Cora;

class Session
{
    public function __construct($data = false)
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    public function __get($name)
    {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }
        return false;
    }
    
    public function __set($name, $value)
    {
        $_SESSION[$name] = $value;
    }
    
    public function delete($name)
    {
        unset($_SESSION[$name]);
    }
    
    public function close()
    {
        session_write_close();
    }
    
    public function destroy()
    {
        session_destroy();
    }
}