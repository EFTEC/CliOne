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
for($i=1;$i<200;$i++) {
    $values[] = ['col1' => 'value'.$i, 'col2' => 'value'.$i, 'col3' => "values 1234 #".$i];
}

$cli->showLine("<yellow>simple table :</yellow>");
$cli->setStyle()->setColor(['bred','yellow','bold'])->showTable($values);
$cli->showTable($values);
$cli->showTable($values,false,false,false,5,3,2);
$cli->showTable($values,false,false,false,5,3,3);
$cli->showTable($values,false,false,false,5,3,9);
$cli->showTable($values,false,false,false,5,3,10);
