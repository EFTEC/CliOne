<?php

use Eftec\CliOne\CliOne;

include __DIR__.'/../src/CliOne.php';
include __DIR__.'/../src/CliOneParam.php';
$origin='example1.php';

$cli=new CliOne($origin);
if($cli->isCli()) {
    $cli->createParam('param1')
        ->setDescription('This field is called param1 and it is required')
        ->setRequired(true)
        ->setDefault('param1')
        ->add();
    $cli->createParam('param1','subparam0')
        ->setDescription('This field is called subparam0 and it is required')
        ->setRequired(true)
        ->setInput(true,'options',['op1','op2','op3'])
        ->add();
    $cli->createParam('param1','subparam1')
        ->setDescription('This field is called subparam1 and it is required')
        ->setRequired(true)
        ->setInput(true,'option',['op1','op2','op3'])
        ->add();
    $cli->createParam('param1','subparam2')
        ->setDescription('This field is called subparam2 and it is required','subparam2 (optionshorts)')
        ->setRequired(true)
        ->setInput(true,'optionshort',['yes','no'])
        ->add();
    $cli->createParam('param1','subparam3')
        ->setDescription('This field is called subparam3 and it is required','subparam3 number')
        ->setRequired(true)
        ->setInput(true,'number','')
        ->add();
    $cli->createParam('param1','subparam4')
        ->setDescription('This field is called subparam4 and it is required','subparam4 (range)')
        ->setRequired(true)
        ->setDefault('0')
        ->setInput(true,'range',[0,100])
        ->add();
    $cli->createParam('param2')
        ->setDescription('This field is called para2 and it is required')
        ->setDefault('hello')
        ->setRequired(true)
        ->add();

    $param1=$cli->evalParam('*');
    if(is_object($param1)) {
        if($param1->key==='param1' && $param1->subkey===null && $param1->value!==false) {
            echo "running param1\n";
            $param1_1=$cli->evalParam('param1','subparam0');
            $param1_2=$cli->evalParam('param1','subparam1');
            $param1_3=$cli->evalParam('param1','subparam2');
            $param1_4=$cli->evalParam('param1','subparam3');
            $param1_5=$cli->evalParam('param1','subparam4');
        }
    }
    foreach($cli->parameters as $v) {
        echo "$v->key,$v->subkey = ".json_encode($v->value)."\n";
    }
    //var_dump($param1);

/*
    $cli->addSubParameter('param1','subparam2',null,'1','This field is called subparam2 and it is required',true,true,'option',['op1','op2','op3']);
    $cli->addSubParameter('param1','subparam2',null,['yes','no'],'This field is called subparam2 and it is required',true,true,'optionshort',['yes','no']);
    $cli->addSubParameter('param1','subparam3',null,'hello','This field is called subparam3 and it is required (string)',true,true,'string');
    $cli->addSubParameter('param1','subparam4',null,'3','This field is called subparam4 and it is required (number)',true,true,'number');
    $cli->addSubParameter('param1','subparam5','subparam5 (range 0 to 100)','3','This field is called subparam4 and it is required',true,true,'range',[0,100]);
    $cli->addParameter('param2',null,'','This field is called param2  and it is not required',false,false);*/
  /*  echo <<<EOF
   __                                 _
  /__\__  __  __ _  _ __ ___   _ __  | |  ___
 /_\  \ \/ / / _` || '_ ` _ \ | '_ \ | | / _ \
//__   >  < | (_| || | | | | || |_) || ||  __/
\__/  /_/\_\ \__,_||_| |_| |_|| .__/ |_| \___|
                              |_|  version 1.2
Parameters:

EOF;*/
    //$cli->start();
    //$cli->showparams();
    //$cli->end();
} /** @noinspection PhpStatementHasEmptyBodyInspection */ else {
    // 1) not cli
    // 2) composer is running
    // 3) it is not running the right file.
    // 4) it is running as web.
}
