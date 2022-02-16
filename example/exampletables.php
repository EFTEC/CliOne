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

$cli->showMessageBox(['hello','world','new line','other line'],['title','title long']);
$cli->setStyle('double')->showFrame(['double','world'],['title']);
$cli->setStyle('minimal')->showFrame(['minimal','world'],['minimal title']);
$cli->showFrame(['hello','world']);
$cli->setStyle('mysql')->showFrame(['hello','world'],['title']);
$cli->showLine('<yellow>minimal table blue:</yellow>');
$cli->setStyle('minimal')->setColor(['bblue'])->showTable($values);
$cli->showLine("<yellow>simple table :</yellow>");
$cli->setStyle()->setColor(['bred','yellow','bold'])->showTable($values);
$cli->setColor(['bwhite','black'])->showLine('<yellow>double table :</yellow>');
$cli->setStyle('double')->showTable($values);

$cli->showLine('<yellow>mysql table :</yellow>');
$cli->setStyle('mysql')->showTable($values);

$cli->setAlign('left','left','right')->setStyle('double')->showTable($values);
