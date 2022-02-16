<?php
/*










<col0/><col1/><col2/><col3/><col4/><col5/>  columns. col0=0 (left),col1--col5 every column of the page.
<option/> it shows all the options available (if the input has some options)
 */

use eftec\CliOne\CliOne;

include __DIR__.'/../src/CliOne.php';
include __DIR__.'/../src/CliOneParam.php';

$cli=new CliOne(); // we create an instance
$cli->setColor(['bblue'])->showBread();
$cli->upLevel('level1');

$cli->upLevel('level2',' (hi)');
$cli->upLevel('level3',' (lo)');
$cli->setColor(['bblue'])->showBread();
$cli->setColor([])->showBread();
$cli->setPatternTitle('{value}{type}')
    ->setPatternCurrent('<bred>{value}</bred>{type}')
    ->setPatternSeparator(' -> ')
    ->showBread();

$cli->showMessageBox(['line1','line2'],['title1']);
$cli->setPatternTitle('<bgreen>{value}</bgreen>')
    ->setPatternCurrent('<bred>{value}</bred>')
    ->showMessageBox(['line1','line2','line3'],['title1']);

$cli->showFrame(['line1','line2'],['title1','title2']);



