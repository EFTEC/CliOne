<?php
use eftec\CliOne\CliOne;

include __DIR__.'/../src/CliOne.php';
include __DIR__.'/../src/CliOneParam.php';

$cli=new CliOne(); // we create an instance
if($cli->isCli()) { // is running under cli? (if not then we do nothing)
    $cli->createParam('param1') // we create a parameter called "param1"
        ->setDescription('This field is called param1 and it is required') // we add a description
        ->setRequired(true) // the mark the value as required
        ->add(); // and finally we add the argument
    $param1 = $cli->evalParam('param1'); // then we evaluated the argument previously created
    if(!$param1->missing) { // is the argument missing?
        echo "The argument is :" . $param1->value; // we show the value of the argument
    }
}
