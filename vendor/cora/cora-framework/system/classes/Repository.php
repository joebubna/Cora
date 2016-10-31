<?php 
namespace Cora;
/**
* 
*/
class Repository
{
    protected $factory;
    protected $gateway;
    protected $saveStarted;
    //protected $savedModelsList;

    public function __construct(Gateway $gateway, Factory $factory)
    {
        $this->gateway = $gateway;
        $this->factory = $factory;
        
        $this->saveStarted = &$GLOBALS['coraSaveStarted'];
    }
    
    public function viewQuery($bool = true)
    {
        $this->gateway->viewQuery($bool);
        return $this;
    }
    
    public function getDb($fresh = false)
    {
        return $this->gateway->getDb();
    }

    public function find($id)
    {
        $record = $this->gateway->fetch($id);
        return $this->factory->make($record);
    }
    
    public function findOne($coraDbQuery)
    {
        $all = $this->gateway->fetchByQuery($coraDbQuery);
        return $this->factory->makeGroup($all)->get(0);
    }

    public function findAll($coraDbQuery = false)
    {
        if ($coraDbQuery) {
            $all = $this->gateway->fetchByQuery($coraDbQuery);
        }
        else {
            $all = $this->gateway->fetchAll();
        }
        return $this->factory->makeGroup($all);
    }
    
    public function findBy($prop, $value, $options = array())
    {
        $all = $this->gateway->fetchBy($prop, $value, $options);
        return $this->factory->makeGroup($all);
    }
    
    public function findOneBy($prop, $value, $options = array())
    {
        $all = $this->gateway->fetchBy($prop, $value, $options);
        return $this->factory->makeGroup($all)->get(0);
    }
    
    /**
     *  Count the number of results, optionally with query limiters.
     */ 
    public function count($coraDbQuery = false)
    {
        return $this->gateway->count($coraDbQuery);
    }
    
    /**
     *  Counts the number of results from the last executed query.
     *  Removes any LIMITs.
     */
    public function countPrev()
    {
        return $this->gateway->countPrev();
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
        $return = 0;
        
        // Check whether or not a "save transaction" has been started.
        // If not, start one.
        $clearSaveLockAfterThisFinishes = false;
        if ($this->saveStarted == false) {
            $clearSaveLockAfterThisFinishes = true;
            $this->saveStarted = true;
        }
        
        if ($this->checkIfModel($model)) {
            $return = $this->gateway->persist($model, $table, $id_name);
        }
        else if ($model instanceof \Cora\Container || $model instanceof \Cora\ResultSet) {
            foreach ($model as $obj) {
                if ($this->checkIfModel($obj)) {
                    $this->gateway->persist($obj, $table, $id_name);  
                }
                else {
                    throw new \Exception("Cora's Repository class can only be used with models that extend the Cora Model class. ".get_class($obj)." does not.");
                }
            }
        }
        else {
            throw new \Exception("Cora's Repository class can only be used with models that extend the Cora Model class. " .get_class($model)." does not.");
        }
        
        // Check whether this call to Save should clear the lock.
        //
        // Basically, because when an object is saved, child objects are also recursively saved...
        // To avoid save loops and resaving models that have already been saved once during the current transaction,
        // this save lock is initiated on the original call to Save() on the parent object.
        if ($clearSaveLockAfterThisFinishes) {
            $this->resetSavedModelsList();
            $this->saveStarted = false;
        }  
        return $return;
    }
    
    protected function checkIfModel($model)
    {
        if ($model instanceof \Cora\Model) {
            return true;   
        }
        return false;
    }
    
    protected function resetSavedModelsList()
    {
        $GLOBALS['savedModelsList'] = [];
    }

}
