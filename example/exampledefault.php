<?php
use eftec\CliOne\CliOne;

include __DIR__.'/../src/CliOne.php';
include __DIR__.'/../src/CliOneParam.php';

$cli=new CliOne();
if($cli->isCli()) {
    $cli->createParam('param1', [], 'onlyinput')
        ->setDescription('This field is called param1 and it is required')
        ->setInput(true,'string')
        ->setRequired(true)
        ->setCurrentAsDefault()
        ->setDefault('IT MUST NOT BE VISIBLE')
        ->add();
    $cli->setParam('param1','IT MUST BE VISIBLE');
    $param1 = $cli->evalParam('param1',true);
    var_dump($param1->value);
}
