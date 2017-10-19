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
    protected $filesData;


    public function __construct($data = false)
    {
        if ($data) {
            $this->params = $this->_cleanInput($data);
        }

        $this->postData = $this->_cleanInput($_POST);
        $this->getData  = $this->_cleanInput($_GET);
        $this->filesData = $this->_cleanFiles($_FILES);
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
            return $this->_cleanInput($_POST);
        }
        if (isset($_POST[$name])) {
            return $this->_purify($_POST[$name]);
        }
        return false;

    }


    public function get($name = null, $defaultValue = null)
    {
        if (!$name) {
            return $this->_cleanInput($_GET);
        }
        if (isset($_GET[$name])) {
            return $this->_purify($_GET[$name]);
        }
        return $defaultValue;
    }

    public function files($name = null)
    {
        if (!$name) {
            return $this->filesData;
        }
        if (isset($this->filesData[$name])) {
            return $this->filesData[$name];
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

    protected function _cleanFiles($data)
    {
        $FileArray = array();
        if (is_array($data)) {
            foreach ($data as $field_name => $files) {
                $errors = $this->_getErrors($files);
                $file_array = $this->rearrange($files);
                if (array_keys($file_array)[0] === 'name') {
                    $file_array = array($file_array);
                }
                $FileArray[$field_name] = $this->_clearErrors($file_array, $errors);
            }
        }
        return $FileArray;
    }

    protected function _getErrors($file)
    {
        $errors = array();
        if (is_array($file['error'])) {
            foreach ($file['error'] as $i => $error_code) {
                if ($error_code != 0) {
                    $errors[] = $i;
                }
            }
        }
        if ($file['error'] != 0) {
            return $errors[] = 0;
        }
        return $errors;
    }

    protected function _clearErrors($FileArray, $errors)
    {
        foreach ($errors as $error) {
            unset($FileArray[$error]);
        }
        return array_values($FileArray);
    }

    public function hasFiles($field = null)
    {
        if ($field) {
            if ($this->files($field)) {
                return true;
            }
            return false;
        }
        foreach ($this->filesData as $files) {
            if ($files && count($this->filesData)) {
                return true;
            }
        }
        return false;
    }

    private function rearrange($file_post)
    {
        $file_array = array();
        if (is_array($file_post['name'])) {
            foreach ($file_post['name'] as $i => $name) {
                foreach (array_keys($file_post) as $key) {
                    $file_array[$i][$key] = $file_post[$key][$i];
                }
            }
            return $file_array;
        }
        return $file_post;
    }

    protected function _purify($input, $encoding = 'UTF-8')
    {
        if (is_array($input)) {
            return $this->_cleanInput($input);
        }
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML401, $encoding);
        // $pattern = '/((<[\s\/]*script\b[^>]*>)([^>]*)(<\/script>))/i';
        // if (preg_match($pattern, $string)) {
        //     return false;
        // }
        // $pattern = '/(<[\s\/]*script\b[^>]*>)/i';
        // if (preg_match($pattern, $string)) {
        //     return false;
        // }
        // $pattern = '/<\/script>/i';
        // if (preg_match($pattern, $string)) {
        //     return false;
        // }
        // $pattern = '/data-bind/i';
        // if (preg_match($pattern, $string)) {
        //     return false;
        // }
        // return $string;
    }
}