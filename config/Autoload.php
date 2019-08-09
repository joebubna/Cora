<?php

return [

  'basedir' => realpath(dirname(__FILE__)."/../")."/",

  /**
   *  Class file prefix. I.e. If your class files are named "class.MyClass.inc.php"
   *  then enter 'class.' for Prefix and '.inc' for postfix.
   */
  'classesPrefix' => 'class.',
  'classesPostfix' => '',

  /**
   *  Controller file prefix / postfix.
   */
  'controllersPrefix' => 'controller.',
  'controllersPostfix' => '',

  /**
   *  Model/Class file prefix. I.e. If your class files are named "class.MyClass.inc.php"
   *  then enter 'class.' for Prefix and '.inc' for postfix.
   */
  'modelsPrefix' => 'model.',
  'modelsPostfix' => '',

  /**
   *  View file prefix / postfix.
   */
  'viewsPrefix' => 'view.',
  'viewsPostfix' => '',

  /**
   *  Library file prefix / postfix.
   */
  'librariesPrefix' => 'lib.',
  'librariesPostfix' => '',

  /**
   *  Event file prefix / postfix.
   */
  'eventsPrefix' => 'event.',
  'eventsPostfix' => '',

  /**
   *  Listener file prefix / postfix.
   */
  'listenersPrefix' => 'listen.',
  'listenersPostfix' => '',
];