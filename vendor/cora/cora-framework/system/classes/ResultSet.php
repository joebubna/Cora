<?php
namespace Cora;
/**
*
*/

class ResultSet implements \IteratorAggregate, \Countable
{
    
    protected $_results = array();

    public function __construct($data = null)
    {
        //$this->_results = array();
        if (isset($data)) {
            if (is_array($data)) {
                $this->merge($data);
            }
            else {
                $this->add($data);
            }
        }
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->_results);
    }

    public function count()
    {
        return count($this->_results);
    }

    public function add($result)
    {
        $this->_results[] = $result;
    }

    public function contains($key, $value = null)
    {
        foreach($this->_results as $result) {
            if($value) {
                if ($result->$key == $value) {
                    return true;
                }
            } else {
                if($result->$key) {
                     return true;
                }
            }
        }
        return false;
    }

    public function merge($set)
    {
        foreach ($set as $result) {
            $this->add($result);
        }
    }

    public function get($index)
    {
        return array_key_exists($index, $this->_results)
            ? $this->_results[$index] : null;
    }

    public function getByValue($key, $value)
    {
        foreach($this->_results as $result) {
            if($result->$key == $value) {
                return $result;
            }
        }
        return null;
    }

    public function sort($sorter, $dir = Sorter::SORT_DIRECTION_ASCENDING)
    {
        $sorter->setDirection($dir);
        usort($this->_results, array($sorter, $sorter->getCallback()));
    }

    public function getResults()
    {
        return $this->_results;
    }

    public function sumByKey($key)
    {
        $sum = 0;
        foreach ($this->_results as $result) {
            if ($result->$key) {
                $sum += $result->$key;
            }
        }
        return $sum;
    }

    public function remove($item)
    {
        if ($key = array_search($item, $this->_results)) {
            unset($this->_results[$key]);
        }
        return $this->_results;
    }
}
