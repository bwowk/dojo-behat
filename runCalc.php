<?php
require __DIR__ . '/vendor/autoload.php';

use Ciandt\Calc;

$calc = new Calc();

$calc->doOp('+',15);
$calc->doOp('-',5);

echo $calc->equals();

 ?>
