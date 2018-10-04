<?php
namespace Cora\Adm;

class LoadMap
{
  protected $localMapping;
  protected $relationsMapping;
  protected $onLoadFunction;
  protected $onLoadArgs;
  protected $fetchData;
  
  public function __construct($localMapping = [], $relationsToLoad = [], $fetchData = false, $onLoadFunction = false, $onLoadArgs = [])
  {
    $this->localMapping = $localMapping;
    $this->relationsMapping = $relationsToLoad;
    $this->onLoadFunction = $onLoadFunction;
    $this->onLoadArgs = $onLoadArgs;
    $this->fetchData = $fetchData;
  }
  
  /**
   *  @return array
   */
  public function getLocalMapping()
  {
    return $this->localMapping;
  }

  /**
   *  @return array
   */
  public function getRelationsMapping()
  {
    return $this->relationsMapping;
  }

  /**
   *  @return Closure
   */
  public function getOnLoadFunction()
  {
    return $this->onLoadFunction;
  }

  /**
   *  @return array
   */
  public function getOnLoadArgs() 
  {
    return $this->onLoadArgs;
  }

  /**
   *  Returns whether or not the models this LoadMap is used with need their data fetched.
   *  This defaults to false which means it's assumed if you are using a LoadMap that you will be 
   *  providing the data the model(s) need without dynamic loading.
   * 
   *  @return bool
   */
  public function fetchData()
  {
    return $this->fetchData;
  }
}