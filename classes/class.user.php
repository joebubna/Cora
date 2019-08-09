<?php
namespace Classes;

class User 
{
  public $user_id;

  public function __construct($user_id)
  {
    $this->user_id = $user_id;
  }
}