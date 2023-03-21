<?php
include  '../vendor/autoload.php';



class ExampleClass {

    public function example() {
        $cli=new \eftec\CliOne\CliOne();
        $cli->addMenu('menu1',null);
        $cli->addMenuItem('option1','option #1');
        $cli->addMenuItem('option2','option #2');
        $cli->evalMenu($this);
    }
    public function menuoption1() {
        var_dump('calling menu option1');
    }
    public function menuoption2() {
        var_dump('calling menu option2');
    }
}

$c=new ExampleClass();
$c->example();
