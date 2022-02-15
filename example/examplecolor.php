<?php
/*










<col0/><col1/><col2/><col3/><col4/><col5/>  columns. col0=0 (left),col1--col5 every column of the page.
<option/> it shows all the options available (if the input has some options)
 */

use eftec\CliOne\CliOne;

include __DIR__.'/../src/CliOne.php';
include __DIR__.'/../src/CliOneParam.php';

$cli=new CliOne(); // we create an instance

$cli->createParam('test')->setInput(true,'option',['alpha','beta','gamma'])->add();
$cli->upLevel('level1')->showBread();
$cli->showValuesColumn(['hello','world','alpha','beta','gamma'],'option2');
$cli->upLevel('level2')->showBread();
$cli->showValuesColumn(['col1'=>'hello','col2'=>'world','col3'=>'alpha','col4'=>'beta','col5'=>'gamma'],'option2');
$cli->downLevel()->showBread();
$cli->showLine("<bold>bold</bold>");
$cli->showLine("<bred>background red</bred>");
$cli->showLine("<bblue>background red</bblue>");
$cli->showLine("<bwhite><black>background white</black> </bwhite>");
$cli->showLine("<byellow><blue>background yellow</blue></byellow>");

$cli->showLine("<red>error</red> (color red)");

$cli->showLine("<yellow>warning</yellow> (color yellow)");
$cli->showLine("<blue>information</blue> (blue)");
$cli->showLine("<yellow>yellow</yellow> (yellow)");
$cli->showLine("<green>green</green> (color green)");
$cli->showLine("<italic>italic</italic>");
$cli->showLine("<bold>bold</bold>");
$cli->showLine("<bold><yellow>bold yellow</yellow></bold>");
$cli->showLine("<strikethrough>stike</strikethrough>");
$cli->showLine("<underline>underline</underline>");
$cli->showLine("<cyan>cyan</cyan> (color cyan)");
$cli->showLine("<magenta>magenta</magenta> (color magenta)");
$cli->showLine("<bold><cyan>bold cyan</cyan></bold> (color cyan)");
$cli->showLine("<bold><magenta>bold magenta</magenta></bold> (color magenta)");

$cli->showLine("<bblue><col0/> col0</bblue>");
$cli->showLine("<bblue><col1/> col1</bblue>");
$cli->showLine("<bblue><col2/> col2</bblue>");
$cli->showLine("<bblue><col3/> col3</bblue>");
$cli->showLine("<bblue><col4/> col4</bblue>");
$cli->showLine("<bblue><col1/> col1 <col3/> col3 <col5/> col5</bblue>");
$cli->showLine("The parameters of option are: <option/>",$cli->getParameter('test'));
