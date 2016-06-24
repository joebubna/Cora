<?php 
namespace Cora;
/**
* 
*/
class Factory
{
	protected $type;
    protected $dbConfig;
    
    public function __construct($type)
    {
        $this->type = $type;
        
//        // Load and set cora config.
//        require(dirname(__FILE__).'/../config/database.php');
//        
//        // Load custom app config
//        if (file_exists($this->config['basedir'].'cora/config/database.php')) {
//            include($this->config['basedir'].'cora/config/database.php');
//        }
//        $this->dbConfig = $dbConfig;
    }
    
    public function make($data)
	{
		if (empty($data)) {
			return null;
		}
        
        // Populate Object with data
        $type = '\\'.$this->type;
        $obj = new $type();
        $obj->_populate($data);
        
        foreach ($obj->model_attributes as $key => $def) {
            if (isset($def['model']) && !is_null($obj->$key)) {
                $repo = \Cora\RepositoryFactory::make($def['model']);
                $relatedObj = $repo->find($obj->$key);
                $obj->$key = $relatedObj;
            }
            if (isset($def['models'])) {
                echo $key;
                //$this->model_data[$key] = $record[$key];
            }
        }
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