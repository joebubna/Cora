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

    public function findBy($coraDbQuery)
    {
        $all = $this->gateway->fetchBy($coraDbQuery);
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
        return $this->gateway->persist($model, $table, $id_name);
    }

}
