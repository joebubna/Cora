<?php
namespace Cora\Adm;

class LoadMap
{
  protected $localMapping;
  protected $relationsMapping;
  
  public function __construct($localMapping = [], $relationsToLoad = [])
  {
    $this->localMapping = $localMapping;
    $this->relationsMapping = $relationsToLoad;
  }
  
  public function getLocalMapping()
  {
    return $this->localMapping;
  }

  public function getRelationsMapping()
  {
    return $this->relationsMapping;
  }
}