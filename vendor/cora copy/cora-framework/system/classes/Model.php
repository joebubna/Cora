<?
namespace Cora;

/**
 *
 */
class Model 
{
    public function __construct()
    {
        $classProperties = get_object_vars($this);
        //var_dump($classProperties);
        
        //if ($classProperties)
    }
    
    
    public function __get($name)
    {
        $class = get_class($this);
        if (property_exists($class, $name)) {
            return $this->{$name};
        }
        
        // If your DB id's aren't 'id', but instead something like "note_id",
        // but you always want to be able to refer to 'id' within a class.
        if ($name == 'id' && property_exists($class, 'id_name')) {
            return $this->{$this->id_name};
        }
    }
    
    
    public function __set($name, $value)
    {
        $class = get_class($this);
        if (property_exists($class, $name)) {
            $this->{$name} = $value;
        }
        
        // If your DB id's aren't 'id', but instead something like "note_id",
        // but you always want to be able to refer to 'id' within a class.
        if ($name == 'id' && property_exists($class, 'id_name')) {
            $id_name = $this->id_name;
            $this->{$id_name} = $value;
        }
    }
}