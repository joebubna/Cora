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

    public function __construct($type, $db = false, $namespaceType = 'Models\\')
    {
        $this->type = $type;
        $this->db   = $db;
        $this->namespaceType = $namespaceType;
    }

    public function make($data)
	{
		if (empty($data)) {
			return null;
		}

        // If the class name lacks a leading slash \, then add the namespaceType.
        if ($this->type[0] != '\\') {
            $type = '\\'.$this->namespaceType.$this->type;
        }

        // If the class is specified like '/Models/User' then do nothing special.
        else {
            $type = $this->type;
        }

        // Populate Object with data
        //$type = '\\'.$this->namespaceType.$this->type;
        $obj = new $type();
        $obj->_populate($data, $this->db);

        return $obj;
	}

	public function makeGroup($records)
	{
        // If the class name lacks a leading slash \, then add the namespaceType.
        if ($this->type[0] != '\\') {
            $type = '\\'.$this->namespaceType.$this->type;
        }

        // If the class is specified like '/Models/User' then do nothing special.
        else {
            $type = $this->type;
        }

        // Check if a 'CLASSGroup' file exists. If not, then use a regular ResultSet.
		$class = $type.'Group';
        if (class_exists($class)) {
            $group = new $class();
        }
        else {
            $group = new Collection();
        }

		foreach ($records as $record) {
			$group->add($this->make($record));
		}
		return $group;
	}
}
