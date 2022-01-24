<?php

namespace Eftec\CliOne;

use PHPUnit\Framework\TestCase;

class CliOneTest extends TestCase
{

    public function testEvalParam()
    {
        global $argv;
        $t=new CliOne('CliOneTest.php');
        $t->createParam('test1')->add();
        $t->createParam('test2')->add();
        $argv=['-test1','hello','-test2','world'];
        $p=$t->evalParam('test2');
        $this->assertEquals('world',$p->value);

        // test 2.
        $t=new CliOne('CliOneTest.php');
        $t->createParam('test1')->add();
        $t->createParam('test2')->add();
        $argv=['-test1','hello','-test2','world'];
        $p=$t->evalParam('*');
        $this->assertEquals('hello',$p->value);

        // test 3
        $t=new CliOne('CliOneTest.php');
        $t->createParam('test1')->setDefault('not found')->add();
        $argv=[];
        $p=$t->evalParam('test1');
        $this->assertEquals('not found',$p->value);

        // test 4
        $t=new CliOne('CliOneTest.php');
        $t->createParam('test1')->setRequired()->add();
        $argv=[];
        $p=$t->evalParam('test1');
        $this->assertEquals(false,$p->value);
    }


}
