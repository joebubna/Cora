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

  public function make($data, $loadMap = false)
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
    $obj->_populate($data, $this->db, $loadMap);

    return $obj;
	}

	public function makeGroup($records, $loadMap = false)
	{
    // If the class name lacks a leading slash \, then add the namespaceType.
    if ($this->type[0] != '\\') {
      $type = '\\'.$this->namespaceType.$this->type;
    }

    // If the class is specified like '/Models/User' then do nothing special.
    else {
      $type = $this->type;
    }

    $group = new Collection();
		foreach ($records as $record) {
      // Check if this model has a dataKey to use for its offset within the collection
      $model = $this->make($record, $loadMap);
      if (isset($model->model_collection_offset)) {
        $dataKey = $model->model_collection_offset;
        $model->$dataKey;
      } else {
        $dataKey = false;
      }
			$group->add($model, false, $dataKey);
		}
		return $group;
	}
}
