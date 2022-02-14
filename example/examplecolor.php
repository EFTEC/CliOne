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

$cli->showLine("<e>error</e> (color red)");
$cli->showLine("<w>warning</w> (color yellow)");
$cli->showLine("<i>information</i> (blue)");
$cli->showLine("<y>yellow</y> (yellow)");
$cli->showLine("<g>green</g> <s>success</s> (color green)");
$cli->showLine("<italic>italic</italic>");
$cli->showLine("<bold>bold</body>");
$cli->showLine("<underline>underline</underline>");
$cli->showLine("<c>cyan</c> (color light cyan)");
$cli->showLine("<m>magenta</m> (color magenta)");
$cli->showLine("<col0/> col0");
$cli->showLine("<col1/> col1");
$cli->showLine("<col2/> col2");
$cli->showLine("<col3/> col3");
$cli->showLine("<col4/> col4");
$cli->showLine("<col1/> col1 <col3/> col3 <col5/> col5");
$cli->showLine("The parameters of option are: <option/>",$cli->getParameter('test'));
