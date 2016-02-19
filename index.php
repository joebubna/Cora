<?php
require('system/Framework.php');

$Cora = new Cora\Framework();
$Cora->routeFind();
$Cora->routeExec();