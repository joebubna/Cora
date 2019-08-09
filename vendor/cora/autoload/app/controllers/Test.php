<?php
namespace Controllers;

class Test {

  public function sayHi()
  {
    echo 'Hi there from Controllers/Test !!!<br>';
  }

  /**
   * Just a method to use in our automated tests
   */
  public function verifyLoad()
  {
    return 42;
  }
}