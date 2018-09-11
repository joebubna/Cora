<?php
namespace Cora\Adm;

class LoadMap
{
  protected $localMapping;
  protected $relationsMapping;
  protected $onLoadFunction;
  protected $onLoadArgs;
  
  public function __construct($localMapping = [], $relationsToLoad = [], $onLoadFunction = false, $onLoadArgs = [])
  {
    $this->localMapping = $localMapping;
    $this->relationsMapping = $relationsToLoad;
    $this->onLoadFunction = $onLoadFunction;
    $this->onLoadArgs = $onLoadArgs;
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
}