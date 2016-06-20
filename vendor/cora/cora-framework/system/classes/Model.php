<?
namespace Cora;

/**
 *
 */
class Model 
{
    protected $model_data;
    
    public function __construct($record = null)
    {
        //$classProperties = get_object_vars($this);
        if($record) {
            foreach ($this->model_attributes as $key => $def) {
                
                // If the data is present in the DB, assign to model.
                // Otherwise ignore any data returned from the DB that isn't defined in the model.
                if (isset($record[$key])) {
                    $this->model_data[$key] = $record[$key];
                }
            }
        }
    }
    
    
    public function __get($name)
    {
        if (isset($this->model_data[$name])) {
            return $this->model_data[$name];
        }
        
        $class = get_class($this);
        if (property_exists($class, $name)) {
            return $this->{$name};
        }
        return $this->{$name};
        
        // If your DB id's aren't 'id', but instead something like "note_id",
        // but you always want to be able to refer to 'id' within a class.
        if ($name == 'id' && property_exists($class, 'id_name')) {
            if (isset($this->model_data[$this->id_name])) {
                return $this->model_data[$this->id_name];
            }
            return $this->{$this->id_name};
        }
    }
    
    
    public function __set($name, $value)
    {
        // If a matching DB attribute is defined for this model.
        if (isset($this->model_attributes[$name])) {
            $this->model_data[$name] = $value;
        }
        
        // Otherwise if a plain model attribute is defined.
        else {
            $class = get_class($this);
            if (property_exists($class, $name)) {
                $this->{$name} = $value;
            }
        }
 
        // If your DB id's aren't 'id', but instead something like "note_id",
        // but you always want to be able to refer to 'id' within a class.
        if ($name == 'id' && property_exists($class, 'id_name')) {
            $id_name = $this->id_name;
            if (isset($this->model_attributes[$id_name])) {
                $this->model_data[$id_name] = $value;
            }
            else {
                $id_name = $this->id_name;
                $this->{$id_name} = $value;
            }
        }
    }
    
    
    public function delete()
    {
        return true;
    }
}