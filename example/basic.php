<?php /** @noinspection ForgottenDebugOutputInspection */

use eftec\CliOne\CliOne;

include __DIR__.'/../src/CliOne.php';
include __DIR__.'/../src/CliOneParam.php';

$cli=new CliOne();
$cli->createParam('read',[],'first')
    ->setDescription('The command','')
    ->add();
$cli->createParam('o',['output','outputresult'],'flag')
    ->setDescription('The output file without extension','',['example: -o file'])
    ->add();

$cli->createParam('pwd',[],'flag')
    ->setDescription('It is the password','what is the password?')
    ->setInput(true,'password')
    ->add();

$cli->createParam('type',[],'flag')
    ->setDescription('it is the type of output','what is the option?',['it is the help1','example: -option xml'])
    ->setInput(true,'option',['json','csv','xml','html'])
    ->add();

$cli->evalParam('read');
$cli->evalParam('o');
$cli->evalParam('pwd');
$cli->evalParam('type');
//var_dump($cli->getParameter('read')->value);
//var_dump($cli->getParameter('o')->value);

$cli->showParamSyntax2('parameters:');
