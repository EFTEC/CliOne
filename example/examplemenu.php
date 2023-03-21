<?php
/** @noinspection AutoloadingIssuesInspection */

use eftec\CliOne\CliOne;

include  '../vendor/autoload.php';



class ClassService {
    public function menuHeader($cli) { $cli->upLevel('menu'); $cli->setColor(['byellow'])->showBread(); }
    public function menuFooter($cli) { $cli->downLevel(); }
    public function menuOption1($cli) { $cli->showLine('it is option1'); }
    public function menuOption2($cli) { $cli->showLine('it is option2'); }
}
$obj=new ClassService();

$cli = new CliOne();
$cli->addMenu('menu1', 'header','footer');
$cli->addMenuItem('menu1','option1', 'option #1'
    ,function($cli) {$cli->showLine('calling action1');});
$cli->addMenuItem('menu1','option2', 'option #2');
$cli->addMenuItem('menu1','option3', 'option #3','navigate:menu1.1');
$cli->addMenuItems('menu1',['option4'=>'option #4','option5'=> 'option #5']); // adding multiples options

$cli->addMenu('menu1.1', 'header2','footer2');
$cli->addMenuItem('menu1.1','option1', 'option #1.1');
$cli->addMenuItem('menu1.1','option2', 'option #2.1');
$cli->addMenuItem('menu1.1','option3', 'option #3.1');
$cli->addMenuItem('menu1.1','option4', 'option #4.1');
$cli->addMenuItem('menu1.1','option5', 'option #5.1');
$cli->evalMenu('menu1',$obj); // runs the menu.
$cli->showLine('exit ok');
$cli->clearMenu();
