<?php
/*










<col0/><col1/><col2/><col3/><col4/><col5/>  columns. col0=0 (left),col1--col5 every column of the page.
<option/> it shows all the options available (if the input has some options)
 */

use eftec\CliOne\CliOne;

include __DIR__.'/../src/CliOne.php';
include __DIR__.'/../src/CliOneParam.php';

$cli=new CliOne(); // we create an instance
$values=[];
$values[]=['col1'=>'value1','col2'=>'value2','col3'=>'value2'];
$values[]=['col1'=>'value12222222222222','col2'=>'value2','col3'=>'value2'];
$values[]=['col1'=>'value1','col2'=>'value2','col3'=>3232];
$values[]=['col1'=>'value1','col2'=>'value2','col3'=>'544554'];

$cli->showMessageBox(['hello','world','new line','other line'],'simple',['title','title long']);
$cli->showFrame(['hello','world'],'double', ['title']);
$cli->showFrame(['hello','world'],'simple');
$cli->showFrame(['hello','world'],'mysql',['title']);
$cli->show('<bblue>');
$cli->showTable($values);
$cli->show('</bblue>');
$cli->showTable($values,'double','left','left','right');
$cli->show('<yellow>loading: ');
for($i=0; $i<=100; ++$i) {
    $cli->showProgressBar($i,100,25," $i%",'mysql');
    usleep(25000);
}
$cli->showLine('</yellow>');

$cli->show('<green>loading: ');
for($i=0; $i<=100; ++$i) {
    $cli->showProgressBar($i,100,40," $i%",'simple');
    usleep(25000);
}
$cli->showLine('</green>');

$cli->show('<yellow>please wait: ');
$cli->showWaitCursor(true);
for($i=0;$i<=100;$i+=5) {
    $cli->showWaitCursor(false,' '.$i.'%');
    usleep(50000);
}
$cli->showLine('</yellow>');


