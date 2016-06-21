<?php 
namespace Cora;
/**
* 
*/
class Factory 
{
	protected $type;
    
    public function __construct($type)
    {
        $this->type = $type;
    }
    
    public function make($data)
	{
		if (empty($data)) {
			return null;
		}
        $this->type = '\\'.$this->type;
		//return new $this->type($data);
        $obj = new $this->type();
        $obj->_populate($data);
        return $obj;
	}

	public function makeGroup($records)
	{
        // Check if a 'CLASSGroup' file exists. If not, then use a regular ResultSet.
		$class = $this->type.'Group';
        if (class_exists($class)) {
            $group = new $class();
        }
        else {
            $group = new ResultSet();
        }
        
		foreach ($records as $record) {
			$group->add($this->make($record));
		}
		return $group;
	}
}