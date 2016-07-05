<?php 
namespace Cora;
/**
* 
*/
class Repository
{
    protected $factory;
    protected $gateway;

    public function __construct(Gateway $gateway, Factory $factory)
    {
        $this->gateway = $gateway;
        $this->factory = $factory;
    }
    
    public function getDb()
    {
        return $this->gateway->getDb();
    }

    public function find($id)
    {
        $record = $this->gateway->fetch($id);
        return $this->factory->make($record);
    }

    // Add ability to filter and control results with Cora DB.
    public function findAll()
    {
        $all = $this->gateway->fetchAll();
        return $this->factory->makeGroup($all);
    }
    
    public function findBy($prop, $value, $options = array())
    {
        $all = $this->gateway->fetchBy($prop, $value, $options);
        return $this->factory->makeGroup($all);
    }

    public function findByQuery($coraDbQuery)
    {
        $all = $this->gateway->fetchByQuery($coraDbQuery);
        return $this->factory->makeGroup($all);
    }

    public function delete($id)
    {
        // Get model from DB.
        $model = $this->find($id);

        // Delete any data associated with this model by calling it's own delete method
        // I.E. Notes, file uploads, etc.
        $model->delete();
        
        // Delete the model from the DB.
        $this->gateway->delete($id);
    }


    public function save($model, $table = null, $id_name = null)
    {
        if ($this->checkIfModel($model)) {
            return $this->gateway->persist($model, $table, $id_name);
        }
        else if ($model instanceof \Cora\ResultSet) {
            foreach ($model as $obj) {
                if ($this->checkIfModel($obj)) {
                    $this->gateway->persist($obj, $table, $id_name);
                }
                else {
                    throw new \Exception("Cora's Repository class can only be used with models that extend the Cora Model class.");
                }
            }
        }
        else {
            throw new \Exception("Cora's Repository class can only be used with models that extend the Cora Model class.");
        }
    }
    
    protected function checkIfModel($model)
    {
        if ($model instanceof \Cora\Model) {
            return true;   
        }
        return false;
    }

}
