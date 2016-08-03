<?php 
namespace Cora;
/**
* 
*/
class Factory
{
	protected $type;
    protected $db;
    protected $dbConfig;
    
    public function __construct($type, $db = false)
    {
        $this->type = $type;
        $this->db   = $db;
    }
    
    public function make($data)
	{
		if (empty($data)) {
			return null;
		}
        
        // Populate Object with data
        $type = '\\'.$this->type;
        $obj = new $type();
        $obj->_populate($data, $this->db);
        
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