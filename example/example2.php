<?php
use Eftec\CliOne\CliOne;

include __DIR__.'/../src/CliOne.php';
include __DIR__.'/../src/CliOneParam.php';

$cli=new CliOne();
if($cli->isCli()) {
    $cli->createParam('param1')
        ->setDescription('This field is called param1 and it is required')
        ->setInput(true,'string')
        ->setRequired(true)
        ->setDefault('param1')
        ->add();
    $param1 = $cli->evalParam('param1');
    var_dump($param1->value);
}
