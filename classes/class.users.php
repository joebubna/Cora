<?php
namespace Classes;

class Users 
{ 
  protected $db;
  protected $userFactory;
  public $type;

  public static function di_config($c, $type)
  {
    return new \Classes\Users(
      $c->{\Cora\Database::class},
      $c->getFactory(\Classes\User::class),
      $type
    );
  }

  public function __construct($db, $userFactory, $type)
  {
    $this->db = $db;
    $this->userFactory = $userFactory;  
    $this->type = $type;
  }

  public function fetch($id) 
  {
    return $this->userFactory->make($id);
  }
}