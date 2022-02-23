<?php
use eftec\CliOne\CliOne;

include __DIR__.'/../src/CliOne.php';
include __DIR__.'/../src/CliOneParam.php';

$cli=new CliOne();
if($cli->isCli()) {
    $cli->createParam('param1')
        ->setDescription('This field is called param1 and it is required')
        ->setInput(true,'string',['op1','op2','op3'])
        ->setRequired(true)
        ->setDefault('param1')
        ->add();
    $param1 = $cli->evalParam('param1');
    var_dump($param1->value);

    $cli->createParam('param2')
        ->setDescription('This field is called param1 and it is required')
        ->setHistory(['op1','op2','op3'])
        ->setInput(true,'option',['op1'=>'op1','op2'=>'op2','op3'=>'op3'])
        ->setRequired(true)
        ->setDefault('param1')
        ->add();
    $cli->setHistory(['op1','op2','op3']);
    $param1 = $cli->evalParam('param2');

    var_dump($param2->value);
}
