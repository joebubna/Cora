<?php 
namespace Cora;
/**
 * 
 */
 class Sorter
 {
 	
	const SORT_DIRECTION_ASCENDING = 'asc';
    const SORT_DIRECTION_DESCENDING = 'desc';

	protected $_direction;
	protected $_strategy;

	public function __construct($strategy, $direction)
    {
        $this->_strategy = $strategy;
        $this->_direction = $direction;
    }
    
    public function compareTo($a, $b)
    {
        if ($a === $b) {
            return 0;
        }
        $augmenter = ($this->_direction == self::SORT_DIRECTION_DESCENDING) ? -1 : 1;
        return $augmenter * $this->_strategy->compareTo($a, $b);
    }

    public function getCallback()
    {
        return 'compareTo';
    }

    public function setDirection($dir)
    {
        $this->_direction = $dir;
    }

    public function getDirection()
    {
        return $this->_direction;
    }

    public function setStrategy($strat)
    {
        $this->_direction = $strat;
    }

    public function getStrategy()
    {
        return $this->_strategy;
    }

    public function getOtherDirection()
    {
        if($this->_direction == 'asc') return 'desc';
        else return 'asc';
    }

    public static function factory($type = null)
    {
		$strategy = new ByValue($type);
        return new Sorter($strategy, '');
    }

}