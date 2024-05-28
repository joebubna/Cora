<?php
namespace Classes;

class Test {

  public function sayHi()
  {
    echo 'Hi there from Classes/Test !!!<br>';
  }

  /**
   * Just a method to use in our automated tests
   */
  public function verifyLoad()
  {
    return 99;
  }
}