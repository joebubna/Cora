<?php
namespace Controllers\Api;

class Test {

  public function sayHi()
  {
    echo 'Hi there from Controllers/Api/Test !!!<br>';
  }


  /**
   * Just a method to use in our automated tests
   */
  public function verifyLoad()
  {
    return 7;
  }
}