<?php
use eftec\CliOne\CliOne;

include __DIR__.'/../src/CliOne.php';
include __DIR__.'/../src/CliOneParam.php';

$cli=new CliOne(); // we create an instance
if($cli->isCli()) { // is running under cli? (if not then we do nothing)
    $cli->showLine('it is the stdin:'. $cli->getSTDIN());
    $cli->showLine('it is a message for stdout',null,'stdout');
    $cli->showLine('it is a message for stderr',null,'stderr');
}
