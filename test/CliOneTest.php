<?php /** @noinspection ForgottenDebugOutputInspection */
/** @noinspection ReturnTypeCanBeDeclaredInspection */
/** @noinspection DuplicatedCode */

namespace eftec\CliOne;

use PHPUnit\Framework\TestCase;

class CliOneTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        CliOne::testUserInput(["input"]);  // we use this line to simulate the user input
        //, the first value is the current value to read
        //, and the next values are the emulated user input
    }

    public function testBread()
    {
        $t = new CliOne();
        $t->showLine('---bread---');
        $t->showBread();
        $t->upLevel('level1', ' (type)');
        $t->showBread();
        $t->downLevel();
        $t->showBread();
        $t->downLevel(3);
        $t->showBread();
        $t->showLine('---bread---');
        $this->assertTrue(true);
    }

    public function testMenu()
    {
        $cli = new CliOne();
        $this->assertEquals(false,CliOne::hasMenu());
        CliOne::testUserInput(null);
        CliOne::testUserInput(['option1','wrong', 'option2', 'option3','option1','','']);
        $cli->addMenu('menu1', 'header','footer');
        $cli->addMenuItem('menu1','option1', 'option #1',
            function($cli) {$cli->showLine('calling action1');$this->assertTrue(true, true);});
        $cli->addMenuItem('menu1','option2', 'option #2');
        $cli->addMenuItem('menu1','option3', 'option #3','navigate:menu1.1');
        $cli->addMenuItems('menu1',['option4'=>'option #4','option5'=> 'option #5']);
        $cli->addMenu('menu1.1', 'header2','footer2');
        $cli->addMenuItem('menu1.1','option1', 'option #1.1');
        $cli->addMenuItem('menu1.1','option2', 'option #2.1');
        $cli->addMenuItem('menu1.1','option3', 'option #3.1');
        $cli->addMenuItem('menu1.1','option4', 'option #4.1');
        $cli->addMenuItem('menu1.1','option5', 'option #5.1');
        $cli->addMenuService('menu1',$this);
        $cli->evalMenu('menu1');
        $cli->showLine('exit ok');
        $cli->clearMenu();
    }

    public function testMenuCallable()
    {
        $cli = new CliOne();
        CliOne::testUserInput(null);
        CliOne::testUserInput(['option1','wrong', 'option2', 'option3','option1','','']);
        $cli->addMenu('menu1',
            function($cli) { $cli->upLevel('menu1'); $cli->showBread();},
            function($cli) { $cli->downLevel();});
        $cli->addMenuItem('menu1','option1', 'option #1','action1');
        $cli->addMenuItem('menu1','option2', 'option #2');
        $cli->addMenuItem('menu1','option3', 'option #3','navigate:menu1.1');
        $cli->addMenuItems('menu1',['option4'=>'option #4','option5'=> 'option #5']);
        $cli->addMenu('menu1.1', 'header2','footer2');
        $cli->addMenuItem('menu1.1','option1', 'option #1.1');
        $cli->addMenuItem('menu1.1','option2', 'option #2.1');
        $cli->addMenuItem('menu1.1','option3', 'option #3.1');
        $cli->addMenuItem('menu1.1','option4', 'option #4.1');
        $cli->addMenuItem('menu1.1','option5', 'option #5.1');

        $cli->evalMenu('menu1',$this);
        $cli->showLine('exit ok');
        $cli->clearMenu();
    }
    public function menuHeader(CliOne $cli) {

        $cli->upLevel('menu1');
        $cli->showBread();
    }
    public function menuHeader2(CliOne $cli) {

        $cli->upLevel('menu2');
        $cli->showBread();
    }
    public function menuFooter(CliOne $cli) {
        $cli->downLevel();
    }
    public function menuFooter2(CliOne $cli) {
        $cli->downLevel();
    }
    public function menuoption1()
    {
        $this->assertTrue(true, true);
    }
    public function menuaction1(CliOne $cli)
    {
        $cli->showLine('calling action1');
        $this->assertTrue(true, true);
    }
    public function menuoption2()
    {
        $this->assertTrue(true, true);
    }

    public function testVariables()
    {
        $t = new CliOne();
        $t->setVariable('v1', 'hello');
        $t->addVariableCallBack('call1', function() {
            CliOne::instance()->setVariable('v2', 'world');
        });
        CliOne::instance()->callVariablesCallBack();
        $this->assertEquals('v1:hello,v2:world', $t->colorText('v1:{{v1}},v2:{{v2}}'));
    }

    public function testMemory()
    {
        CliOne::testUserInput(null);
        CliOne::testArguments(['program.php', '-dosave', 'xxxx']); // this value must be ignored
        CliOne::testUserInput(['bbb', 'yes']);
        $t = new CliOne();
        $t->setNoANSI();
        $t->echo = false;
        $t->showLine('<red>1)hello world</red>');
        $this->assertEquals("\e[31m1)hello world\e[39m\n", $t->getMemory());
        $t->showLine('<red>2)hello world</red>');
        $this->assertEquals("\e[31m1)hello world\e[39m\n\e[31m2)hello world\e[39m\n", $t->getMemory(true));
        $t->setNoColor();
        $t->showLine('<red>hello world</red>');
        $this->assertEquals("hello world\n", $t->getMemory(true));
        $this->assertEquals(true, $t->isNoANSI());
        $this->assertEquals(true, $t->isNoColor());
    }

    public function testCurly()
    {
        CliOne::testUserInput(null);
        CliOne::testArguments([]);
        $t = new CliOne();
        $t->setVariable('var1', 'it is the var1');
        $t->setVariable('varcolor', '<red>red</red>');
        $this->assertEquals("var1:it is the var1,{{var2}}", $t->colorText('var1:{{var1}},{{var2}}'));
        $this->assertEquals("color:\e[31mred\e[39m", $t->colorText('color:{{varcolor}}'));
    }

    public function testWrap()
    {
        CliOne::testUserInput(null);
        CliOne::testArguments([]);
        $t = new CliOne();
        $this->assertEquals("\e[36mhello\e[39m", $t->colorText('<cyan>hello</cyan>'));
        $t->initialEndStyle($t->colorText('<cyan>hello</cyan>'), $initial, $end);
        $this->assertEquals("\e[36m", $initial);
        $this->assertEquals("\e[39m", $end);
        $this->assertEquals([0 => 'it is a',
            1 => 'test',
            2 => 'example',
            3 => 'one two'],
            $t->wrapLine([$t->colorText('it is a test example one two')], 10));
        $this->assertEquals([
            0 => 'it is a',
            1 => 'test',
            2 => 'example',
            3 => 'one two',
            4 => 'it is a',
            5 => 'test',
            6 => 'example',
            7 => 'one two'],
            $t->wrapLine([
                'it is a test example one two',
                'it is a test example one two'], 10)
        );
        $this->assertEquals([
            "\e[36mit is a\e[39m",
            1 => "\e[36mtest\e[39m",
            2 => "\e[36mexample\e[39m",
            "\e[36mone two\e[39m"
        ],
            $t->wrapLine([$t->colorText('<cyan>it is a test example one two</cyan>')], 10, true));
        $this->assertEquals([
            0 => 'it is a',
            1 => 'test',
            2 => 'example',
            3 => 'one two',
            4 => 'it is a',
            5 => 'test',
            6 => 'example',
            7 => 'one two'],
            $t->wrapLine([
                'it is a test example one two',
                'it is a test example one two'], 10)
        );
        $this->assertEquals([
            "\e[36mit is a test example one two\e[39m"
        ],
            $t->wrapLine([$t->colorText('<cyan>it is a test example one two</cyan>')], 100, true));
    }

    public function testArgumentAsKey()
    {
        CliOne::testUserInput(null);
        CliOne::testArguments(['program.php', '-flag', 'key1', '--flag2', 'key2']); // this value must be ignored
        $t = new CliOne();
        $t->createParam('flag')->setArgumentIsValueKey()
            ->setInput(false, 'option', ['key1' => 'value1', 'key2' => 'value2'])
            ->add();
        $t->createParam('flag2')->setArgument('longflag', true)
            ->setInput(false, 'option', ['key1' => 'value1', 'key2' => 'value2'])
            ->add();
        $this->assertEquals('value1', $t->evalParam('flag', false, true));
        $this->assertEquals('value2', $t->evalParam('flag2', false, true));
        // #2
    }

    public function testHistory()
    {
        CliOne::testUserInput(null);
        CliOne::testArguments(['program.php', '-dosave', 'xxxx']); // this value must be ignored
        CliOne::testUserInput(['bbb', 'yes']);
        $t = new CliOne();
        $t->createParam('aaa', [], 'none')->setInput()->setAddHistory()->evalParam();
        CliOne::testUserInput(['ccc', 'yes']);
        $t->createParam('aaa', [], 'none')->setInput()->setAddHistory()->evalParam();
        if (PHP_MAJOR_VERSION <= 7 && PHP_MINOR_VERSION < 3) {
            $this->assertEquals([], $t->listHistory());
        } else {
            $this->assertEquals(['bbb', 'ccc'], $t->listHistory());
            $t->clearHistory();
            $this->assertEquals([], $t->listHistory());
            $t->setHistory(['bbb', 'ccc']);
            $this->assertEquals(['bbb', 'ccc'], $t->listHistory());
            $t->createParam('aaa4', [], 'none')->setInput()->setHistory(['a', 'b', 'c'])->setAddHistory()->evalParam();
            $this->assertEquals(['a', 'b', 'c'], $t->getParameter('aaa4')->getHistory());
            $t->setHistory(['bbb', 'ccc']);
            $this->assertEquals(['bbb', 'ccc'], $t->listHistory());
        }
    }

    public function testMisc()
    {
        $t = new CliOne();
        $this->assertGreaterThan(20, $t->getColSize());
        $this->assertNotEmpty(CliOne::VERSION);
        $this->assertStringContainsString('vendor', CliOne::findVendorPath());
    }

    public function testDefault()
    {
        CliOne::testUserInput(null);
        CliOne::testArguments([]);
        CliOne::testUserInput([""]);
        $t = new CliOne();
        $t->createParam('t1', [], 'none')->setCurrentAsDefault()->setDescription('', 'select value:')->setInput(true, 'option', ['a' => 1, 'b' => 2, 'c' => 3])->add();
        $t->getParameter('t1')->setValue(null, 'b');
        $v = $t->evalParam('t1', true);
        $this->assertEquals(2, $v->value);
        $this->assertEquals('b', $v->valueKey);
        $this->assertEquals(['t1' => 2], $t->getValueAsArray());
        $t->setParamUsingArray(['param2' => 'hello', 'param3' => 'world', 't1' => 'changed']);
        $this->assertEquals('hello', $t->getValue('param2'));
        $this->assertEquals('world', $t->getValue('param3'));
        $this->assertEquals(['t1' => 'changed', 'param2' => 'hello', 'param3' => 'world'], $t->getValueAsArray());
        CliOne::testUserInput(null);
        CliOne::testArguments([]);
        CliOne::testUserInput([""]);
        $t = new CliOne();
        $t->createOrReplaceParam('t1', [], 'none')->setCurrentAsDefault()->setDescription('', 'select value:')->setInput(true, 'optionshort', ['a', 'b', 'c'])->add();
        $t->getParameter('t1')->setValue('b');
        $v = $t->evalParam('t1', true);
        $this->assertEquals('b', $v->value);
        //$this->assertEquals('b',$v->valueKey);
    }

    public function testArgNone()
    {
        CliOne::testUserInput(null);
        CliOne::testArguments(['program.php', '-dosave', 'xxxx']); // this value must be ignored
        CliOne::testUserInput(['bbb', 'yes']);
        $t = new CliOne();
        $this->assertEquals('yes'
            , $t->createParam('dosave', [], 'none')
                ->setRequired(false)
                ->setDefault('false')
                ->setInput(true, 'optionshort', ['yes', 'no'])
                ->setDescription('', 'Do you want to save?')
                ->evalParam(true, true));
    }

    public function testFile2()
    {
        $t = new CliOne();
        $t->createParam('test1')->add();
        $t->createParam('test2')->add();
        $t->getParameter('test1')->value = 'hello';
        $this->assertEquals('', $t->saveDataPHPFormat(__DIR__ . '/file1php', $t->getArrayParams()));
        $this->assertEquals([true, ['test1' => 'hello', 'test2' => null], '$config'], $t->readDataPHPFormat(__DIR__ . '/file1php'));
    }

    public function testFile()
    {
        $t = new CliOne();
        $t->createParam('test1')->add();
        $t->createParam('test2')->add();
        $t->getParameter('test1')->value = 'hello';
        $this->assertEquals('', $t->saveData(__DIR__ . '/file1', $t->getArrayParams()));
        $t->getParameter('test1')->value = 'xxxxxxx';
        $rd = $t->readData('file2');
        $this->assertEquals([false, 'Unable to read file file2.config.php'], $rd);
        $rd = $t->readData(__DIR__ . '/file1');
        $this->assertEquals([true, ['test1' => 'hello', 'test2' => null]], $rd);
        $t->setArrayParam($rd[1]);
        $this->assertEquals('hello', $t->getParameter('test1')->value);
        $t->getParameter('test1')->value = 'xxxxxxx';
        $t->setArrayParam($rd[1], ['test1']);
        $this->assertEquals('xxxxxxx', $t->getParameter('test1')->value);
        $t->getParameter('test1')->value = 'xxxxxxx';
        $t->setArrayParam($rd[1], [], ['test1']);
        $this->assertEquals('hello', $t->getParameter('test1')->value);
        $t->getParameter('test1')->value = 'xxxxxxx';
        $t->setArrayParam($rd[1], [], ['test2']);
        $this->assertEquals('xxxxxxx', $t->getParameter('test1')->value);
    }

    public function testhasColor()
    {
        CliOne::testUserInput(null);
        CliOne::testArguments(['program.php', '--test1b', 'hello', '-test2', '"hello world"']);
        $t = new CliOne();
        $this->assertEquals(true, $t->hasColorSupport());
    }

    public function testEvalAlias()
    {
        CliOne::testUserInput(null);
        CliOne::testArguments(['program.php', '--test1b', 'hello', '-test2', '"hello world"']);
        $t = new CliOne();
        $t->createParam('test1', ['test1b'])->add();
        $this->assertEquals('hello', $t->evalParam('test1')->value);
    }

    public function testEvalFirstParam()
    {
        CliOne::testUserInput(null);
        CliOne::testArguments(['program.php',
            'firstop',
            'testop2',
            '-test1',
            'hello',
            '-test2',
            '"hello world"',
            '--test3',
            '"hello world"',
            'testop3']);
        $t = new CliOne();
        $t->createParam('firstop', [], 'first')->setRequired(false)->setAllowEmpty()->add();
        $this->assertEquals('firstop', $t->evalParam('firstop')->value);
        $t->createParam('com', [], 'command')->setRequired(false)->setAllowEmpty()->add();
        $this->assertEquals('firstop', $t->evalParam('com')->value);
        $t->createParam('secondop', [], 'first')->setRequired(false)->setAllowEmpty()->add();
        $this->assertEquals(false, $t->evalParam('secondop')->value);
    }

    public function testEvalParam()
    {
        CliOne::testUserInput(null);
        CliOne::testArguments(['program.php',
            'testopxxx',
            'testop2',
            '-test1',
            'hello',
            '-test2',
            '"hello world"',
            '--test3',
            '"hello world"',
            'testop3']);
        $t = new CliOne();
        $t->createParam('testop', [], 'first')->setRequired(false)->setAllowEmpty()->add();
        $t->createParam('testop2', [], 'second')->setRequired(false)->setAllowEmpty()->add();
        $t->createParam('testop3', [], 'last')->setRequired(false)->setAllowEmpty()->add();
        $t->createParam('test1')->add();
        $t->createParam('test2')->add();
        $t->createParam('test3', [], 'longflag')->add();
        $this->assertEquals(false, $t->evalParam('testop')->value);
        $this->assertEquals('testop2', $t->evalParam('testop2')->value);
        $this->assertEquals('testop3', $t->evalParam('testop3')->value);
        $this->assertEquals('hello', $t->evalParam('test1')->value);
        $this->assertEquals('hello world', $t->evalParam('test2')->value);
        $this->assertEquals('hello world', $t->evalParam('test3')->value);
        // test 2.
        CliOne::testArguments(['program.php', '-test1', 'hello', '-test2', 'world']);
        $t = new CliOne();
        $t->createParam('test1')->add();
        $t->createParam('test2')->add();
        $p = $t->evalParam('test1');
        $this->assertEquals('hello', $p->value);
        // test 3
        CliOne::testArguments(['program.php',]);
        $t = new CliOne();
        $t->createParam('test1')->setDefault('not found')->add();
        $p = $t->evalParam('test1');
        $this->assertEquals('not found', $p->value);
        // test 3b
        CliOne::testArguments(['program.php',]);
        CliOne::testUserInput(['']);
        $t = new CliOne();
        $t->createParam('test1b')->setDescription('', 'desc:')->setInput()->setDefault('not found')->setAllowEmpty()->add();
        $p = $t->evalParam('test1b', true);
        $this->assertEquals('not found', $p->value);
        CliOne::testUserInput(null);
        // test 4
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        $t->createParam('test1')->setRequired()->add();
        $p = $t->evalParam('test1');
        $this->assertEquals(false, $p->value);
        // test 5
        CliOne::testArguments(['program.php', '-test1', 'apple']);
        $t = new CliOne();
        $t->createParam('test1')->setRequired()->add();
        $t->setParam('test1', 'hello world');
        $p = $t->evalParam('test1');
        $this->assertEquals('apple', $p->value);
        // test 5 it test setparam() with a value-key
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        $t->createParam('test1k')->setInput(true, 'option', ['h1' => 'hello', 'w1' => 'world'])->setRequired(false)->add();
        $t->setParam('test1k', 'h1', true);
        $this->assertEquals('hello', $t->getValue('test1k'));
        $t->createParam('test2k')->setInput(true, 'option', ['h1' => 'hello', 'w1' => 'world'])->setRequired(false)->add();
        $t->setParam('test2k', 'hello');
        $this->assertEquals('h1', $t->getValueKey('test2k'));
        // test 5
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        $t->createParam('test1')->setRequired()->add();
        $t->setParam('test1', 'hello world2');
        $p = $t->evalParam('test1');
        $this->assertEquals('hello world2', $p->value);
    }

    public function testSyntax()
    {
        CliOne::testArguments([]);
        CliOne::testUserInput(null);
        $t = new CliOne();
        $t->createParam('param1', 'p1', 'longflag')->setDescription('desc1')->add();
        $t->createParam('param2', 'p2', 'longflag')->setDescription('desc2')->add();
        $t->createParam('param3', 'p3', 'first')->setDescription('desc2')->add();
        $t->createParam('paramv1', 'v1', 'longflag')->setDescription('descv1')->setRelated('p3')->add();
        $t->createParam('paramv2', 'v2', 'longflag')->setDescription('descv2')->setRelated('p3')->add();
        $t->setDefaultStream('memory')->setNoColor()->showParamSyntax2('title1', ['flag', 'longflag']);
        $this->assertEquals("title1\n --param1, -p1 desc1 [(null)]\n --param2, -p2 desc2 [(null)]\n" .
            " --paramv1, -v1 descv1 [(null)]\n --paramv2, -v2 descv2 [(null)]\n", $this->removespaces($t->getMemory(true)));
        $t->setDefaultStream('memory')->setNoColor()->showParamSyntax2('title1', ['flag', 'longflag'], [], null, 'p3');
        $this->assertEquals("title1\n --paramv1, -v1 descv1 [(null)]\n --paramv2, -v2 descv2 [(null)]\n", $this->removespaces($t->getMemory(true)));
    }

    /** @noinspection CascadeStringReplacementInspection */
    public function removespaces($text)
    {
        $text = str_replace(['    ', '   ', '  ', "\t"], ' ', $text);
        $text = str_replace(['    ', '   ', '  '], ' ', $text);
        $text = str_replace(['    ', '   ', '  '], ' ', $text);
        return str_replace(['    ', '   ', '  '], ' ', $text);
    }

    public function testInput()
    {
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        CliOne::testUserInput(['hello world']);         // we use this line to simulate the user input
        $t->createParam('test1')->setDescription('it is a test')->setInput()->add();
        $p = $t->evalParam('test1');
        $this->assertEquals('hello world', $p->value);
        $t->createParam('com', [], 'command')->setRequired(false)->setAllowEmpty()->add();
        $this->assertEquals(false, $t->evalParam('com')->value);
    }

    public function testInputDefault2()
    {
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        CliOne::testUserInput(['']);         // we use this line to simulate the user input
        $t->createParam('test1')
            ->setValue('defvalue')
            ->setDefault('def')
            ->setInput()
            ->setCurrentAsDefault()
            ->add();
        $this->assertEquals('defvalue', $t->evalParam('test1', true)->value);
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        CliOne::testUserInput(['']);         // we use this line to simulate the user input
        $t->createParam('test1')
            ->setValue(null)
            ->setDefault('def')
            ->setInput()
            ->setCurrentAsDefault()
            ->add();
        $this->assertEquals('def', $t->evalParam('test1', true)->value);
    }

    public function testBasic()
    {
        CliOne::testArguments(['program.php', 'aaa', '-bbb', 'ccc', '-ddd']);
        $t = new CliOne();
        CliOne::testUserInput(['hello world']);
        $this->assertEquals('php program.php aaa -bbb ccc', $t->reconstructPath(true, 4));
        $this->assertEquals('program.php', $t->getPhpOriginalFile());
        $t->setPhpOriginalFile('dummy.php');
        $this->assertEquals('dummy.php', $t->getPhpOriginalFile());
    }

    public function testInputDefault()
    {
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        CliOne::testUserInput(['hello world']);         // we use this line to simulate the user input
        $t->createParam('test1')->setDescription('it is a test')->setInput()->add();
        $this->assertEquals('hello world', $t->evalParam('test1', true)->value);
        $t->getParameter('test1')->setDefault($t->getParameter('test1')->value);
        CliOne::testUserInput(['', 'xxxx']);         // we use this line to simulate the user input
        $this->assertEquals('hello world', $t->evalParam('test1', true)->value);
        $t->getParameter('test1')->setCurrentAsDefault();
        CliOne::testUserInput(['', 'xxxx']);         // we use this line to simulate the user input
        $this->assertEquals('hello world', $t->evalParam('test1', true)->value);
        CliOne::testUserInput(['', 'xxxx']);         // we use this line to simulate the user input
        $t->createParam('test10')->add();
        $t->setParam('test10', '1234');
        $t->getParameter('test10')->missing = true;
        $this->assertEquals('1234', $t->getParameter('test10')
            ->setCurrentAsDefault()
            ->setDefault('defaultvalue')
            ->evalParam(true)->value);
        CliOne::testUserInput(['', 'xxxx']);         // we use this line to simulate the user input
        $t->createParam('test11')->add();
        $t->getParameter('test11')->missing = true;
        $this->assertEquals('defaultvalue', $t->setParam('test11', 'setvalue')
            ->setDefault('defaultvalue')
            ->setCurrentAsDefault(false)
            ->evalParam(true)
            ->value);
    }

    public function testcurrentAsDefault()
    {
        CliOne::testArguments(['program.php']);
        $cli = new CliOne();
        CliOne::testUserInput(['']);
        $cli->createParam('param1', [], 'onlyinput')
            ->setDescription('This field is called param1 and it is required')
            ->setInput()
            ->setRequired()
            ->setCurrentAsDefault()
            ->setDefault('IT MUST NOT BE VISIBLE')
            ->add();
        $cli->setParam('param1', 'IT MUST BE VISIBLE');
        $param1 = $cli->evalParam('param1', true);
        $this->assertEquals('IT MUST BE VISIBLE', $param1->value);
        CliOne::testUserInput(['']);
        $cli->createParam('param2', [], 'onlyinput')
            ->setDescription('This field is called param1 and it is required')
            ->setInput()
            ->setRequired()
            ->setCurrentAsDefault()
            ->setDefault('IT MUST BE VISIBLE2')
            ->add();
        var_dump($cli->getParameter('param2')->value);
        //$cli->setParam('param2','IT MUST BE VISIBLE');
        $param2 = $cli->evalParam('param2', true);
        $this->assertEquals('IT MUST BE VISIBLE2', $param2->value);
    }

    public function testCut()
    {
        $txt = 'C1234 123456789 123456789 123456789 123456789 123456789 123456789 123456789 123456789';
        $w = 34;
        $t = new CliOne();
        $r = $t->wrapLine($txt, $w, true);
        $this->assertEquals([
            //0        1        2         3
            //234567890123456789012345678901234
            'C1234 123456789 123456789',
            '123456789 123456789 123456789',
            '123456789 123456789 123456789'], $r);
    }

    public function testValid()
    {
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        $t->setErrorType('silent');
        $this->assertEquals('silent', $t->getErrorType());
        $this->assertEquals(true, $t->createParam('param1')->add());
        $this->assertEquals(true, $t->getParameter('param1')->isValid());
        $this->assertEquals(false, $t->createParam('param1')->add());
        $this->assertEquals(false, $t->getParameter('helloworld')->isValid());
        $this->assertEquals(true, $t->createParam('multi1', ['alpha', 'beta'])->add());
        $this->assertEquals(false, $t->createParam('alpha', ['a', 'b'])->add());
        $this->assertEquals(false, $t->createParam('gamma', ['alpha'])->add());
        $this->assertEquals(false, $t->createParam('delta', ['alpha'])->add());
    }

    public function testInputV2()
    {
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        CliOne::testUserInput(['hello world']);         // we use this line to simulate the user input
        $p = $t->createParam('test1')->setDescription('it is a test', 'test #1')->setInput()->evalParam(true);
        $this->assertEquals('hello world', $p->value);
        CliOne::testUserInput(['hello world']);         // we use this line to simulate the user input
        $p = $t->createParam('test1')->setDescription('it is a test', 'test #2')->setInput()->evalParam(true);
        $this->assertEquals('hello world', $p->value);
    }

    public function testEscape()
    {
        $cli = new CliOne();
        $txt = "<red><bold>hello</bold></red>";
        $this->assertEquals("\e[31m\e[01mhello\e[22m\e[39m", $cli->colorText($txt));
        $this->assertEquals("hello", $cli->colorLess($cli->colorText($txt)));
        $this->assertEquals(str_repeat(chr(250), 10) . "hello" . str_repeat(chr(250), 10), $cli->colorMask($cli->colorText($txt)));
    }

    public function testVisual()
    {
        CliOne::testArguments(['program.php']);
        $cli = new CliOne();
        $cli->showFrame(['line1', 'line2', 'line3'], ['title1', 'title2']);
        $this->assertEquals(["", "a", ""], $cli->alignLinesVertically(["a"], 3));
        $this->assertEquals(["", "a", "", ""], $cli->alignLinesVertically(["a"], 4));
        $cli->showMessageBox(['line1', 'line2'], ['title1', 'title2']);
        $cli->showMessageBox(["one", "two", "three"], ["title"]);
        $cli->showMessageBox(["content"], ["one", "two", "three"]);
        $values = [];
        $values[] = ['col1' => 'value1', 'col2' => 'value2', 'col3' => 'value2'];
        $values[] = ['col1' => 'value12222222222222', 'col2' => 'value2', 'col3' => 'value2'];
        $values[] = ['col1' => 'value1', 'col2' => 'value2', 'col3' => 3232];
        $values[] = ['col1' => 'value1', 'col2' => 'value2', 'col3' => '544554'];
        $cli->showTable($values);
        $cli->setStyle('mysql')->showTable($values);
        $cli->setStyle('double')->showTable($values);
        $cli->setStyle('minimal')->showTable($values);
        $cli->setStyle('minimal')->showValuesColumn($values, 'option3');
        for ($i = 0; $i <= 100; $i += 20) {
            $cli->setStyle()->showProgressBar($i, 100, 40, " $i%");
        }
        $this->assertTrue(true);
    }

    public function testInputOptionDefaultError()
    {
        $values = ['k1' => 'v1', 'k2' => 'v2', 'k3' => 'v3'];
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        CliOne::testUserInput(['X', 'k1']);         // we use this line to simulate the user input
        $t->createParam('test1')
            ->setDescription('it is a test')
            ->setAllowEmpty()
            ->setInput(true, 'option', $values)->add();
        $t->showparams();
        $p = $t->evalParam('test1');
        $this->assertEquals('v1', $p->value);
        $this->assertEquals('v1', $t->getValue('test1'));
        $this->assertEquals('k1', $t->getValueKey('test1'));
    }

    public function testisParamPresent()
    {
        CliOne::testArguments(['program.php', '-flag1', '--flag3', '--flag4', 'value', '-f6', 'value2']);
        $t = new CliOne();
        $t->createParam('flag1')->add();
        $t->createParam('flag2')->add();
        $t->createParam('flag3', [], 'longflag')->add();
        $t->createParam('flag4', [], 'longflag')->add();
        $t->createParam('flag6', 'f6', 'longflag')->add();
        $t->createParam('flag7', 'f7', 'longflag')->add();
        $this->assertEquals('empty', $t->isParameterPresent('flag1'));
        $this->assertEquals('none', $t->isParameterPresent('flag2'));
        $this->assertEquals('empty', $t->isParameterPresent('flag3'));
        $this->assertEquals('value', $t->isParameterPresent('flag4'));
        $this->assertEquals('none', $t->isParameterPresent('flag5'));
        $this->assertEquals('value', $t->isParameterPresent('flag6'));
        $this->assertEquals('none', $t->isParameterPresent('flag7'));
    }

    public function testInputOption()
    {
        $values = ['op1aaaaaaaa', 'op2', 'op3', 'op4', 'op5', 'op6', 'op7'];
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        CliOne::testUserInput(['X', '3']);         // we use this line to simulate the user input
        $t->createParam('test1')->setDescription('it is a test')->setInput(true, 'option', $values)->add();
        $t->showparams();
        $p = $t->evalParam('test1');
        $this->assertEquals('op3', $p->value);
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        CliOne::testUserInput(['X', '10', '', '3']);         // we use this line to simulate the user input
        $t->createParam('test1')->setDescription('it is a test')->setInput(true, 'option', $values)->add();
        $t->showparams();
        $p = $t->evalParam('test1');
        $this->assertEquals('op3', $p->value);
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        CliOne::testUserInput(['X', '10', '', '3']);         // we use this line to simulate the user input
        $t->createParam('test1')
            ->setDescription('it is a test')
            ->setDefault('')
            ->setAllowEmpty()
            ->setInput(true, 'option', $values)
            ->add();
        $t->showparams();
        $p = $t->evalParam('test1');
        $this->assertEquals('', $p->value);
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        CliOne::testUserInput(['X', '3']);         // we use this line to simulate the user input
        $t->createParam('test1')->setDescription('it is a test')->setInput(true, 'option2', $values)->add();
        $t->showparams();
        $p = $t->evalParam('test1');
        $this->assertEquals('op3', $p->value);
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        CliOne::testUserInput(['X', '3']);         // we use this line to simulate the user input
        $t->createParam('test1')->setDescription('it is a test')->setInput(true, 'option3', $values)->add();
        $t->showparams();
        $p = $t->evalParam('test1');
        $this->assertEquals('op3', $p->value);
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        CliOne::testUserInput(['X', '3']);         // we use this line to simulate the user input
        $t->createParam('test1')->setDescription('it is a test')->setInput(true, 'option4', $values)->add();
        $t->showparams();
        $p = $t->evalParam('test1');
        $this->assertEquals('op3', $p->value);
    }

    public function testInputEmpty()
    {
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        CliOne::testUserInput(['']);         // we use this line to simulate the user input
        $t->createParam('test1')
            ->setAllowEmpty()
            ->setDescription('it is a test')
            ->setInput()->add();
        $t->showparams();
        $p = $t->evalParam('test1', true);
        $this->assertEquals('', $p->value);
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        CliOne::testUserInput(['', 'hello']); // we use this line to simulate the user input
        //, the first value is the current value to read
        //, and the next values are the emulated user input
        $t->createParam('test1')
            ->setAllowEmpty(false)
            ->setDescription('it is a test')
            ->setInput()->add();
        $t->showparams();
        $p = $t->evalParam('test1', true);
        $this->assertEquals('hello', $p->value);
    }

    public function testInputOptions()
    {
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        CliOne::testUserInput(['']);         // we use this line to simulate the user input
        $t->createParam('test1')->setDescription('it is a test')
            ->setDefault(['op1', 'op2'])
            ->setInput(true, 'multiple', ['op1', 'op2', 'op3'])->add();
        $t->showparams();
        $p = $t->evalParam('test1', true);
        $this->assertEquals(['op1', 'op2'], $p->value);
        CliOne::testUserInput(['a', 'op3', 'op2', 'op2', '']);   // all, remove3, remove2,add2, end
        $t->getParameter('test1')->setDefault([])
            ->setInput(true, 'multiple2', ['op1' => 'op1', 'op2' => 'op2', 'op3' => 'op3']);
        $p = $t->evalParam('test1', true);
        $this->assertEquals(['op1', 'op2'], $p->value);
        CliOne::testUserInput(['a', 'n', 'x', '']);   // all, remove all, error,end
        $t->getParameter('test1')->setDefault([])
            ->setInput(true, 'multiple3', ['op1' => 'op1', 'op2' => 'op2', 'op3' => 'op3']);
        $p = $t->evalParam('test1', true);
        $this->assertEquals([], $p->value);
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        CliOne::testUserInput(['']);         // we use this line to simulate the user input
        $t->createParam('test1')->setDescription('it is a test')
            ->setDefault(['op1', 'op2'])
            ->setInput(true, 'multiple', ['op1', 'op2', 'op3'])->add();
        $t->showparams();
        $p = $t->evalParam('test1', true);
        $this->assertEquals(['op1', 'op2'], $p->value);
    }

    public function testTemplate()
    {
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        CliOne::testUserInput(['']);         // we use this line to simulate the user input
        $t->createParam('test1')->setDescription('it is a test')
            ->setDefault(['op1', 'op2'])
            ->setPattern('**<c>[{key}]</c>** {value} def:{def} desc{desc}')
            ->setInput(true, 'multiple', ['op1', 'op2', 'op3'])->add();
        $t->showparams();
        $p = $t->evalParam('test1', true);
        $this->assertEquals(['op1', 'op2'], $p->value);
    }

    public function testInputOptionShort()
    {
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        // select "a"ll, de-select 1, end
        CliOne::testUserInput(['', 'o', 'op1']);         // we use this line to simulate the user input
        $t->createParam('test1')->setDescription('it is a test')
            ->setInput(true, 'optionshort', ['op1', 'op2', 'op3'])->add();
        $t->showparams();
        $p = $t->evalParam('test1', true);
        $this->assertEquals('op1', $p->value);
        CliOne::testUserInput(['', 'y']);         // we use this line to simulate the user input
        $t->createParam('test1s')->setDescription('it is a test')
            ->setInput(true, 'optionshort', ['yes', 'no'])->add();
        $t->showparams();
        $p = $t->evalParam('test1s', true);
        $this->assertEquals('yes', $p->value);
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        // select "a"ll, de-select 1, end
        CliOne::testUserInput(['']);         // we use this line to simulate the user input
        $t->createParam('test1')->setDescription('it is a test')
            ->setDefault('op1')
            ->setAllowEmpty(false)
            ->setInput(true, 'optionshort', ['op1', 'op2', 'op3'])->add();
        $t->showparams();
        $p = $t->evalParam('test1', true);
        $this->assertEquals('op1', $p->value);
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        // select "a"ll, de-select 1, end
        CliOne::testUserInput(['']);         // we use this line to simulate the user input
        $t->createParam('test1')->setDescription('it is a test')
            ->setDefault('op1')
            ->setAllowEmpty() // even when it allows empty, it uses the default value.
            ->setInput(true, 'optionshort', ['op1', 'op2', 'op3'])->add();
        $t->showparams();
        $p = $t->evalParam('test1', true);
        $this->assertEquals('op1', $p->value);
    }

    public function testInputOptions2()
    {
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        // select "a"ll, de-select 1, end
        CliOne::testUserInput(['a', 1, '']);         // we use this line to simulate the user input
        $t->createParam('test1')->setDescription('it is a test')
            ->setInput(true, 'multiple', ['op1', 'op2', 'op3'])->add();
        $t->showparams();
        $p = $t->evalParam('test1', true);
        $this->assertEquals(['op2', 'op3'], $p->value);
    }

    public function testInputRage()
    {
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        CliOne::testUserInput([33]);         // we use this line to simulate the user input
        $t->createParam('test1')->setDescription('it is a test')
            ->setInput(true, 'range', [0, 100])->add();
        $t->showparams();
        $p = $t->evalParam('test1');
        $this->assertEquals(33, $p->value);
    }

    public function testInputNumber()
    {
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        CliOne::testUserInput([33]);         // we use this line to simulate the user input
        $t->createParam('test1')->setDescription('it is a test')
            ->setInput(true, 'number')->add();
        $t->showparams();
        $p = $t->evalParam('test1');
        $this->assertEquals(33, $t->getParameter('test1')->value);
        $this->assertEquals(33, $p->value);
    }

    public function testOthers()
    {
        CliOne::testArguments(['program.php']);
        $t = new CliOne();
        $this->assertEquals(true, $t->isCli());
    }
}
