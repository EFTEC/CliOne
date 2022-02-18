<?php
use eftec\CliOne\CliOne;

include __DIR__.'/../src/CliOne.php';
include __DIR__.'/../src/CliOneParam.php';

$cli=new CliOne();
if($cli->isCli()) {
    $cli->createParam('param1','none')
        ->setInput(true,'string')
        ->setAddHistory()
        ->setRequired(true)
        ->setDefault('param1')
        ->evalParam(true);
    $cli->createParam('param1','none')
        ->setInput(true,'string')
        ->setAddHistory()
        ->setRequired(true)
        ->setDefault('param1')
        ->evalParam(true);
    $cli->createParam('param1','none')
        ->setInput(true,'string',null,['first','second'])
        ->setAddHistory()
        ->setRequired(true)
        ->setDefault('param1')
        ->evalParam(true);

}
