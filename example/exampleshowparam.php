<?php
use eftec\CliOne\CliOne;

include __DIR__.'/../src/CliOne.php';
include __DIR__.'/../src/CliOneParam.php';

$cli=new CliOne(); // we create an instance

$cli->createParam('p1','flag',['param2','param3']) // we create a parameter called "param1"

->setDescription('This field is called param1 and it is required','?',
    [
        'A2345678 9<red>01234 567890</red>1234567 8902323323E',
        '<yellow>B23456<bold>7890 123456789012 345678 343434 3434 343490E</bold></yellow>'],
    'arg2') // we add a description
->setRequired(true) // the mark the value as required
->add(); // and finally we add the argument
$cli->createParam('param4','longflag',['p5','p6']) // we create a parameter called "param1"
->setDescription('This field is called param4 and it is required','?',['help1','help2'],'arg1') // we add a description
->setRequired(false) // the mark the value as required
->add(); // and finally we add the argument

$cli->createParam('op1','first') // we create a parameter called "param1"
->setDescription('This field is called param4 and it is required','?',['help1','help2']) // we add a description
->setRequired(false) // the mark the value as required
->add(); // and finally we add the argument

//$cli->showparams();
$cli->showParamSyntax2('Flags:',['flag'],[],40);
$cli->showParamSyntax2('Longflags:',['longflag'],[],40);
$cli->showParamSyntax2('Operator:',['first'],[],40);
