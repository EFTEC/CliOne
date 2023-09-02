<?php
/*










<col0/><col1/><col2/><col3/><col4/><col5/>  columns. col0=0 (left),col1--col5 every column of the page.
<option/> it shows all the options available (if the input has some options)
 */

use eftec\CliOne\CliOne;

include __DIR__.'/../src/CliOne.php';
include __DIR__.'/../src/CliOneParam.php';

$cli=new CliOne(); // we create an instance

// bar #1
$cli->show('<yellow>loading: ');
for($i=0; $i<=100; ++$i) {
    $cli->setStyle('style')->showProgressBar($i,100,25," $i%");
    usleep(25000);
}
$cli->showLine('</yellow>');
// bar #mysql
$cli->show('<yellow>loading: ')->hideCursor();
for($i=0; $i<=100; ++$i) {
    $cli->setStyle('mysql')->showProgressBar($i,100,25," $i%");
    usleep(25000);
}
$cli->showCursor()->showLine('</yellow>');

// wait #1
$cli->show('<yellow>please wait: ');
$cli->hideCursor()->setStyle('simple','bar3')->showWaitCursor(true);
for($i=0;$i<=100;$i+=5) {
    $cli->showWaitCursor(false,' '.$i.'%');
    usleep(50000);
}
$cli->hideWaitCursor()->showCursor()->showLine('</yellow>');
// wait #2
$cli->show('<yellow>please wait: ');
$cli->hideCursor()->setStyle('simple','pipe')->showWaitCursor();
for($i=0;$i<=100;$i+=5) {
    $cli->showWaitCursor(false,' '.$i.'%');
    usleep(50000);
}
$cli->hideWaitCursor()->showCursor()->showLine('</yellow>');
// wait #2
$cli->show('<yellow>please wait: ');
$cli->hideCursor()->setStyle('simple','triangle')->showWaitCursor();
for($i=0;$i<=100;$i+=5) {
    $cli->showWaitCursor(false,' '.$i.'%');
    usleep(50000);
}
$cli->showCursor()->showLine('</yellow>');
// wait #3
$cli->show('<yellow>please wait: ');
$cli->hideCursor()->setStyle('simple','braille')->showWaitCursor();
for($i=0;$i<=100;$i+=5) {
    $cli->showWaitCursor(false,' '.$i.'%');
    usleep(50000);
}
$cli->showCursor()->showLine('</yellow>');



$cli->show('<green>loading: ');
for($i=0; $i<=100; ++$i) {
    $cli->setStyle()->showProgressBar($i,100,40," $i%");
    usleep(25000);
}
$cli->showLine('</green>');




