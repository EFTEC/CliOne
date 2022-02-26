<?php
use eftec\CliOne\CliOne;

include __DIR__.'/../src/CliOne.php';
include __DIR__.'/../src/CliOneParam.php';

$cli=new CliOne();

$cli->showCheck('test','green','it is a test');
$cli->showLine('----');
$cli->showCheck('test','green',['it is a test','second','third']);
$cli->showLine('----');
$cli->showCheck(['[test]','[test22]'],'green',['it is a test','second','third']);
$cli->showLine('----');
$cli->showCheck($cli->makeBigWords('!','atr'),'green',['1)it is a test','2)second','3)third','4)','5)','6)','7)','8)']);
$cli->showLine('----');
$cli->showCheck($cli->makeBigWords('!','atr',true),'green',['1)it is a test trim','2)second','3)third','4)','5)','6)']);
$cli->showLine('----');
$cli->showCheck($cli->makeBigWords('!','znaki'),'green',['it is a test','second','third']);

$cli->showLine('----');
$cli->showCheck($cli->makeBigWords('?','znaki',true),'green',['it is a test','second','third']);

$cli->showLine('----');
$cli->showCheck($cli->makeBigWords('E','znaki'),'red',['it is a test','second','third']);
