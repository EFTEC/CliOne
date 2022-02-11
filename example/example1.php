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
    $cli->createParam('subparam0')
        ->setDescription('This field is called subparam0 and it is required')
        ->setRequired(true)
        ->setInput(true,'options',['op1','op2','op3'])
        ->add();
    $cli->createParam('subparam1')
        ->setDescription('This field is called subparam1 and it is required')
        ->setRequired(true)
        ->setInput(true,'option',['op1','op2','op3'])
        ->add();
    $cli->createParam('subparam1b')
        ->setDescription('This field is called subparam1b and it is required')
        ->setRequired(true)
        ->setInput(true,'option2',['op1222222222222222222222222','op2','op3','op4','op5'])
        ->add();
    $cli->createParam('subparam1c')
        ->setDescription('This field is called subparam1b and it is required')
        ->setRequired(true)
        ->setInput(true,'option3',['op1222222222222222222222222','op2','op3','op4','op5','op6','op7','op8','op9','op10'])
        ->add();
    $cli->createParam('subparam2')
        ->setDescription('This field is called subparam2 and it is required','subparam2 (optionshorts)')
        ->setRequired(true)
        ->setInput(true,'optionshort',['yes','no'])
        ->add();
    $cli->createParam('subparam3')
        ->setDescription('This field is called subparam3 and it is required','subparam3 number')
        ->setRequired(true)
        ->setInput(true,'number','')
        ->add();
    $cli->createParam('subparam4')
        ->setDescription('This field is called subparam4 and it is required','subparam4 (range)')
        ->setRequired(true)
        ->setDefault('0')
        ->setInput(true,'range',[0,100])
        ->add();
    $cli->createParam('subparam5')
        ->setDescription('This field is called subparam5 and it has a default value','subparam5')
        ->setRequired(true)
        ->setDefault('hello world')
        ->setInput(true)
        ->add();
    $cli->createParam('param2')
        ->setDescription('This field is called para2 and it is required')
        ->setDefault('hello')
        ->setRequired(true)
        ->add();

    $param1=$cli->evalParam('*');
    if(is_object($param1)) {
        if($param1->key==='param1' && $param1->value!==false) {
            echo "running param1\n";
            $param1_2b=$cli->evalParam('subparam1b');
            $param1_2c=$cli->evalParam('subparam1c');
            $param1_1=$cli->evalParam('subparam0');
            $param1_2=$cli->evalParam('subparam1');

            $param1_3=$cli->evalParam('subparam2');
            $param1_4=$cli->evalParam('subparam3');
            $param1_5=$cli->evalParam('subparam4');
            $param1_6=$cli->evalParam('subparam5',true);
        }
    }
    foreach($cli->parameters as $v) {
        echo "$v->key = ".json_encode($v->value)."\n";
    }
    //var_dump($param1);

/*
    $cli->addSubParameter('subparam2',null,'1','This field is called subparam2 and it is required',true,true,'option',['op1','op2','op3']);
    $cli->addSubParameter('subparam2',null,['yes','no'],'This field is called subparam2 and it is required',true,true,'optionshort',['yes','no']);
    $cli->addSubParameter('subparam3',null,'hello','This field is called subparam3 and it is required (string)',true,true,'string');
    $cli->addSubParameter('subparam4',null,'3','This field is called subparam4 and it is required (number)',true,true,'number');
    $cli->addSubParameter('subparam5','subparam5 (range 0 to 100)','3','This field is called subparam4 and it is required',true,true,'range',[0,100]);
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
