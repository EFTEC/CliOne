<?php /** @noinspection ForgottenDebugOutputInspection */

use eftec\CliOne\CliOne;

include __DIR__.'/../src/CliOne.php';
include __DIR__.'/../src/CliOneParam.php';

$cli=new CliOne();
$cli->createParam('p1',[],'none')
    ->setInput()
    ->setDescription('it is for help','what is the value of p1?',['help line1','help line 2'],'the argument is called p1')
    ->add();
$cli->showParamSyntax2('title',['none'],[],['p1'],'subcategory');
$cli->showParamSyntax('p1');
$p1=$cli->evalParam('p1');


/*$cli->createParam('p1',[],'none')
    ->setInput(true,'option2',['key1'=>'value1','key2'=>'value2','key3'=>'value3','key4'=>'value4'])
    ->add();
$cli->evalParam('p1');
*/

/*$cli->createParam('p1',[],'none')
    ->setInput(true,'multiple',['key1'=>'value1','key2'=>'value2','key3'=>'value3','key4'=>'value4'])
    ->add();
$cli->evalParam('p1');*/

$cli->showLine("value :".json_encode($cli->getValue('p1')));
$cli->showLine("valuekey :".$cli->getValueKey('p1'));
