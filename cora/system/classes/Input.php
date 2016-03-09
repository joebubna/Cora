<?php
namespace Cora;

/**
*
*/
class Input
{

    protected $params;
    protected $postData;
    protected $getData;

    
    public function __construct($data = false)
    {
        if ($data) {
            $this->params = $this->_cleanInput($data);
        }
        
        $this->postData = $this->_cleanInput($_POST);
        $this->getData  = $this->_cleanInput($_GET);
    }

    
    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->name;
        }
        if (isset($this->params[$name])) {
            return $this->params[$name];
        }
        return false;
    }
    
    
    public function post($name = null)
    {
        if (!$name) {
            return $this->postData;
        }
        if (isset($this->postData[$name])) {
            return $this->postData[$name];
        }
        return false;
        
    }
    
    
    public function get($name = null)
    {
        if (!$name) {
            return $this->postData;
        }
        if (isset($this->getData[$name])) {
            return $this->getData[$name];
        }
        return false;
    }
    
    
    public function getData()
    {
        return $this->params;
    }

    
    protected function _cleanInput($data)
    {
        return array_map([$this, '_purify'], $data);
    }

    
    protected function _purify($string)
    {
        if (is_array($string)) {
            return $this->_cleanInput($string);
        }
        $pattern = '/((<[\s\/]*script\b[^>]*>)([^>]*)(<\/script>))/i';
        if (preg_match($pattern, $string)) {
            return false;
        }
        $pattern = '/(<[\s\/]*script\b[^>]*>)/i';
        if (preg_match($pattern, $string)) {
            return false;
        }
        $pattern = '/<\/script>/i';
        if (preg_match($pattern, $string)) {
            return false;
        }
        $pattern = '/data-bind/i';
        if (preg_match($pattern, $string)) {
            return false;
        }
        return $string;
    }
}
