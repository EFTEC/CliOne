<?php /** @noinspection UnknownInspectionInspection */
/** @noinspection ForgottenDebugOutputInspection */
/** @noinspection DuplicatedCode */

namespace eftec\CliOne;

use DateTime;
use Exception;
use JsonException;
use RuntimeException;

/**
 * CliOne - A simple creator of command-line argument program.
 *
 * @package   CliOne
 * @author    Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @copyright Copyright (c) 2022 Jorge Patricio Castro Castillo. Dual Licence: MIT License and Commercial.
 *            Don't delete this comment, its part of the license.
 * @version   1.32.1
 * @link      https://github.com/EFTEC/CliOne
 */
class CliOne
{
    public const VERSION = '1.32.1';
    /**
     * @var bool if debug is true then:<br>
     *           1) every operation will be recorded in $this->debugHistory<br>
     *           2) if you input ??history then, it will show the debug history in PHP format
     *           3) if you input ??clear then, it will clean the debug history
     *           4) if you input ??save then, it saves the history in a file called _save.json
     *           5) if you input ??load then, it loads the history and executes it.
     */
    public bool $debug = false;
    public array $debugHistory = [];
    /** @var array|null this field is called by self::testUserInput() and It's used for debug purpose. */
    public static ?array $fakeReadLine =null;
    public static bool $throwNoInput = false;
    public static array $autocomplete = [];
    /**
     * @var string it is the empty value used for escape, but it is also used to mark values that aren't selected
     *             directly "a" all, "n" nothing, "" enter exit
     */
    public string $emptyValue = '__INPUT_';
    public ?string $origin = null;
    /** @var string $error it stores the latest error */
    public string $error = '';
    /** @var CliOneParam[] */
    public array $parameters = [];
    /**
     * If <b>true</b> (default value), then the values are echo automatically on the screen.<br/>
     * If <b>false</b>, then the values are stored into the memory.<br/>
     * You can access to the memory using getMemory(), setMemory()<br/>
     * @var bool
     * @see CliOne::getMemory
     * @see CliOne::setMemory
     */
    public bool $echo = true;
    /** @var false|resource|null  */
    public $MEMORY;
    public array $menu = [];
    public array $menuServices = [];
    public array $menuEventItem = [];
    public array $menuEventMenu = [];
    protected string $defaultStream = 'stdout';
    protected $colSize = 80;
    protected $rowSize = 25;
    protected array $bread = [];
    /** @var bool if true then mb_string library is loaded, otherwise it is false. it is calculated in the constructor */
    protected bool $multibyte = false;
    protected string $styleStack = 'simple';
    protected string $styleIconStack = 'line';
    /** @var string[] [$alignTitle, $alignContent, $alignContentNumeric] */
    protected array $alignStack = ['middle', 'middle', 'middle'];
    protected array $colorStack = [];
    protected ?string $patternTitleStack = null;
    protected ?string $patternCurrentStack = null ;
    protected ? string $patternSeparatorStack= null;
    protected ? string $patternContentStack = null;
    /** @var array It is an associative array used to replace when the value displayed contains {{namevar}} */
    protected array $variables = [];
    protected array $variablesCallback = [];
    /** @var int used internally for waiting cursor */
    protected int $wait = 0;
    protected int $waitSize = 1;
    /** @var string=['silent','show','throw'][$i] */
    protected string $errorType = 'show';
    /** @var CliOne|null */
    protected static ?CliOne $instance = null;
    protected string $waitPrev = '';
    /** @var string the original script file */
    protected string $phpOriginalFile = '';
    /**
     * the arguments as a couple key/value. If the value is missing, then it is ''
     * @var array
     */
    protected array $argv = [];
    /** @var bool if true then it will not show colors */
    protected bool $noColor = false;
    /** @var bool if true then the console is in old-cmd mode (no colors, no utf-8 characters, etc.) */
    protected bool $noANSI = false;
    public array $colorTags = ['<red>', '</red>', '<yellow>', '</yellow>', '<green>', '</green>',
        '<white>', '</white>', '<blue>', '</blue>', '<black>', '</black>',
        '<cyan>', '</cyan>', '<magenta>', '</magenta>',
        '<bred>', '</bred>', '<byellow>', '</byellow>', '<bgreen>', '</bgreen>',
        '<bwhite>', '</bwhite>', '<bblue>', '</bblue>', '<bblack>', '</bblack>',
        '<bcyan>', '</bcyan>', '<bmagenta>', '</bmagenta>'];
    public array $styleTextTags = ['<italic>', '</italic>', '<bold>', '</bold>', '<dim>', '</dim>',
        '<underline>', '</underline>', '<strikethrough>', '</strikethrough>'];
    public array $columnTags = ['<col0/>', '<col1/>', '<col2/>',
        '<col3/>', '<col4/>', '<col5/>',];
    public array $colorEscape = ["\e[31m", "\e[39m", "\e[33m", "\e[39m", "\e[32m", "\e[39m",
        "\e[37m", "\e[39m", "\e[34m", "\e[39m", "\e[30m", "\e[39m",
        "\e[36m", "\e[39m", "\e[35m", "\e[39m",
        "\e[41m", "\e[49m", "\e[43m", "\e[49m", "\e[42m", "\e[49m",
        "\e[47m", "\e[49m", "\e[44m", "\e[49m", "\e[40m", "\e[49m",
        "\e[46m", "\e[49m", "\e[45m", "\e[49m",];
    /** @var string[] note, it must be 2 digits */
    public array $styleTextEscape = ["\e[03m", "\e[23m", "\e[01m", "\e[22m", "\e[02m", "\e[22m",
        "\e[04m", "\e[24m", "\e[09m", "\e[29m"];
    public array $columnEscape = [];
    protected array $columnEscapeCmd = [];

    /**
     * The constructor. If there is an instance, then it replaces the instance.
     * @param string|null $origin you can specify the origin script file. If you specify the origin script
     *                            then, isCli will only return true if the file is called directly using its file.
     * @param bool        $ignoreCli if true, then it will run no matter if it is running on cli or not.
     */
    public function __construct(?string $origin = null,bool $ignoreCli=false)
    {
        self::$instance = $this;
        $this->origin = $origin;
        if (!$ignoreCli && !$this->isCli()) {
            die("you are not running a CLI: " . $this->error);
        }
        $this->MEMORY = fopen('php://memory', 'rwb');
        $this->readingArgv();
        if (getenv('NO_COLOR')) {
            $this->noColor = true;
        }
        if (!$this->hasColorSupport()) {
            $this->noColor = true;
            $this->noANSI = true;
        } else if (PHP_OS_FAMILY === 'Windows') {
            if ($this->getWindowsVersion() >= 10.1607) {
                // @getenv('PROMPT', true)
                $this->noANSI = false; // its windows but it is a modern version
            } else {
                $this->noANSI = true; // its windows and it is an old version.
            }
        }
        //
        $this->colSize = $this->calculateColSize();
        $this->rowSize = $this->calculateRowSize();
        $t = floor($this->colSize / 6);
        $this->columnEscape = ["\e[000G", "\e[" . sprintf('%03d', $t) . "G", "\e[" . sprintf('%03d', $t * 2) . "G",
            "\e[" . sprintf('%03d', $t * 3) . "G", "\e[" . sprintf('%03d', $t * 4) . "G", "\e[" . sprintf('%03d', $t * 5) . "G"];
        $this->columnEscapeCmd = ['', str_repeat(' ', $t), str_repeat(' ', $t * 2),
            str_repeat(' ', $t * 3), str_repeat(' ', $t * 4), str_repeat(' ', $t * 5)];
        $this->multibyte = function_exists('mb_strlen');
        // it is used by readline
        readline_completion_function(static function($input) {
            // Filter Matches
            $matches = [];
            foreach (CliOne::$autocomplete as $cmd) {
                if (stripos($cmd, $input) === 0) {
                    $matches[] = $cmd;
                }
            }
            return $matches;
        });
    }

    /**
     * It returns the current Windows version as a decimal number.<br>
     * If no version is found then it returns 6.1 (Windows 7).<br>
     * Windows 10 and Windows 11 versions are returned as 10.xxxx instead of 10.0.xxxx
     *
     * @return string
     * @noinspection TypeUnsafeComparisonInspection
     */
    public function getWindowsVersion(): string
    {
        $version = trim(shell_exec('ver') ?? '');
        if (strpos($version, '[') === false) {
            return "6.1"; // no value found, returned: Windows 7
        }
        $parts = explode('[', $version);
        if (strpos($parts[1], ' ') === false) {
            return "6.1"; // no value found, returned: Windows 7
        }
        $part2 = explode(' ', $parts[1]); // Version 10.0.xxxx
        if (strpos($part2[1], '.') === false) {
            return "6.1"; // no value found, returned: Windows 7
        }
        $versions = explode('.', $part2[1] . '.0.0.0.0');
        if ($versions[0] < 10) { // example: Windows 7 6.1.7601
            return $versions[0] . '.' . $versions[1];
        }
        if ($versions[0] > 10) { // future use.
            return $versions[0] . '.' . $versions[1];
        }
        if (($versions[0] == 10) && $versions[1] == 0) {  // Windows 10 and 11 use the version 10.0.xxxx,
            // so we return 10.xxxx, omitting the 0 in between.
            return $versions[0] . '.' . $versions[2];
        }
        return $versions[0] . '.' . $versions[1]; // in the case that it returns 10.xxx, then we returned it.
    }

    /**
     * It gets the current instance of the library.<br/>
     * If the instance does not exist, then it is created
     * @param string|null $origin you can specify the origin script file. If you specify the origin script
     *                            then, isCli will only return true if the file is called directly using its file.
     * @return CliOne
     */
    public static function instance(?string $origin = null): CliOne
    {
        if (self::$instance === null) {
            self::$instance = new CliOne($origin);
        }
        return self::$instance;
    }

    /**
     * Returns true if there is an instance of CliOne.
     * @return bool
     */
    public static function hasInstance(): bool
    {
        return self::$instance !== null;
    }

    /**
     * It returns true if CliOne has a menu defined. It will return false if there is no menu or there is no instance.
     * @return bool
     */
    public static function hasMenu(): bool
    {
        if (self::hasInstance()) {
            return count(self::instance()->menu) > 0;
        }
        // no instance, no menu
        return false;
    }

    /**
     * It clears a menu and the services associated.
     * @param string|null $idMenu If null, then it clears all menus
     * @return CliOne
     */
    public function clearMenu(?string $idMenu = null): CliOne
    {
        if ($idMenu === null) {
            $this->menu = [];
            $this->menuServices = [];
        } else {
            unset($this->menu[$idMenu], $this->menuServices[$idMenu]);
        }
        return $this;
    }

    /**
     * It adds a new menu that could be called by evalMenu()<br/>
     * <b>Example:</b><br/>
     * ```php
     * //"fnheader" call to $this->menuHeader(CliOne $cli);
     * $this->addMenu('idmenu','fnheader',null,'What do you want to do?','option3');
     * // you can use a callable argument, the first argument is of type CliOne.
     * $this->addMenu('idmenu',function($cli) { echo "header";},function($cli) { echo "footer;"});
     * ```
     * @param string               $idMenu         The unique name of the menu
     * @param string|null|callable $headerFunction Optional, the name of the method called every time the menu is
     *                                             displayed<br/>
     *                                             The method called must have a prefix menu.Ex:"opt1",method:menuopt1"
     *                                             If $headerFunction is callable, then it calls the function.
     * @param string|null|callable $footerFunction Optional, the name of the method called every time the menu end its
     *                                             display. The method called must have a prefix "menu".<br/>
     *                                             If $footerFunction is callable, then it calls the function
     * @param string               $question       The input question.
     * @param string               $size           =['option','option2','option3','option4','wide-option','wide-option2'][$i]
     *                                             The size of the option menu.
     * @return CliOne
     */
    public function addMenu(string $idMenu,
                                   $headerFunction = null,
                                   $footerFunction = null,
                            string $question = 'Select an option (empty to exit)',
                            string $size = 'wide-option'): CliOne
    {
        $this->menu[$idMenu] = [];
        $this->menuEventItem[$idMenu] = [];
        $this->menuEventMenu[$idMenu] = ['header' => $headerFunction, 'footer' => $footerFunction,
            'question' => $question, 'size' => $size];
        return $this;
    }

    /**
     * It adds a menu item.<br/>
     * <b>Example:</b><br/>
     * ```php
     * $this->addMenu('menu1');
     * // if op1 is selected then it calls method menufnop1(), the prefix is for protection.
     * $this->addMenuItem('menu1','op1','option #1','fnop1');
     * // if op1 is selected then it calls method menuop2()
     * $this->addMenuItem('menu1','op2','option #2');
     * $this->addMenuItem('menu1','op3','go to menu2','navigate:menu2');
     * $this->addMenuItem('menu1','op4','call function',function(CliOne $cli) {  });
     * $this->evalMenu('menu1',$obj);
     * // the method inside $obj
     * public function menufnop1($caller):void {
     * }
     * public function menuop2($caller):string {
     *      return 'EXIT'; // if any function returns EXIT (uppercase), then the menu ends (simmilar to "empty to
     *      exit")
     * }
     * ```
     *
     * @param string               $idMenu        The unique name of the menu
     * @param string               $indexMenuItem The unique index of the menu. It is used for selection and action
     *                                            (if no action is supplied).
     * @param string               $description   The description of the menu
     * @param string|null|callable $action        The action is the method called (the method must have a prefix
     *                                            "menu").<br/> If action starts with <b>"navigate:"</b> then it opens
     *                                            the menu indicated.<br/> If action is "exit:" then exit of the
     *                                            menu.<br/> If action is callable, then it calls the function
     *
     * @return CliOne
     */
    public function addMenuItem(string $idMenu, string $indexMenuItem, string $description, $action = null): CliOne
    {
        $this->menu[$idMenu][$indexMenuItem] = $description;
        $this->menuEventItem[$idMenu][$indexMenuItem] = $action ?? $indexMenuItem;
        return $this;
    }

    /**
     * It adds multiples items to a menu<br/>
     * <b>Example:</b><br/>
     * ```php
     * $this->addMenu('menu1');
     * $this->addMenuItems('menu1',[
     *                'op1'=>['operation #1','action1'], // with description & action
     *                'op2'=>'operation #2']); // the action is "op2"
     * ```
     * @param string     $idMenu The unique name of the menu
     * @param array|null $items  An associative array with the items to add. Examples:<br/>
     *                           [index=>[description,action]]<br/>
     *                           [index=>description]<br/>
     * @return $this
     * @see CliOne::addMenuItem
     */
    public function addMenuItems(string $idMenu, ?array $items): CliOne
    {
        foreach ($items as $indexMenuItem => $v) {
            if (is_array($v)) {
                $this->menu[$idMenu][$indexMenuItem] = $v[0];
                $this->menuEventItem[$idMenu][$indexMenuItem] = $v[1] ?? $indexMenuItem;
            } else {
                $this->menu[$idMenu][$indexMenuItem] = $v;
                $this->menuEventItem[$idMenu][$indexMenuItem] = $indexMenuItem;
            }
        }
        return $this;
    }

    /**
     * It adds a service object to be evaluated when we run evalMenu()<br/>
     * You can add menu services. Every service is evaluated in order, so if both service objects has the same method,
     * then it is only called by the first object.<br/>
     * If evalMenu() uses a service then, the services defined here are ignored.<br/>
     * <b>Example:</b><br/>
     * ```php
     * $objService=new Class1();
     * $this->addMenuService('menu1',$objService);
     * // or:
     * $this->addMenuService('menu1',Class1:class);
     * ```
     * @param string        $idMenu  The unique name of the menu
     * @param object|string $service The service object or the name of the class.<br/>
     *                               If it is a name of a class, then it creates an instance of it.
     * @return CliOne
     */
    public function addMenuService(string $idMenu, $service): CliOne
    {
        if (!is_object($service)) {
            $service = new $service();
        }
        $this->menuServices[$idMenu][] = $service;
        return $this;
    }

    /**
     * Eval (executes) a menu previously defined.<br/>
     * <b>Example:</b><br/>
     * ```php
     * $this->addMenu('menu1');
     * // pending: add items to the menu
     * $this->evalMenu('menu1',$myService);
     * // or also
     * $this->>addMenu('menu1')->addMenuService('menu1',$myService)->evalMenu('menu1');
     * ```
     * @param string            $idMenu The unique name of the menu
     * @param object|null|array $caller The caller object(s). It is used to the events and actions.<br/>
     *                                  If null, then it use the services defined by addMenuService();<br/>
     *                                  If it is an array, then it calls the first object that has the method<br/>
     *                                  If this argument is used then addMenuService() is ignored
     *
     * @return CliOne
     * @throws JsonException
     */
    public function evalMenu(string $idMenu, $caller = null): CliOne
    {
        if (!isset($this->menuEventMenu[$idMenu])) {
            throw new RuntimeException("CliOne: Menu [$idMenu] does not exist]");
        }
        $callfooterExit = false;
        if ($caller === null) {
            $caller = $this->menuServices[$idMenu];
        }
        if (!is_array($caller)) {
            $caller = [$caller];
        }
        while (true) {
            $menuHeader = $this->menuEventMenu[$idMenu]['header'];
            if ($menuHeader !== null) {
                if (is_string($menuHeader)) {
                    $method = 'menu' . $menuHeader;
                    $called = false;
                    foreach ($caller as $call) {
                        if (method_exists($call, $method)) {
                            $call->$method($this);
                            $called = true;
                            break;
                        }
                    }
                    if (!$called) {
                        throw new RuntimeException("CliOne: method [$method] does not exist");
                    }
                } else {
                    $menuHeader($this);
                }
            }
            $value = $this->createParam('_menu', [], 'none')
                ->setDescription('Select an option'
                    , $this->menuEventMenu[$idMenu]['question']
                    , [], '_menu')
                ->setAllowEmpty()
                ->setInput(true, $this->menuEventMenu[$idMenu]['size'], $this->menu[$idMenu])
                ->evalParam(true);
            if ($value->valueKey === $this->emptyValue) {
                $callfooterExit = true;
                break;
            }
            $nameAction = $this->menuEventItem[$idMenu][$value->valueKey];
            if (is_string($nameAction)) {
                if (strpos($nameAction, 'navigate:') === 0) {
                    $menu = substr($nameAction, strlen('navigate:'));
                    $this->evalMenu($menu, $caller);
                } else {
                    if ($nameAction === 'exit:') {
                        $callfooterExit = true;
                        break;
                    }
                    $method = 'menu' . $this->menuEventItem[$idMenu][$value->valueKey];
                    $called = false;
                    $result = null;
                    foreach ($caller as $call) {
                        if (method_exists($call, $method)) {
                            $result = $call->$method($this, $value);
                            $called = true;
                            break;
                        }
                    }
                    if (!$called) {
                        throw new RuntimeException("CliOne: method [$method] does not exist");
                    }
                    if ($result === 'EXIT') {
                        $callfooterExit = true;
                        break;
                    }
                }
            } else {
                $result = $nameAction($this, $value);
                if ($result === 'EXIT') {
                    $callfooterExit = true;
                    break;
                }
            }
            $menuFooter = $this->menuEventMenu[$idMenu]['footer'];
            $result = null;
            if ($menuFooter !== null) {
                // this must be called before any break;
                if (is_string($menuFooter)) {
                    $method = 'menu' . $menuFooter;
                    //$called=false;
                    foreach ($caller as $call) {
                        if (method_exists($call, $method)) {
                            $result = $call->$method($this);
                            //$called=true;
                            break;
                        }
                    }
                } else {
                    $result = $menuFooter($this);
                }
                if ($result === 'EXIT') {
                    break;
                }
            }
        }
        $menuFooter = $this->menuEventMenu[$idMenu]['footer'];
        if ($callfooterExit && $menuFooter !== null) {
            // this must be called before any break;
            if (is_string($menuFooter)) {
                $method = 'menu' . $menuFooter;
                foreach ($caller as $call) {
                    if (method_exists($call, $method)) {
                        $call->$method($this);
                        //$called=true;
                        break;
                    }
                }
            } else {
                $menuFooter($this);
            }
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getErrorType(): string
    {
        return $this->errorType;
    }

    public function getMemory($autoflush = false): string
    {
        fseek($this->MEMORY, 0);
        $r = stream_get_contents($this->MEMORY);
        if ($autoflush) {
            @ftruncate($this->MEMORY, 0);
        }
        return $r;
    }

    /**
     * It sets a value into an array.<br/>
     * @param string $variableName the name of the variable. If the variable exists, then it is replaced.
     * @param mixed  $value        the value to assign.
     * @param bool   $callBack     if the value is true (default), then every modification (if the value is changed)
     *                             will call the functions defined in addVariableCallBack()<br/> if fallse, then it does
     *                             not call the callback functions.
     * @return void
     */
    public function setVariable(string $variableName, $value, bool $callBack = true): void
    {
        if (isset($this->variables[$variableName]) && $this->variables[$variableName] === $value) {
            // value not changed
            return;
        }
        $this->variables[$variableName] = $value;
        if ($callBack) {
            $this->callVariablesCallBack();
        }
    }

    /**
     * It gets the value of a variable
     * @param string     $variableName    The name of the variable
     * @param mixed|null $valueIfNotFound If not found then it returns this value
     * @return mixed|null
     */
    public function getVariable(string $variableName, $valueIfNotFound = null)
    {
        return $this->variables[$variableName] ?? $valueIfNotFound;
    }

    /**
     * It adds a callback function.<br/>
     * <b>Example:</b><br/>
     * ```php
     * $t->addVariableCallBack('call1', function(CliOne $cli) {
     *          $cli->setVariable('v2', 'world',false); // the false is important if you don't want recursivity
     * });
     * ```
     * This function is called every setVariable() if the value is different as the defined.
     * @param string        $callbackName the name of the function. If the function exists, then it is replaced.
     * @param callable|null $function     If the function is null, then it deleted the function assigned.<br/>
     *                                    The function could be defined using an argument of the type CliOne.
     * @return void
     */
    public function addVariableCallBack(string $callbackName, ?callable $function): void
    {
        if ($function !== null) {
            $this->variablesCallback[$callbackName] = $function;
        } else {
            unset($this->variablesCallback[$callbackName]);
        }
    }

    /**
     * It calls the callback functions. Usually they are called every time we setVariable() (and the value is changed).
     * @return void
     */
    public function callVariablesCallBack(): void
    {
        foreach ($this->variablesCallback as $v) {
            $v($this);
        }
    }

    /**
     * This function is based in Symfony
     * @return bool
     */
    public function hasColorSupport(): bool
    {
        if ('Hyper' === getenv('TERM_PROGRAM')) {
            return true;
        }
        if (PHP_OS_FAMILY === 'Windows') {
            return (function_exists('sapi_windows_vt100_support')
                    && @sapi_windows_vt100_support(STDOUT))
                || false !== getenv('ANSICON')
                || 'ON' === getenv('ConEmuANSI')
                || 'xterm' === getenv('TERM');
        }
        if (function_exists('stream_isatty')) {
            return @stream_isatty(STDOUT);
        }
        if (function_exists('posix_isatty')) {
            return @posix_isatty(STDOUT);
        }
        $stat = @fstat(STDOUT);
        return $stat && 0020000 === ($stat['mode'] & 0170000);
    }

    /**
     * It is used for testing. You can simulate arguments using this function<br/>
     * This function must be called before the creation of the instance
     * @param array $arguments
     * @return void
     */
    public static function testArguments(array $arguments): void
    {
        global $argv;
        $argv = $arguments;
    }

    /**
     * It is used for testing. You can simulate user-input using this function<br/>
     * This function must be called before every interactivity<br/>
     * This function is not resetted automatically, to reset it, set $userInput=null<br/>
     * @param ?array $userInput
     * @param bool   $throwNoInput (def:true) if true then it throws an exception if not input<br>
     *                             if false, then if no more input then it cleans the userinput
     * @return void
     */
    public static function testUserInput(?array $userInput, bool $throwNoInput = true): void
    {
        if ($userInput === null) {
            self::$fakeReadLine = null;
            //unset(self::$fakeReadLine);
        } else {
            array_unshift($userInput, 0);
            self::$fakeReadLine = $userInput;
            //self::$fakeReadLine = $userInput;
        }
        self::$throwNoInput = $throwNoInput;
    }

    /**
     * It sets the value stored into the memory stream<br/>
     * If the memory stream has values, then they are deleted and replaced.
     * @param string $memory The value to store
     * @return $this
     */
    public function setMemory(string $memory): CliOne
    {
        ftruncate($this->MEMORY, 0);
        fwrite($this->MEMORY, $memory);
        return $this;
    }

    /**
     * It sets if you want to display errors or not. This flag is reseted every time it is used.
     * @param string $errorType =['silent','show','throw'][$i] (default is show)
     * @return CliOne
     */
    public function setErrorType(string $errorType = 'throw'): CliOne
    {
        $this->errorType = $errorType;
        return $this;
    }

    /**
     * This function is used internally to reading the arguments and storing into the field $this->argv
     * @return void
     */
    protected function readingArgv(): void
    {
        global $argv;
        $this->argv = [];
        $c = $argv === null ? 0 : count($argv);
        if ($c > 0) {
            $this->phpOriginalFile = $argv[0];
        }
        // the first argument is the name of the program, i.e ./program.php, so it is excluded.
        for ($i = 1; $i < $c; $i++) {
            $x = explode('=', $argv[$i], 2);
            if (count($x) === 2) {
                // the argument is merged with a symbol "="
                // program.php arg=value
                // program.php -arg=value
                // program.php --arg=value
                $this->argv[$x[0]] = trim($x[1], " \t\n\r\0\x0B\"'");
            } else {
                $x2 = $argv[$i + 1] ?? null;
                if ($x2 !== null && strpos($x2, '-') !== 0) {
                    // it is not the last argument and the next argument is not a flag.
                    // program.php -arg value
                    // program.php --arg value
                    /** @noinspection NestedPositiveIfStatementsInspection */
                    if ($argv[$i][0] === '-') {
                        $this->argv[$argv[$i]] = trim($x2, " \t\n\r\0\x0B\"'");
                        $i++;
                    } else {
                        // program.php subcommand (it is a positional argument, and it doesn't have value).
                        $this->argv[$argv[$i]] = '';
                    }
                } else {
                    // it is the last argument or the next argument is a flag.
                    // program.php arg -arg2
                    // program.php -arg -arg2
                    // program.php --arg -arg2
                    // program.php subcommand1 -bbb dddd subcommand2 (the last argument could be positional)
                    $this->argv[$argv[$i]] = '';
                }
            }
        }
    }

    /**
     * It finds the vendor path starting from a route. The route must be inside the application path.
     * @param ?string $initPath     the initial path, example __DIR__, getcwd(), 'folder1/folder2'. If null, then
     *                              __DIR__
     * @return string It returns the relative path to where is the vendor path. If not found then it returns the
     *                              initial path
     */
    public static function findVendorPath(?string $initPath = null): string
    {
        $initPath = $initPath ?: __DIR__;
        $prefix = '';
        $defaultvendor = $initPath;
        // finding vendor
        for ($i = 0; $i < 6; $i++) {
            if (@file_exists("$initPath/{$prefix}vendor/autoload.php")) {
                $defaultvendor = "{$prefix}vendor";
                break;
            }
            $prefix .= '../';
        }
        return $defaultvendor;
    }

    /**
     * It removes trail slashes.
     * @param string $txt
     * @return string
     * @noinspection PhpUnused
     */
    protected static function removeTrailSlash(string $txt): string
    {
        return rtrim($txt, '/\\');
    }

    /**
     * It creates a new parameter to be read from the command line and/or to be input manually by the user<br/>
     * <b>Example:</b><br/>
     * ```php
     * $this->createParam('k1','first'); // php program.php thissubcommand
     * $this->createParam('k1','flag',['flag2','flag3']); // php program.php -k1 <val> or --flag2 <val> or --flag3
     * <val>
     * ```
     * @param string       $key                The key or the parameter. It must be unique.
     * @param array|string $alias              A simple array with the name of the arguments to read (without - or
     *                                         <b>flag</b>: (default) it reads a flag "php program.php -thisflag
     *                                         value"<br/>
     *                                         <b>first</b>: it reads the first argument "php program.php thisarg"
     *                                         (without value)<br/>
     *                                         <b>second</b>: it reads the second argument "php program.php sc1
     *                                         thisarg" (without value)<br/>
     *                                         <b>last</b>: it reads the second argument "php program.php ... thisarg"
     *                                         (without value)<br/>
     *                                         <b>longflag</b>: it reads a longflag "php program --thislongflag
     *                                         value<br/>
     *                                         <b>last</b>: it reads the second argument "php program.php ...
     *                                         thisvalue" (without value)<br/>
     *                                         <b>onlyinput</b>: the value means to be user-input, and it is
     *                                         stored<br/>
     *                                         <b>none</b>: the value it is not captured via argument, so it could be
     *                                         user-input, but it is not stored<br/> none parameters could always be
     *                                         overridden, and they are used to "temporary" input such as validations
     *                                         (y/n).
     * @param string       $type               =['command','first','last','second','flag','longflag','onlyinput','none'][$i]<br/>
     *                                         "-"<br/> if the type is a flag, then the alias is a double flag
     *                                         "--".<br/> if the type is a double flag, then the alias is a flag.
     * @param bool         $argumentIsValueKey <b>true</b> the argument is value-key<br/>
     *                                         <b>false</b> (default) the argument is a value
     * @return CliOneParam
     */
    public function createParam(string $key,
                                       $alias = [],
                                string $type = 'flag',
                                bool   $argumentIsValueKey = false): CliOneParam
    {
        return new CliOneParam($key, $type, $alias, null, null, $argumentIsValueKey);
    }

    public function createOrReplaceParam(string $key,
                                                $alias = [],
                                         string $type = 'flag',
                                         bool   $argumentIsValueKey = false): CliOneParam
    {
        $p = $this->getParameter($key);
        if ($p->key !== null) {
            return $p;
        }
        return new CliOneParam($key, $type, $alias, null, null, $argumentIsValueKey);
    }

    /**
     * Down a level in the breadcrub.<br/>
     * If down more than the number of levels available, then it clears the stack.
     * @param int $number number of levels to down.
     * @return CliOne
     */
    public function downLevel(int $number = 1): CliOne
    {
        for ($i = 0; $i < $number; $i++) {
            array_pop($this->bread);
        }
        return $this;
    }

    /**
     * It evaluates the parameters obtained from the syntax of the command.<br/>
     * The parameters must be defined before call this method<br/>
     * <b>Example:</b><br/>
     * ```php
     * // shell:
     * php mycode.php -argument1 hello -argument2 world
     *
     * // php code:
     * $t=new CliOne('mycode.php');
     * $t->createParam('argument1')->add();
     * $result=$t->evalParam('argument1'); // an object ClieOneParam where value is "hello"
     * ```
     * @param string $key         the key to read.<br/>
     *                            If $key='*' then it reads the first flag and returns its value (if any).
     * @param bool   $forceInput  it forces input no matter if the value is already inserted.
     * @param bool   $returnValue If true, then it returns the value obtained.<br/>
     *                            If false (default value), it returns an instance of CliOneParam.
     * @return mixed Returns false if not value is found.
     * @throws JsonException
     */
    public function evalParam(string $key = '*', bool $forceInput = false, bool $returnValue = false)
    {
        $valueK = null;
        $notfound = true;
        foreach ($this->parameters as $k => $parameter) {
            if ($parameter->key === $key || ($key === '*' && $parameter->type === 'flag')) {
                $currentValue = $parameter->value;
                $notfound = false;
                //if ($parameter->missing === false && !$forceInput) {
                // the parameter is already read, skipping.
                //    return $returnValue === true ? $parameter->value : $parameter;
                //}
                if ($parameter->value !== null && $parameter->missing === true) {
                    $parameter->missing = false;
                }
                if ($parameter->currentAsDefault && $parameter->value !== null && !$forceInput) {
                    //$parameter->value = $parameter->currentAsDefault; the value hasn't changed
                    $this->refreshParamValueKey($parameter);
                    if ($parameter->isAddHistory()) {
                        $this->addHistory($parameter->value);
                    }
                    $this->errorType = 'show';
                    return $returnValue === true ? $parameter->value : $parameter;
                }
                if (!$parameter->argumentIsValueKey) {
                    [$def, $parameter->value] = $this->readArgument($parameter);
                } else {
                    [$def, $parameter->valueKey] = $this->readArgument($parameter);
                    if ($def && isset($parameter->inputValue[$parameter->valueKey])) {
                        $parameter->value = $parameter->inputValue[$parameter->valueKey];
                    } else {
                        $parameter->value = null;
                    }
                }
                if ($key === '*' && $def === false) {
                    // value not found, not asking for input.
                    continue;
                }
                if ($parameter->currentAsDefault && $currentValue !== null) {
                    // if the value is not empty, and we set the current value as default, then we set it.
                    if ($parameter->inputType !== 'optionshort' && strpos($parameter->inputType, 'option') === 0) {
                        $parameter->default = $parameter->valueKey;
                    } else {
                        $parameter->default = $currentValue;
                    }
                }
                if ($def === false && $currentValue !== null && $forceInput === false) {
                    $def = true;
                    $parameter->value = $currentValue;
                    $this->refreshParamValueKey($parameter);
                }
                if ($def === false || $forceInput) {
                    // the value is not defined as an argument
                    if ($parameter->input === true) {
                        $def = true;
                        $parameter->value = $this->readParameterInput($parameter);
                    }
                    if ($def === false || $parameter->value === false) {
                        $parameter->value = $parameter->default;
                        if ($parameter->required && $parameter->value === false) {
                            $this->throwError("Field $parameter->key is missing");
                            $parameter->value = false;
                        }
                    }
                } else {
                    // the value is defined as an argument.
                    $ok = $this->validate($parameter, false);
                    if (!$ok) {
                        $parameter->value = false;
                    }
                }
                $valueK = $k;
            }
            if ($key === '*' && $parameter->value !== false) {
                // value found, exiting.
                break;
            }
        }
        if ($notfound) {
            $this->throwError("parameter [$key] is not defined");
        }
        if ($valueK === false || $valueK === null) {
            $this->errorType = 'show';
            return false;
        }
        if ($this->parameters[$valueK]->isAddHistory()) {
            $this->addHistory($this->parameters[$valueK]->value);
        }
        $this->errorType = 'show';
        return $returnValue === true ? $this->parameters[$valueK]->value : $this->parameters[$valueK];
    }

    public function throwError($msg): void
    {
        switch ($this->errorType) {
            case 'show':
                $this->showCheck('ERROR', 'red', $msg, 'stderr');
                break;
            case 'throw':
                throw new RuntimeException($msg);
            default:
                // silent
                break;
        }
        $this->errorType = 'show';
    }

    public function showWarning($msg): void
    {
        if ($this->errorType === 'show' || $this->errorType === 'throw') {
            $this->showCheck('WARNING', 'yellow', $msg);
        }
    }

    /**
     * Add a value to the history
     * @param string|array $prompt the value(s) of the history to add
     * @return $this
     */
    public function addHistory($prompt): self
    {
        if (function_exists('readline_add_history')) {
            $prompt = is_array($prompt) ? $prompt : [$prompt];
            foreach ($prompt as $v) {
                readline_add_history($v);
            }
        }
        return $this;
    }

    /**
     * It sets the history (deleting the old history) with the new values
     * @param string|array $prompt
     * @return $this
     */
    public function setHistory($prompt): self
    {
        $this->clearHistory();
        $this->addHistory($prompt);
        return $this;
    }

    /**
     * It clears the global history (if any).
     * @return $this
     */
    public function clearHistory(): CliOne
    {
        if (function_exists('readline_clear_history')) {
            readline_clear_history();
        }
        return $this;
    }

    /**
     * It retrieves the global history (if any)
     * @return array
     */
    public function listHistory(): array
    {
        if (function_exists('readline_list_history')) {
            return readline_list_history();
        }
        return [];
    }

    /**
     * It returns an associative array with all the parameters of the form [key=>value]<br/>
     * Parameters of the type "none" are ignored<br/>
     * @param array $excludeKeys you can add a key that you want to exclude.
     * @return array
     */
    public function getArrayParams(array $excludeKeys = []): array
    {
        $array = [];
        foreach ($this->parameters as $parameter) {
            if ($parameter->type !== 'none' && !$this->in_array_i($parameter->key, $excludeKeys, true)) {
                $array[$parameter->key] = $parameter->value;
            }
        }
        return $array;
    }

    /**
     * It returns the number of columns present on the screen. The columns are calculated in the constructor.
     * @return int
     */
    public function getColSize(): int
    {
        return $this->colSize;
    }

    /**
     * It gets the parameter by the key or an empty parameter with a null key if null.
     *
     * @param string $key the key of the parameter
     * @return CliOneParam
     */
    public function getParameter(string $key): CliOneParam
    {
        foreach ($this->parameters as $v) {
            if ($v->key === $key) {
                return $v;
            }
        }
        return new CliOneParam(null);
    }

    /**
     * It reads a value of a parameter.
     * <b>Example:</b><bt>
     * ```php
     * // [1] option1
     * // [2] option2
     * // select a value [] 2
     * $v=$this->getValueKey('idparam'); // it will return "option2".
     * ```
     * @param string $key the key of the parameter to read the value
     * @return mixed|null It returns the value of the parameter or null if not found.
     */
    public function getValue(string $key)
    {
        $parameter = $this->getParameter($key);
        if (!$parameter->isValid()) {
            return null;
        }
        return $parameter->value;
    }

    /**
     * It returns an array [$prefix,$prefixAlias,$position]<br/>
     * <b>$prefix</b> the prefix of the type of data, example "-", "--" or ""
     * <b>$prefixAlias</b> the prefix of the alias of the type of data, example "-", "--" or ""
     * <b>$position</b> return true if it is a positional argument.
     * @param string $type
     * @return array
     */
    protected function prefixByType(string $type): array
    {
        $position = false;
        switch ($type) {
            case 'last':
            case 'second':
            case 'command':
            case 'first':
                $position = true;
                $prefix = '';
                $prefixAlias = '-';
                break;
            case 'flag':
                $prefix = '-';
                $prefixAlias = '--';
                break;
            case 'longflag':
                $prefix = '--';
                $prefixAlias = '-';
                break;
            default:
                $prefix = '';
                $prefixAlias = '';
        }
        return [$prefix, $prefixAlias, $position];
    }

    /**
     * It reads a parameter as an argument or flag.
     * @param CliOneParam $parameter
     * @return array
     */
    public function readArgument(CliOneParam $parameter): array
    {
        /** @noinspection DuplicatedCode */
        [$prefix, $prefixAlias, $position] = $this->prefixByType($parameter->type);
        $trueName = $prefix . $parameter->key;
        if ($prefix === '' && $prefixAlias === '') {
            // this type of parameter is not readable.
            return [false, null];
        }
        /** @noinspection DuplicatedCode */
        if ($position === false) {
            $value = $this->argv[$trueName] ?? null;
        } else {
            $keys = array_keys($this->argv);
            //$value=null;
            /** @noinspection DuplicatedCode */
            switch ($parameter->type) {
                case 'first':
                case 'command':
                    if (count($this->argv) >= 1) {
                        $keyP = $keys[0];
                        $value = $keyP;
                    } else {
                        $value = null;
                    }
                    break;
                case 'second':
                    if (count($this->argv) > 1) {
                        $keyP = $keys[1];
                        $value = $keyP;
                    } else {
                        $value = null;
                    }
                    break;
                case 'last':
                    if (count($this->argv) > 1) {
                        $keyP = end($keys);
                        $value = $keyP;
                    } else {
                        $value = null;
                    }
                    break;
            }
            /** @noinspection PhpUndefinedVariableInspection */
            if (($value !== null && $keyP[0] === '-') || (!isset($this->argv[$trueName]) && $parameter->type !== 'command')) {
                // positional argument exists however it is a flag or the argument does not exist
                $value = null;
            }
        }
        if ($value === null) {
            // if the value is not found.
            $parameter->missing = true;
            // we try find in the alias (if any).
            foreach ($parameter->alias as $ali) {
                $value = $this->argv[$prefixAlias . $ali] ?? null;
                if ($value !== null) {
                    $parameter->missing = false;
                    $parameter->origin = 'argument';
                    return [true, $value];
                }
            }
            return [false, false];
        }
        // the value is found and we return the value.
        $parameter->missing = false;
        $parameter->origin = 'argument';
        return [true, $value];
    }

    /**
     * It shows the help
     * @param CliOneParam $parameter
     * @param bool        $verbose
     * @return void
     * @throws JsonException
     */
    public function showHelp(CliOneParam $parameter, bool $verbose): void
    {
        $this->showLine("<yellow>Help</yellow> [$parameter->key]");
        if ($verbose) {
            if (count($parameter->alias) > 0) {
                $this->showLine("  <yellow>Aliases: </yellow> " . json_encode($parameter->alias, JSON_THROW_ON_ERROR));
            }
            $this->showLine("  <yellow>Can be called as argument?: </yellow>" .
                (($parameter->type !== 'onlyinput' && $parameter->type !== 'none') ? 'yes' : 'no'));
            $this->showLine("  <yellow>Input Type: </yellow> " . $parameter->inputType);
            $inputVal = $parameter->inputValue;
            if ($inputVal !== null) {
                if (!is_array($inputVal)) {
                    $inputVal = [$inputVal];
                }
                if (count($inputVal) > 0) {
                    $this->showLine("  Values allowed:");
                    foreach ($inputVal as $k => $v) {
                        $this->showLine("  <yellow>$k:</yellow> $v");
                    }
                }
            } else {
                $this->showLine("  No help available.");
            }
        }
        if ($parameter->description) {
            $this->showLine("  <yellow>Description: </yellow>" . $parameter->description);
        }
        foreach ($parameter->getHelpSyntax() as $help) {
            $help = $this->colorText($help);
            if (trim($help) !== '') {
                $this->showLine("  " . $help);
            }
        }
    }

    /**
     * @param string  $word the words to display.
     * @param string  $font =['atr','znaki'][$i]
     * @param bool    $trim if true then, if the first line and/or the last line is empty, then it is removed.
     * @param ?string $bit1 the visible character, if null then it will use a block code
     * @param string  $bit0 the invisible character
     * @return array
     * @noinspection CallableParameterUseCaseInTypeContextInspection
     */
    public function makeBigWords(string $word, string $font = 'atr', bool $trim = false, ?string $bit1 = null, string $bit0 = ' '): array
    {
        $bf = $this->shadow('simple', 'full');
        $bit1 = $bit1 ?? $bf;
        $result = [];
        $words = str_split($word);
        foreach ($words as $k => $letter) {
            switch ($font) {
                case 'znaki':
                    $result[$k] = $this->fontZnaki($letter, $bit1, $bit0);
                    break;
                default:
                case 'atr':
                    $result[$k] = $this->fontAtr($letter, $bit1, $bit0);
                    break;
            }
        }
        $final = [];
        for ($i = 0; $i < 8; $i++) {
            $m = '';
            foreach ($result as $rows) {
                $m .= $rows[$i];
            }
            $final[] = $m;
        }
        if ($trim) {
            $to = count($final) - 1;
            if (trim($final[$to], $bit0) === '') {
                array_splice($final, $to, 1);
            }
            if (trim($final[0], $bit0) === '') {
                array_splice($final, 0, 1);
            }
        }
        return $final;
    }

    /**
     * Font Znaki
     * @param string  $letter the letter to generate
     * @param ?string $bit1   the character to show then the bit is "on"
     * @param string  $bit2   the character to show then the bit is "off"
     * @return array return an array of 8 lines and each line has 8 characters to show the letter.
     */
    protected function fontZnaki(string $letter, ?string $bit1 = null, string $bit2 = ' '): array
    {
        switch ($letter) {
            case ' ':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                ];
                break;
            case '!':
                $r = [
                    0b00000000,
                    0b00111100,
                    0b00111100,
                    0b00111100,
                    0b00011000,
                    0b00000000,
                    0b00011000,
                    0b00000000,
                ];
                break;
            case '"':
                $r = [
                    0b00000000,
                    0b00110011,
                    0b00110011,
                    0b01100110,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                ];
                break;
            case '#':
                $r = [
                    0b00000000,
                    0b01100110,
                    0b11111111,
                    0b01100110,
                    0b01100110,
                    0b11111111,
                    0b01100110,
                    0b00000000,
                ];
                break;
            case '$':
                $r = [
                    0b01111110,
                    0b11000011,
                    0b10011101,
                    0b10110001,
                    0b10110001,
                    0b10011101,
                    0b11000011,
                    0b01111110,
                ];
                break;
            case '%':
                $r = [
                    0b00000000,
                    0b00000011,
                    0b00111110,
                    0b01100000,
                    0b00111110,
                    0b00000011,
                    0b01111110,
                    0b00000000,
                ];
                break;
            case '&':
                $r = [
                    0b00000011,
                    0b00111110,
                    0b01100000,
                    0b00111110,
                    0b00000011,
                    0b00000011,
                    0b01111110,
                    0b00000000,
                ];
                break;
            case '\'':
                $r = [
                    0b00000000,
                    0b00011100,
                    0b00011100,
                    0b00111000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                ];
                break;
            case '(':
                $r = [
                    0b00000000,
                    0b00011110,
                    0b00111100,
                    0b00111000,
                    0b00111000,
                    0b00111100,
                    0b00011110,
                    0b00000000,
                ];
                break;
            case ')':
                $r = [
                    0b00000000,
                    0b00111100,
                    0b00011110,
                    0b00001110,
                    0b00001110,
                    0b00011110,
                    0b00111100,
                    0b00000000,
                ];
                break;
            case '+':
                $r = [
                    0b00000000,
                    0b00011000,
                    0b00011000,
                    0b01111110,
                    0b01111110,
                    0b00011000,
                    0b00011000,
                    0b00000000,
                ];
                break;
            case ',':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00011100,
                    0b00011100,
                    0b00111000,
                ];
                break;
            case '-':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b01111110,
                    0b01111110,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                ];
                break;
            case '.':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00011100,
                    0b00011100,
                    0b00011100,
                    0b00000000,
                ];
                break;
            case '/':
                $r = [
                    0b00000000,
                    0b00000111,
                    0b00001110,
                    0b00011100,
                    0b00111000,
                    0b01110000,
                    0b01100000,
                    0b00000000,
                ];
                break;
            case '0':
                $r = [
                    0b00000000,
                    0b00111110,
                    0b01100011,
                    0b01101111,
                    0b01111011,
                    0b01100011,
                    0b00111110,
                    0b00000000,
                ];
                break;
            case '1':
                $r = [
                    0b00000000,
                    0b00001100,
                    0b00011100,
                    0b00111100,
                    0b00011100,
                    0b00011100,
                    0b00111110,
                    0b00000000,
                ];
                break;
            case '2':
                $r = [
                    0b00000000,
                    0b00111110,
                    0b00000011,
                    0b00111111,
                    0b01100000,
                    0b01100000,
                    0b01111111,
                    0b00000000,
                ];
                break;
            case '3':
                $r = [
                    0b00000000,
                    0b00111110,
                    0b01100011,
                    0b00001110,
                    0b00000011,
                    0b01100011,
                    0b00111110,
                    0b00000000,
                ];
                break;
            case '4':
                $r = [
                    0b00000000,
                    0b00011110,
                    0b00110110,
                    0b01100110,
                    0b01111111,
                    0b00000110,
                    0b00001111,
                    0b00000000,
                ];
                break;
            case '5':
                $r = [
                    0b00000000,
                    0b01111111,
                    0b01100000,
                    0b01111110,
                    0b00000011,
                    0b01100011,
                    0b00111110,
                    0b00000000,
                ];
                break;
            case '6':
                $r = [
                    0b00000000,
                    0b00111110,
                    0b01100000,
                    0b01111110,
                    0b01100011,
                    0b01100011,
                    0b00111110,
                    0b00000000,
                ];
                break;
            case '7':
                $r = [
                    0b00000000,
                    0b01111111,
                    0b01100011,
                    0b00000110,
                    0b00001100,
                    0b00011100,
                    0b00011100,
                    0b00000000,
                ];
                break;
            case '8':
                $r = [
                    0b00000000,
                    0b00111110,
                    0b01100011,
                    0b00111110,
                    0b01100011,
                    0b01100011,
                    0b00111110,
                    0b00000000,
                ];
                break;
            case '9':
                $r = [
                    0b00000000,
                    0b00111110,
                    0b01100011,
                    0b01100011,
                    0b00111111,
                    0b00000011,
                    0b00111110,
                    0b00000000,
                ];
                break;
            case ':':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00011100,
                    0b00011100,
                    0b00000000,
                    0b00011100,
                    0b00011100,
                    0b00000000,
                ];
                break;
            case ';':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00011100,
                    0b00011100,
                    0b00000000,
                    0b00011100,
                    0b00011100,
                    0b00111000,
                ];
                break;
            case '<':
                $r = [
                    0b00000000,
                    0b00011110,
                    0b00111100,
                    0b01110000,
                    0b01110000,
                    0b00111100,
                    0b00011110,
                    0b00000000,
                ];
                break;
            case '=':
                $r = [
                    0b00000000,
                    0b01111110,
                    0b01111110,
                    0b00000000,
                    0b00000000,
                    0b01111110,
                    0b01111110,
                    0b00000000,
                ];
                break;
            case '>':
                $r = [
                    0b00000000,
                    0b01111000,
                    0b00111100,
                    0b00011110,
                    0b00001110,
                    0b00111100,
                    0b01111000,
                    0b00000000,
                ];
                break;
            case '?':
                $r = [
                    0b00000000,
                    0b00111100,
                    0b01101110,
                    0b00001110,
                    0b00011100,
                    0b00000000,
                    0b00011000,
                    0b00000000,
                ];
                break;
            case '@':
                $r = [
                    0b00000000,
                    0b00111110,
                    0b01100011,
                    0b01100011,
                    0b01100011,
                    0b01111111,
                    0b01100011,
                    0b00000110,
                ];
                break;
            case 'A':
                $r = [
                    0b00000000,
                    0b00111110,
                    0b01100011,
                    0b01100011,
                    0b01100011,
                    0b01111111,
                    0b01100011,
                    0b00000000,
                ];
                break;
            case 'B':
                $r = [
                    0b00000000,
                    0b01111110,
                    0b00110011,
                    0b00111110,
                    0b00110011,
                    0b00110011,
                    0b01111110,
                    0b00000000,
                ];
                break;
            case 'C':
                $r = [
                    0b00000000,
                    0b00111110,
                    0b01100011,
                    0b01100000,
                    0b01100000,
                    0b01100011,
                    0b00111110,
                    0b00000000,
                ];
                break;
            case 'D':
                $r = [
                    0b00000000,
                    0b01111110,
                    0b00110011,
                    0b00110011,
                    0b00110011,
                    0b00110011,
                    0b01111110,
                    0b00000000,
                ];
                break;
            case 'E':
                $r = [
                    0b00000000,
                    0b01111111,
                    0b00110001,
                    0b00111100,
                    0b00110000,
                    0b00110001,
                    0b01111111,
                    0b00000000,
                ];
                break;
            case 'F':
                $r = [
                    0b00000000,
                    0b01111111,
                    0b00110001,
                    0b00111100,
                    0b00110000,
                    0b00110000,
                    0b01111000,
                    0b00000000,
                ];
                break;
            case 'G':
                $r = [
                    0b00000000,
                    0b00111111,
                    0b01100011,
                    0b01100000,
                    0b01100111,
                    0b01100011,
                    0b00111111,
                    0b00000000,
                ];
                break;
            case 'H':
                $r = [
                    0b00000000,
                    0b01100011,
                    0b01100011,
                    0b01111111,
                    0b01100011,
                    0b01100011,
                    0b01100011,
                    0b00000000,
                ];
                break;
            case 'I':
                $r = [
                    0b00000000,
                    0b01111110,
                    0b00011000,
                    0b00011000,
                    0b00011000,
                    0b00011000,
                    0b01111110,
                    0b00000000,
                ];
                break;
            case 'J':
                $r = [
                    0b00000000,
                    0b01111111,
                    0b01100011,
                    0b00000011,
                    0b00000011,
                    0b00000011,
                    0b01100011,
                    0b00111110,
                ];
                break;
            case 'K':
                $r = [
                    0b00000000,
                    0b01100111,
                    0b01101110,
                    0b01111100,
                    0b01101100,
                    0b01100110,
                    0b01100011,
                    0b00000000,
                ];
                break;
            case 'L':
                $r = [
                    0b00000000,
                    0b01111000,
                    0b00110000,
                    0b00110000,
                    0b00110000,
                    0b00110011,
                    0b01111111,
                    0b00000000,
                ];
                break;
            case 'M':
                $r = [
                    0b00000000,
                    0b01100011,
                    0b01110111,
                    0b01111111,
                    0b01101011,
                    0b01100011,
                    0b01110111,
                    0b00000000,
                ];
                break;
            case 'N':
                $r = [
                    0b00000000,
                    0b01100011,
                    0b01110011,
                    0b01111011,
                    0b01101111,
                    0b01100111,
                    0b01100011,
                    0b00000000,
                ];
                break;
            case 'O':
                $r = [
                    0b00000000,
                    0b00111110,
                    0b01100011,
                    0b01100011,
                    0b01100011,
                    0b01100011,
                    0b00111110,
                    0b00000000,
                ];
                break;
            case 'P':
                $r = [
                    0b00000000,
                    0b01111110,
                    0b00110011,
                    0b00110011,
                    0b00111110,
                    0b00110000,
                    0b01111000,
                    0b00000000,
                ];
                break;
            case 'Q':
                $r = [
                    0b00000000,
                    0b00111110,
                    0b01100011,
                    0b01100011,
                    0b01101011,
                    0b01100110,
                    0b00111011,
                    0b00000000,
                ];
                break;
            case 'R':
                $r = [
                    0b00000000,
                    0b01111110,
                    0b00110011,
                    0b00110011,
                    0b00111110,
                    0b00110011,
                    0b01111011,
                    0b00000000,
                ];
                break;
            case 'S':
                $r = [
                    0b00000000,
                    0b00111110,
                    0b01100000,
                    0b00111110,
                    0b00000011,
                    0b00000011,
                    0b01111110,
                    0b00000000,
                ];
                break;
            case 'T':
                $r = [
                    0b00000000,
                    0b01111111,
                    0b01011101,
                    0b00011100,
                    0b00011100,
                    0b00011100,
                    0b00111110,
                    0b00000000,
                ];
                break;
            case 'U':
                $r = [
                    0b00000000,
                    0b01100011,
                    0b01100011,
                    0b01100011,
                    0b01100011,
                    0b01100011,
                    0b00111111,
                    0b00000000,
                ];
                break;
            case 'V':
                $r = [
                    0b00000000,
                    0b01100011,
                    0b01100011,
                    0b01100011,
                    0b00110110,
                    0b00011100,
                    0b00001000,
                    0b00000000,
                ];
                break;
            case 'W':
                $r = [
                    0b00000000,
                    0b01110111,
                    0b01100011,
                    0b01101011,
                    0b01111111,
                    0b01110111,
                    0b01100011,
                    0b00000000,
                ];
                break;
            case 'X':
                $r = [
                    0b00000000,
                    0b01100011,
                    0b00110110,
                    0b00011100,
                    0b00011100,
                    0b00110110,
                    0b01100011,
                    0b00000000,
                ];
                break;
            case 'Y':
                $r = [
                    0b00000000,
                    0b01100011,
                    0b01100011,
                    0b00110110,
                    0b00011100,
                    0b00011100,
                    0b00011100,
                    0b00000000,
                ];
                break;
            case 'Z':
                $r = [
                    0b00000000,
                    0b01111111,
                    0b01100110,
                    0b00001100,
                    0b00011000,
                    0b00110011,
                    0b01111111,
                    0b00000000,
                ];
                break;
            case '[':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00111110,
                    0b00000011,
                    0b00111111,
                    0b01100011,
                    0b00111111,
                    0b00000110,
                ];
                break;
            case '\\':
                $r = [
                    0b00000000,
                    0b00000011,
                    0b00111110,
                    0b01100011,
                    0b01100000,
                    0b01100011,
                    0b00111110,
                    0b00000000,
                ];
                break;
            case ']':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00111110,
                    0b01100011,
                    0b01111110,
                    0b01100000,
                    0b00111111,
                    0b00000110,
                ];
                break;
            case '^':
                $r = [
                    0b00000000,
                    0b01111111,
                    0b00000110,
                    0b00111110,
                    0b00011000,
                    0b00110000,
                    0b01111111,
                    0b00000000,
                ];
                break;
            case '_':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b11111111,
                ];
                break;
            case 'a':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00111110,
                    0b00000011,
                    0b00111111,
                    0b01100011,
                    0b00111111,
                    0b00000000,
                ];
                break;
            case 'b':
                $r = [
                    0b00000000,
                    0b01100000,
                    0b01111110,
                    0b01100011,
                    0b01100011,
                    0b01100011,
                    0b01111110,
                    0b00000000,
                ];
                break;
            case 'c':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00111110,
                    0b01100011,
                    0b01100000,
                    0b01100011,
                    0b00111110,
                    0b00000000,
                ];
                break;
            case 'd':
                $r = [
                    0b00000000,
                    0b00000011,
                    0b00111111,
                    0b01100011,
                    0b01100011,
                    0b01100011,
                    0b00111111,
                    0b00000000,
                ];
                break;
            case 'e':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00111110,
                    0b01100011,
                    0b01111110,
                    0b01100000,
                    0b00111111,
                    0b00000000,
                ];
                break;
            case 'f':
                $r = [
                    0b00000000,
                    0b00011100,
                    0b00110110,
                    0b00110000,
                    0b01111000,
                    0b00110000,
                    0b00110000,
                    0b00110000,
                ];
                break;
            case 'g':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00111111,
                    0b01100011,
                    0b01100011,
                    0b00111111,
                    0b00000011,
                    0b01111110,
                ];
                break;
            case 'h':
                $r = [
                    0b00000000,
                    0b01100000,
                    0b01111110,
                    0b01100011,
                    0b01100011,
                    0b01100011,
                    0b01100011,
                    0b00000000,
                ];
                break;
            case 'i':
                $r = [
                    0b00000000,
                    0b00011100,
                    0b00000000,
                    0b00111100,
                    0b00011100,
                    0b00011100,
                    0b00111110,
                    0b00000000,
                ];
                break;
            case 'j':
                $r = [
                    0b00000000,
                    0b00000011,
                    0b00000000,
                    0b00000011,
                    0b00000011,
                    0b00000011,
                    0b00110011,
                    0b00011110,
                ];
                break;
            case 'k':
                $r = [
                    0b00000000,
                    0b01100000,
                    0b01101110,
                    0b01111000,
                    0b01101100,
                    0b01100110,
                    0b01100011,
                    0b00000000,
                ];
                break;
            case 'l':
                $r = [
                    0b00000000,
                    0b00111100,
                    0b00011100,
                    0b00011100,
                    0b00011100,
                    0b00011100,
                    0b00111110,
                    0b00000000,
                ];
                break;
            case 'm':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01100011,
                    0b01110111,
                    0b01111111,
                    0b01101011,
                    0b01100011,
                    0b00000000,
                ];
                break;
            case 'n':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01111110,
                    0b01100011,
                    0b01100011,
                    0b01100011,
                    0b01100011,
                    0b00000000,
                ];
                break;
            case 'o':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00111110,
                    0b01100011,
                    0b01100011,
                    0b01100011,
                    0b00111110,
                    0b00000000,
                ];
                break;
            case 'p':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01111110,
                    0b00110011,
                    0b00110011,
                    0b00111110,
                    0b00110000,
                    0b01111000,
                ];
                break;
            case 'q':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00111111,
                    0b01100110,
                    0b01100110,
                    0b00111110,
                    0b00000110,
                    0b00001111,
                ];
                break;
            case 'r':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01111110,
                    0b00110011,
                    0b00110000,
                    0b00110000,
                    0b01111000,
                    0b00000000,
                ];
                break;
            case 's':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00111110,
                    0b01100000,
                    0b00111110,
                    0b00000011,
                    0b01111110,
                    0b00000000,
                ];
                break;
            case 't':
                $r = [
                    0b00000000,
                    0b00011000,
                    0b01111110,
                    0b00011000,
                    0b00011000,
                    0b00011011,
                    0b00001110,
                    0b00000000,
                ];
                break;
            case 'u':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01100011,
                    0b01100011,
                    0b01100011,
                    0b01100011,
                    0b00111111,
                    0b00000000,
                ];
                break;
            case 'v':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01100011,
                    0b01100011,
                    0b00110110,
                    0b00011100,
                    0b00001000,
                    0b00000000,
                ];
                break;
            case 'w':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01100011,
                    0b01101011,
                    0b01111111,
                    0b01110111,
                    0b01100011,
                    0b00000000,
                ];
                break;
            case 'x':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01100011,
                    0b00110110,
                    0b00011100,
                    0b00110110,
                    0b01100011,
                    0b00000000,
                ];
                break;
            case 'y':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01100011,
                    0b01100011,
                    0b01100011,
                    0b00111111,
                    0b00000011,
                    0b01111110,
                ];
                break;
            case 'z':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01111110,
                    0b01001100,
                    0b00011000,
                    0b00110001,
                    0b01111111,
                ];
                break;
            default:
                $r = [0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0, 0x0,];
                break;
        }
        $result = [];
        foreach ($r as $row) {
            $bin = str_pad(decbin($row), 8, '0', STR_PAD_LEFT);
            $bin = str_replace(['1', '0'], [$bit1, $bit2], $bin);
            $result[] = $bin;
        }
        return $result;
    }

    /**
     * Font Atr
     * @param string  $letter the letter to generate
     * @param ?string $bit1   the character to show then the bit is "on"
     * @param string  $bit2   the character to show then the bit is "off"
     * @return array return an array of 8 lines and each line has 8 characters to show the letter.
     */
    protected function fontAtr(string $letter, ?string $bit1 = null, string $bit2 = ' '): array
    {
        $bit1 = $bit1 ?? $letter;
        switch ($letter) {
            case '!':
                $r = [
                    0b00000000,
                    0b00011000,
                    0b00011000,
                    0b00011000,
                    0b00011000,
                    0b00000000,
                    0b00011000,
                    0b00000000
                ];
                break;
            case '"':
                $r = [
                    0b00000000,
                    0b01100110,
                    0b01100110,
                    0b01100110,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000
                ];
                break;
            case '#':
                $r = [
                    0b00000000,
                    0b01100110,
                    0b11111111,
                    0b01100110,
                    0b01100110,
                    0b11111111,
                    0b01100110,
                    0b00000000
                ];
                break;
            case '$':
                $r = [
                    0b00011000,
                    0b00111110,
                    0b01100000,
                    0b00111100,
                    0b00000110,
                    0b01111100,
                    0b00011000,
                    0b00000000
                ];
                break;
            case '%':
                $r = [
                    0b00000000,
                    0b01100110,
                    0b01101100,
                    0b00011000,
                    0b00110000,
                    0b01100110,
                    0b01000110,
                    0b00000000
                ];
                break;
            case '&':
                $r = [
                    0b00011100,
                    0b00110110,
                    0b00011100,
                    0b00111000,
                    0b01101111,
                    0b01100110,
                    0b00111011,
                    0b00000000
                ];
                break;
            case '\'':
                $r = [
                    0b00000000,
                    0b00011000,
                    0b00011000,
                    0b00011000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000
                ];
                break;
            case '(':
                $r = [
                    0b00000000,
                    0b00001110,
                    0b00011100,
                    0b00011000,
                    0b00011000,
                    0b00011100,
                    0b00001110,
                    0b00000000
                ];
                break;
            case ')':
                $r = [
                    0b00000000,
                    0b01110000,
                    0b00111000,
                    0b00011000,
                    0b00011000,
                    0b00111000,
                    0b01110000,
                    0b00000000
                ];
                break;
            case '*':
                $r = [
                    0b00000000,
                    0b01100110,
                    0b00111100,
                    0b11111111,
                    0b00111100,
                    0b01100110,
                    0b00000000,
                    0b00000000
                ];
                break;
            case '+':
                $r = [
                    0b00000000,
                    0b00011000,
                    0b00011000,
                    0b01111110,
                    0b00011000,
                    0b00011000,
                    0b00000000,
                    0b00000000
                ];
                break;
            case ',
':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00011000,
                    0b00011000,
                    0b00110000
                ];
                break;
            case '-':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b01111110,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000
                ];
                break;
            case '.':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00011000,
                    0b00011000,
                    0b00000000
                ];
                break;
            case '/':
                $r = [
                    0b00000000,
                    0b00000110,
                    0b00001100,
                    0b00011000,
                    0b00110000,
                    0b01100000,
                    0b01000000,
                    0b00000000
                ];
                break;
            case '0':
                $r = [
                    0b00000000,
                    0b00111100,
                    0b01100110,
                    0b01101110,
                    0b01110110,
                    0b01100110,
                    0b00111100,
                    0b00000000
                ];
                break;
            case '1':
                $r = [
                    0b00000000,
                    0b00011000,
                    0b00111000,
                    0b00011000,
                    0b00011000,
                    0b00011000,
                    0b01111110,
                    0b00000000
                ];
                break;
            case '2':
                $r = [
                    0b00000000,
                    0b00111100,
                    0b01100110,
                    0b00001100,
                    0b00011000,
                    0b00110000,
                    0b01111110,
                    0b00000000
                ];
                break;
            case '3':
                $r = [
                    0b00000000,
                    0b01111110,
                    0b00001100,
                    0b00011000,
                    0b00001100,
                    0b01100110,
                    0b00111100,
                    0b00000000
                ];
                break;
            case '4':
                $r = [
                    0b00000000,
                    0b00001100,
                    0b00011100,
                    0b00111100,
                    0b01101100,
                    0b01111110,
                    0b00001100,
                    0b00000000
                ];
                break;
            case '5':
                $r = [
                    0b00000000,
                    0b01111110,
                    0b01100000,
                    0b01111100,
                    0b00000110,
                    0b01100110,
                    0b00111100,
                    0b00000000
                ];
                break;
            case '6':
                $r = [
                    0b00000000,
                    0b00111100,
                    0b01100000,
                    0b01111100,
                    0b01100110,
                    0b01100110,
                    0b00111100,
                    0b00000000
                ];
                break;
            case '7':
                $r = [
                    0b00000000,
                    0b01111110,
                    0b00000110,
                    0b00001100,
                    0b00011000,
                    0b00110000,
                    0b00110000,
                    0b00000000
                ];
                break;
            case '8':
                $r = [
                    0b00000000,
                    0b00111100,
                    0b01100110,
                    0b00111100,
                    0b01100110,
                    0b01100110,
                    0b00111100,
                    0b00000000
                ];
                break;
            case '9':
                $r = [
                    0b00000000,
                    0b00111100,
                    0b01100110,
                    0b00111110,
                    0b00000110,
                    0b00001100,
                    0b00111000,
                    0b00000000
                ];
                break;
            case ':':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00011000,
                    0b00011000,
                    0b00000000,
                    0b00011000,
                    0b00011000,
                    0b00000000
                ];
                break;
            case ';':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00011000,
                    0b00011000,
                    0b00000000,
                    0b00011000,
                    0b00011000,
                    0b00110000
                ];
                break;
            case '<':
                $r = [
                    0b00000110,
                    0b00001100,
                    0b00011000,
                    0b00110000,
                    0b00011000,
                    0b00001100,
                    0b00000110,
                    0b00000000
                ];
                break;
            case '=':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01111110,
                    0b00000000,
                    0b00000000,
                    0b01111110,
                    0b00000000,
                    0b00000000
                ];
                break;
            case '>':
                $r = [
                    0b01100000,
                    0b00110000,
                    0b00011000,
                    0b00001100,
                    0b00011000,
                    0b00110000,
                    0b01100000,
                    0b00000000
                ];
                break;
            case '?':
                $r = [
                    0b00000000,
                    0b00111100,
                    0b01100110,
                    0b00001100,
                    0b00011000,
                    0b00000000,
                    0b00011000,
                    0b00000000
                ];
                break;
            case '@':
                $r = [
                    0b00000000,
                    0b00111100,
                    0b01100110,
                    0b01101110,
                    0b01101110,
                    0b01100000,
                    0b00111110,
                    0b00000000
                ];
                break;
            case 'A':
                $r = [
                    0b00000000,
                    0b00011000,
                    0b00111100,
                    0b01100110,
                    0b01100110,
                    0b01111110,
                    0b01100110,
                    0b00000000
                ];
                break;
            case 'B':
                $r = [
                    0b00000000,
                    0b01111100,
                    0b01100110,
                    0b01111100,
                    0b01100110,
                    0b01100110,
                    0b01111100,
                    0b00000000
                ];
                break;
            case 'C':
                $r = [
                    0b00000000,
                    0b00111100,
                    0b01100110,
                    0b01100000,
                    0b01100000,
                    0b01100110,
                    0b00111100,
                    0b00000000
                ];
                break;
            case 'D':
                $r = [
                    0b00000000,
                    0b01111000,
                    0b01101100,
                    0b01100110,
                    0b01100110,
                    0b01101100,
                    0b01111000,
                    0b00000000
                ];
                break;
            case 'E':
                $r = [
                    0b00000000,
                    0b01111110,
                    0b01100000,
                    0b01111100,
                    0b01100000,
                    0b01100000,
                    0b01111110,
                    0b00000000
                ];
                break;
            case 'F':
                $r = [
                    0b00000000,
                    0b01111110,
                    0b01100000,
                    0b01111100,
                    0b01100000,
                    0b01100000,
                    0b01100000,
                    0b00000000
                ];
                break;
            case 'G':
                $r = [
                    0b00000000,
                    0b00111110,
                    0b01100000,
                    0b01100000,
                    0b01101110,
                    0b01100110,
                    0b00111110,
                    0b00000000
                ];
                break;
            case 'H':
                $r = [
                    0b00000000,
                    0b01100110,
                    0b01100110,
                    0b01111110,
                    0b01100110,
                    0b01100110,
                    0b01100110,
                    0b00000000
                ];
                break;
            case 'I':
                $r = [
                    0b00000000,
                    0b01111110,
                    0b00011000,
                    0b00011000,
                    0b00011000,
                    0b00011000,
                    0b01111110,
                    0b00000000
                ];
                break;
            case 'J':
                $r = [
                    0b00000000,
                    0b00000110,
                    0b00000110,
                    0b00000110,
                    0b00000110,
                    0b01100110,
                    0b00111100,
                    0b00000000
                ];
                break;
            case 'K':
                $r = [
                    0b00000000,
                    0b01100110,
                    0b01101100,
                    0b01111000,
                    0b01111000,
                    0b01101100,
                    0b01100110,
                    0b00000000
                ];
                break;
            case 'L':
                $r = [
                    0b00000000,
                    0b01100000,
                    0b01100000,
                    0b01100000,
                    0b01100000,
                    0b01100000,
                    0b01111110,
                    0b00000000
                ];
                break;
            case 'M':
                $r = [
                    0b00000000,
                    0b01100011,
                    0b01110111,
                    0b01111111,
                    0b01101011,
                    0b01100011,
                    0b01100011,
                    0b00000000
                ];
                break;
            case 'N':
                $r = [
                    0b00000000,
                    0b01100110,
                    0b01110110,
                    0b01111110,
                    0b01111110,
                    0b01101110,
                    0b01100110,
                    0b00000000
                ];
                break;
            case 'O':
                $r = [
                    0b00000000,
                    0b00111100,
                    0b01100110,
                    0b01100110,
                    0b01100110,
                    0b01100110,
                    0b00111100,
                    0b00000000
                ];
                break;
            case 'P':
                $r = [
                    0b00000000,
                    0b01111100,
                    0b01100110,
                    0b01100110,
                    0b01111100,
                    0b01100000,
                    0b01100000,
                    0b00000000
                ];
                break;
            case 'Q':
                $r = [
                    0b00000000,
                    0b00111100,
                    0b01100110,
                    0b01100110,
                    0b01100110,
                    0b01101100,
                    0b00110110,
                    0b00000000
                ];
                break;
            case 'R':
                $r = [
                    0b00000000,
                    0b01111100,
                    0b01100110,
                    0b01100110,
                    0b01111100,
                    0b01101100,
                    0b01100110,
                    0b00000000
                ];
                break;
            case 'S':
                $r = [
                    0b00000000,
                    0b00111100,
                    0b01100000,
                    0b00111100,
                    0b00000110,
                    0b00000110,
                    0b00111100,
                    0b00000000
                ];
                break;
            case 'T':
                $r = [
                    0b00000000,
                    0b01111110,
                    0b00011000,
                    0b00011000,
                    0b00011000,
                    0b00011000,
                    0b00011000,
                    0b00000000
                ];
                break;
            case 'U':
                $r = [
                    0b00000000,
                    0b01100110,
                    0b01100110,
                    0b01100110,
                    0b01100110,
                    0b01100110,
                    0b01111110,
                    0b00000000
                ];
                break;
            case 'V':
                $r = [
                    0b00000000,
                    0b01100110,
                    0b01100110,
                    0b01100110,
                    0b01100110,
                    0b00111100,
                    0b00011000,
                    0b00000000
                ];
                break;
            case 'W':
                $r = [
                    0b00000000,
                    0b01100011,
                    0b01100011,
                    0b01101011,
                    0b01111111,
                    0b01110111,
                    0b01100011,
                    0b00000000
                ];
                break;
            case 'X':
                $r = [
                    0b00000000,
                    0b01100110,
                    0b01100110,
                    0b00111100,
                    0b00111100,
                    0b01100110,
                    0b01100110,
                    0b00000000
                ];
                break;
            case 'Y':
                $r = [
                    0b00000000,
                    0b01100110,
                    0b01100110,
                    0b00111100,
                    0b00011000,
                    0b00011000,
                    0b00011000,
                    0b00000000
                ];
                break;
            case 'Z':
                $r = [
                    0b00000000,
                    0b01111110,
                    0b00001100,
                    0b00011000,
                    0b00110000,
                    0b01100000,
                    0b01111110,
                    0b00000000
                ];
                break;
            case '[
':
                $r = [
                    0b00000000,
                    0b00011110,
                    0b00011000,
                    0b00011000,
                    0b00011000,
                    0b00011000,
                    0b00011110,
                    0b00000000
                ];
                break;
            case '\\':
                $r = [
                    0b00000000,
                    0b01000000,
                    0b01100000,
                    0b00110000,
                    0b00011000,
                    0b00001100,
                    0b00000110,
                    0b00000000
                ];
                break;
            case '
]':
                $r = [
                    0b00000000,
                    0b01111000,
                    0b00011000,
                    0b00011000,
                    0b00011000,
                    0b00011000,
                    0b01111000,
                    0b00000000
                ];
                break;
            case '^':
                $r = [
                    0b00000000,
                    0b00001000,
                    0b00011100,
                    0b00110110,
                    0b01100011,
                    0b00000000,
                    0b00000000,
                    0b00000000
                ];
                break;
            case '_':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b11111111,
                    0b00000000
                ];
                break;
            case 'a':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00111100,
                    0b00000110,
                    0b00111110,
                    0b01100110,
                    0b00111110,
                    0b00000000
                ];
                break;
            case 'b':
                $r = [
                    0b00000000,
                    0b01100000,
                    0b01100000,
                    0b01111100,
                    0b01100110,
                    0b01100110,
                    0b01111100,
                    0b00000000
                ];
                break;
            case 'c':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00111100,
                    0b01100000,
                    0b01100000,
                    0b01100000,
                    0b00111100,
                    0b00000000
                ];
                break;
            case 'd':
                $r = [
                    0b00000000,
                    0b00000110,
                    0b00000110,
                    0b00111110,
                    0b01100110,
                    0b01100110,
                    0b00111110,
                    0b00000000
                ];
                break;
            case 'e':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00111100,
                    0b01100110,
                    0b01111110,
                    0b01100000,
                    0b00111100,
                    0b00000000
                ];
                break;
            case 'f':
                $r = [
                    0b00000000,
                    0b00001110,
                    0b00011000,
                    0b00111110,
                    0b00011000,
                    0b00011000,
                    0b00011000,
                    0b00000000
                ];
                break;
            case 'g':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00111110,
                    0b01100110,
                    0b01100110,
                    0b00111110,
                    0b00000110,
                    0b01111100
                ];
                break;
            case 'h':
                $r = [
                    0b00000000,
                    0b01100000,
                    0b01100000,
                    0b01111100,
                    0b01100110,
                    0b01100110,
                    0b01100110,
                    0b00000000
                ];
                break;
            case 'i':
                $r = [
                    0b00000000,
                    0b00011000,
                    0b00000000,
                    0b00111000,
                    0b00011000,
                    0b00011000,
                    0b00111100,
                    0b00000000
                ];
                break;
            case 'j':
                $r = [
                    0b00000000,
                    0b00000110,
                    0b00000000,
                    0b00000110,
                    0b00000110,
                    0b00000110,
                    0b00000110,
                    0b00111100
                ];
                break;
            case 'k':
                $r = [
                    0b00000000,
                    0b01100000,
                    0b01100000,
                    0b01101100,
                    0b01111000,
                    0b01101100,
                    0b01100110,
                    0b00000000
                ];
                break;
            case 'l':
                $r = [
                    0b00000000,
                    0b00111000,
                    0b00011000,
                    0b00011000,
                    0b00011000,
                    0b00011000,
                    0b00111100,
                    0b00000000
                ];
                break;
            case 'm':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01100110,
                    0b01111111,
                    0b01111111,
                    0b01101011,
                    0b01100011,
                    0b00000000
                ];
                break;
            case 'n':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01111100,
                    0b01100110,
                    0b01100110,
                    0b01100110,
                    0b01100110,
                    0b00000000
                ];
                break;
            case 'o':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00111100,
                    0b01100110,
                    0b01100110,
                    0b01100110,
                    0b00111100,
                    0b00000000
                ];
                break;
            case 'p':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01111100,
                    0b01100110,
                    0b01100110,
                    0b01111100,
                    0b01100000,
                    0b01100000
                ];
                break;
            case 'q':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00111110,
                    0b01100110,
                    0b01100110,
                    0b00111110,
                    0b00000110,
                    0b00000110
                ];
                break;
            case 'r':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01111100,
                    0b01100110,
                    0b01100000,
                    0b01100000,
                    0b01100000,
                    0b00000000
                ];
                break;
            case 's':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00111110,
                    0b01100000,
                    0b00111100,
                    0b00000110,
                    0b01111100,
                    0b00000000
                ];
                break;
            case 't':
                $r = [
                    0b00000000,
                    0b00011000,
                    0b01111110,
                    0b00011000,
                    0b00011000,
                    0b00011000,
                    0b00001110,
                    0b00000000
                ];
                break;
            case 'u':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01100110,
                    0b01100110,
                    0b01100110,
                    0b01100110,
                    0b00111110,
                    0b00000000
                ];
                break;
            case 'v':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01100110,
                    0b01100110,
                    0b01100110,
                    0b00111100,
                    0b00011000,
                    0b00000000
                ];
                break;
            case 'w':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01100011,
                    0b01101011,
                    0b01111111,
                    0b00111110,
                    0b00110110,
                    0b00000000
                ];
                break;
            case 'x':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01100110,
                    0b00111100,
                    0b00011000,
                    0b00111100,
                    0b01100110,
                    0b00000000
                ];
                break;
            case 'y':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01100110,
                    0b01100110,
                    0b01100110,
                    0b00111110,
                    0b00001100,
                    0b01111000
                ];
                break;
            case 'z':
                $r = [
                    0b00000000,
                    0b00000000,
                    0b01111110,
                    0b00001100,
                    0b00011000,
                    0b00110000,
                    0b01111110,
                    0b00000000
                ];
                break;
            case ' ':
            default:
                $r = [
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000,
                    0b00000000
                ];
                break;
        }
        $result = [];
        foreach ($r as $row) {
            $bin = str_pad(decbin($row), 8, '0', STR_PAD_LEFT);
            $bin = str_replace(['1', '0'], [$bit1, $bit2], $bin);
            $result[] = $bin;
        }
        return $result;
    }

    /**
     * It reads the value-key of a parameter selected. It is useful for a list of elements.<br/>
     * <b>Example:</b><br/>
     * ```php
     * // [1] option1
     * // [2] option2
     * // select a value [] 2
     * $v=$this->getValueKey('idparam'); // it will return 2 instead of "option2"
     * ```
     * @param string $key the key of the parameter to read the value-key
     * @return mixed|null It returns the value of the parameter or null if not found.
     *
     */
    public function getValueKey(string $key)
    {
        $parameter = $this->getParameter($key);
        if (!$parameter->isValid()) {
            return null;
        }
        return $parameter->valueKey;
    }

    /**
     * It will return true if the PHP is running on CLI<br/>
     * If the constructor specified a file, then it is also used for validation.
     * <b>Example:</b><br/>
     * ```php
     * // page.php:
     * $inst=new CliOne('page.php'); // this security avoid calling the cli when this file is called by others.
     * if($inst->isCli()) {
     *    echo "Is CLI and the current page is page.php";
     * }
     * ```
     * @return bool
     */
    public function isCli(): bool
    {
        /*if (defined('PHPUNIT_COMPOSER_INSTALL') || defined('__PHPUNIT_PHAR__')) {
            // phpunit is running
            return true;
        }*/
        if ($this->origin !== null && isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) !== $this->origin) {
            // it is not running the right file.
            $this->error = 'You are not the right file in the whitelist ' . basename($_SERVER['PHP_SELF']);
            return false;
        }
        // false if it is running a web.
        if (defined('STDIN') && !http_response_code()) {
            $this->error = '';
            return true;
        }
        $this->error = 'HTTP response found or not STDIN';
        return false;
    }

    /**
     * It gets the STDIN exclusively if the value is passed by pipes. If not, it returns null;
     * @return ?string
     */
    public function getSTDIN(): ?string
    {
        if (@fstat(STDIN)['size'] === 0) {
            return null;
        }
        $r = stream_get_contents(STDIN);
        return ($r === false) ? null : $r;
    }

    /**
     * It reads information from a file. The information will be de-serialized.
     * @param string $filename         the filename with or without extension.
     * @param string $defaultExtension the default extension.
     * @return array it returns an array of the type [bool,mixed]<br/>
     *                                 In error, it returns [false,"error message"]<br/>
     *                                 In success, it returns [true,values de-serialized]<br/>
     */
    public function readData(string $filename, string $defaultExtension = '.config.php'): ?array
    {
        $filename = $this->addExtensionFile($filename, $defaultExtension);
        try {
            $content = @file_get_contents($filename);
            if ($content === false) {
                throw new RuntimeException("Unable to read file $filename");
            }
            $content = substr($content, strpos($content, "\n") + 1); // remove the first line.
            return [true, json_decode($content, true, 512, JSON_THROW_ON_ERROR)];
        } catch (Exception $ex) {
            return [false, $ex->getMessage()];
        }
    }

    /**
     * It reads information from a file. The information is evaluated, so the file must be safe.<br/>
     * @param string $filename         the filename with or without extension.
     * @param string $defaultExtension the default extension.
     * @return array it returns an array of the type [bool,content,namevar]<br/>
     *                                 In error, it returns[ [false,"error message",""]<br/>
     *                                 In success, it returns [true,[1,2,3],'$content']<br/>
     */
    public function readDataPHPFormat(string $filename, string $defaultExtension = '.config.php'): ?array
    {
        $filename = $this->addExtensionFile($filename, $defaultExtension);
        try {
            $content = @file_get_contents($filename);
            if ($content === false) {
                throw new RuntimeException("Unable to read file $filename");
            }
            $p0 = strpos($content, '$');
            if ($p0 === false) {
                throw new RuntimeException("Format incorrect $filename, no \"\$\" found");
            }
            $p1 = STRPOS($content, '=', $p0);
            if ($p1 === false) {
                throw new RuntimeException("Format incorrect $filename, no \"=\" found");
            }
            $varname = substr($content, $p0, $p1 - $p0);
            $content = eval('return ' . substr($content, $p1 + 1));
            return [true, $content, $varname];
        } catch (Exception $ex) {
            return [false, $ex->getMessage(), ''];
        }
    }

    /**
     * Returns true if the parameter is present with or without data.<br/>
     * The parameter is not changed, neither the default values nor user input are applied<br/>
     * Returned Values:<br/>
     * <ul>
     * <li><b>none</b> the value is not present, ex: </li>
     * <li><b>empty</b> the value is present but is empty, ex: -arg1</li>
     * <li><b>value</b> the value is present, and it has a value, ex: -arg1 value</li>
     * </ul>
     * @param string $key
     * @return string=['none','empty','value']
     */
    public function isParameterPresent(string $key): string
    {
        $parameter = $this->getParameter($key);
        if (!$parameter->isValid()) {
            return 'none';
        }
        //
        /** @noinspection DuplicatedCode */
        [$prefix, $prefixAlias, $position] = $this->prefixByType($parameter->type);
        if ($prefix === '' && $prefixAlias === '') {
            // this type of parameter is not readable.
            return 'none';
        }
        $trueName = $prefix . $parameter->key;
        if ($position === false) {
            $value = $this->argv[$trueName] ?? null;
        } else {
            $keys = array_keys($this->argv);
            switch ($parameter->type) {
                case 'first':
                case 'command':
                    if (count($this->argv) >= 1) {
                        $keyP = $keys[0];
                        $value = $keyP;
                    } else {
                        $value = null;
                    }
                    break;
                case 'second':
                    if (count($this->argv) > 1) {
                        $keyP = $keys[1];
                        $value = $keyP;
                    } else {
                        $value = null;
                    }
                    break;
                case 'last':
                    if (count($this->argv) > 1) {
                        $keyP = end($keys);
                        $value = $keyP;
                    } else {
                        $value = null;
                    }
                    break;
            }
            /** @noinspection PhpUndefinedVariableInspection */
            if (($value !== null && $keyP[0] === '-') || (!isset($this->argv[$trueName]) && $parameter->type !== 'command')) {
                // positional argument exists however it is a flag, or the argument does not exist
                $value = null;
            }
        }
        if ($value === null) {
            foreach ($parameter->alias as $ali) {
                $value = $this->argv[$prefixAlias . $ali] ?? null;
                if ($value !== null) {
                    return 'value';
                }
            }
            return 'none';
        }
        return $value ? 'value' : 'empty';
    }

    /**
     * Util class. It adds a default extension to a filename only if the filename doesn't have extension.
     * @param string $filename  The filename full or partial, example "file.jpg", "file", "/folder/file"
     * @param string $extension The extension to add including the dot, example ".ext".<br/>
     *                          The default value is ".config.php"
     * @return string
     */
    public function addExtensionFile(string $filename, string $extension = '.config.php'): string
    {
        $path = pathinfo($filename, PATHINFO_EXTENSION);
        if ($path === '') {
            $filename .= $extension;
        }
        return $filename;
    }

    /**
     * It saves the information into a file. The content will be serialized.
     * @param string $filename         the filename (without extension) to where the value will be saved.
     * @param mixed  $content          The content to save. It will be serialized.
     * @param string $defaultExtension The default extension.
     * @return string empty string if the operation is correct, otherwise it will return a message with the error.
     * @throws JsonException
     */
    public function saveData(string $filename, $content, string $defaultExtension = '.config.php'): string
    {
        $filename = $this->addExtensionFile($filename, $defaultExtension);
        $now = (new DateTime())->format('Y-m-d H:i');
        $contentData = "<?php http_response_code(404); die(1); " .
            "// eftec/CliOne(" . $this::VERSION . ") configuration file (date gen: $now)?>\n";
        $contentData .= json_encode($content, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        try {
            $f = @file_put_contents($filename, $contentData);
            if ($f === false) {
                throw new RuntimeException("Unable to save file $filename");
            }
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
        return "";
    }

    protected function varexport($expression): ?string
    {
        if (!is_array($expression)) {
            return var_export($expression, true);
        }
        $export = var_export($expression, TRUE);
        $export = preg_replace("/^( *)(.*)/m", '$1$1$2', $export);
        $array = preg_split("/\r\n|\n|\r/", $export);
        $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [NULL, ']$1', ' => ['], $array);
        return implode(PHP_EOL, array_filter(["["] + $array));
    }

    /**
     * It saves the information into a file. The content will be converted into a PHP file.<br/>
     * <b>example:</b><br/>
     * ```php
     * $this->saveDataPHPFormat('file',[1,2,3]); // it will save a file with the next content: $config=[1,2,3];
     * ```
     * @param string $filename         the filename (without extension) to where the value will be saved.
     * @param mixed  $content          The content to save. It will be serialized.
     * @param string $defaultExtension The default extension.
     * @param string $namevar          The name of the variable, excample: config or $config
     * @return string empty string if the operation is correct, otherwise it will return a message with the error.
     */
    public function saveDataPHPFormat(string $filename, $content, string $defaultExtension = '.config.php',
                                      string $namevar = 'config', string $description = "It is a configuration file"): string
    {
        $filename = $this->addExtensionFile($filename, $defaultExtension);
        $namevar = trim($namevar, '$ ');
        $now = (new DateTime())->format('Y-m-d H:i');
        $contentData = "<?php // eftec/CliOne(" . $this::VERSION .
            ") PHP configuration file (date gen: " . $now .
            "). DO NOT EDIT THIS FILE \n/**\n * $description\n */\n\$$namevar=" . $this->varexport($content) . ";\n";
        try {
            $f = @file_put_contents($filename, $contentData);
            if ($f === false) {
                throw new RuntimeException("Unable to save file $filename");
            }
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
        return "";
    }

    /**
     * It sets the alignment.  This method is stackable.<br/>
     * <b>Example:</b><br/>
     * ```php
     * $cli->setAlign('left','left','right')->setStyle('double')->showTable($values);
     * ```>
     * @param string $title          =['left','right','middle'][$i] the alignment of the title
     * @param string $content        =['left','right','middle'][$i] the alignment of the content
     * @param string $contentNumeric =['left','right','middle'][$i] the alignment of the content (numeric)
     * @return $this
     */
    public function setAlign(string $title = 'middle', string $content = 'middle', string $contentNumeric = 'middle'): self
    {
        $this->alignStack = [$title, $content, $contentNumeric];
        return $this;
    }

    /**
     * It sets the parameters using an array of the form [key=>value]<br/>
     * It also marks the parameters as missing=false
     * @param array      $array       the associative array to use to set the parameters.
     * @param array      $excludeKeys you can add a key that you want to exclude.<br/>
     *                                If the key is in the array and in this list, then it is excluded
     * @param array|null $includeKeys the whitelist of elements that only could be included.<br/>
     *                                Only keys that are in this list are added.
     * @return void
     */
    public function setArrayParam(array $array, array $excludeKeys = [], ?array $includeKeys = null): void
    {
        foreach ($array as $k => $v) {
            $found = false;
            if (!$this->in_array_i($k, $excludeKeys, true) && ($includeKeys === null || $this->in_array_i($k, $includeKeys, true))) {
                foreach ($this->parameters as $parameter) {
                    if ($parameter->key === $k) {
                        $parameter->value = $v;
                        $parameter->origin = 'set';
                        $this->refreshParamValueKey($parameter);
                        $parameter->missing = false;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $this->throwError("Parameter $k not defined");
                    $this->errorType = 'show';
                    return;
                }
            }
        }
        $this->errorType = 'show';
    }

    public function reconstructPath($includePHP = true, $trimArguments = 999): string
    {
        global $argv;
        $r = ($includePHP) ? 'php ' : '';
        //$r.=($baseUrl)?basename(@$_SERVER['SCRIPT_FILENAME']):@$_SERVER['SCRIPT_FILENAME'];
        $tmps = array_slice($argv, 0, $trimArguments);
        $r .= implode(' ', $tmps);
        return $r;
    }

    /**
     * @return string
     */
    public function getPhpOriginalFile(): string
    {
        return $this->phpOriginalFile;
    }

    /**
     * @param string $phpOriginalFile
     * @return CliOne
     */
    public function setPhpOriginalFile(string $phpOriginalFile): CliOne
    {
        $this->phpOriginalFile = $phpOriginalFile;
        return $this;
    }

    /**
     * It sets the color in the stack
     * @param array $colors =['red','yellow','green','white','blue','black',cyan','magenta'][$i]
     * @return $this
     */
    public function setColor(array $colors): self
    {
        $this->colorStack = $colors;
        return $this;
    }

    /**
     * It sets the value or value-key of a parameter manually.<br/>
     * It also marks the origin of the argument as "set" and it markes the argument as missing=false
     *
     * @param string $key              the key of the parameter
     * @param mixed  $value            the value (or the value-key) to assign.
     * @param bool   $isValueKey       if <b>false</b> (default) then the argument $value is the value of the
     *                                 parameter<br/> if <b>true</b> then the argument $value is the value-key.
     * @param bool   $createIfNotExist If true and the parameter doesn't exist, then it is created with the default
     *                                 configuration.
     * @return CliOneParam true if the parameter is set, otherwise false
     * @throws RuntimeException
     */
    public function setParam(string $key, $value, bool $isValueKey = false, bool $createIfNotExist = false): CliOneParam
    {
        foreach ($this->parameters as $parameter) {
            if ($parameter->key === $key) {
                if (!$isValueKey) {
                    $parameter->setValue($value);
                } else {
                    $parameter->setValue(null, $value);
                }
                return $parameter;
            }
        }
        if ($createIfNotExist) {
            $this->createParam($key, [], 'none')->add(true);
            $p = $this->getParameter($key);
            if ($isValueKey) {
                $p->setValue(null, $value);
            } else {
                $p->setValue($value);
            }
            return $p;
        }
        throw new RuntimeException("Parameter [$key] does not exist");
        //return new CliOneParam($this, null);
    }

    /**
     * Set the values of the parameters using an array.<br/>
     * If the parameter does not exist, then it is created with the default values
     * @param array|null $assocArray An associative array of the form ['key'=>'value']
     * @param array|null $fields     if null, then is set all values of the array<br/>
     *                               If not null, then it is used to determine which fields will be used
     * @return void
     */
    public function setParamUsingArray(?array $assocArray, ?array $fields = null): void
    {
        if ($assocArray === null) {
            return;
        }
        foreach ($assocArray as $k => $v) {
            if ($fields === null || in_array($k, $fields, true)) {
                $this->setParam($k, $v, false, true);
            }
        }
    }

    public function createContainer($width, $height): CliOneContainer
    {
        return new CliOneContainer([], $width, $height, 'container', null);
    }

    /**
     * It gets the values of the parameters are an associative array.
     * @param array|null $fields       If the fields are null then it returns all parameters, including "none".
     * @param bool       $asAssocArray (def:true) if true then it returns the values as an associative array<br>
     *                                 if false, then it returns as an indexed array.
     * @return array
     */
    public function getValueAsArray(?array $fields = null, bool $asAssocArray = true): array
    {
        $result = [];
        if ($fields === null) {
            foreach ($this->parameters as $v) {
                if ($asAssocArray) {
                    $result[$v->key] = $this->getValue($v->key);
                } else {
                    $result[] = $this->getValue($v->key);
                }
            }
        } else {
            foreach ($fields as $field) {
                if ($asAssocArray) {
                    $result[$field] = $this->getValue($field);
                } else {
                    $result[] = $this->getValue($field);
                }
            }
        }
        return $result;
    }

    /**
     * It sets the pattern used for the title. This operation is used in a stack.
     * {value} {type}
     * @param ?string $pattern1Stack if null then it will use the default value.
     * @return $this
     */
    public function setPatternTitle(?string $pattern1Stack = null): CliOne
    {
        $this->patternTitleStack = $pattern1Stack;
        return $this;
    }

    /**
     * <bold>{value}{type}</bold>
     * @param ?string $pattern2Stack if null then it will use the default value.
     * @return $this
     */
    public function setPatternCurrent(?string $pattern2Stack = null): CliOne
    {
        $this->patternCurrentStack = $pattern2Stack;
        return $this;
    }

    /**
     * ">"
     * @param ?string $pattern3Stack if null then it will use the default value.
     * @return $this
     */
    public function setPatternSeparator(?string $pattern3Stack = null): CliOne
    {
        $this->patternSeparatorStack = $pattern3Stack;
        return $this;
    }

    /**
     * Not used yet.
     * @param ?string $pattern4Stack if null then it will use the default value.
     * @return $this
     * @noinspection PhpUnused
     */
    public function setPatternContent(?string $pattern4Stack = null): CliOne
    {
        $this->patternContentStack = $pattern4Stack;
        return $this;
    }

    /**
     * It sets the styles used by different elements
     * @param string       $style            =['mysql','simple','double','minimal','style'][$i]
     * @param string|array $waitingIconStyle =['triangle','braille','pipe','braille2','bar','bar2','bar3','arc','waiting'][$i]
     *                                       <br>if is an array, then it uses the elements of array to show the waiting
     *                                       icon
     * @return $this
     */
    public function setStyle(string $style = 'simple', $waitingIconStyle = 'line'): self
    {
        $this->styleStack = $style;
        $this->styleIconStack = $waitingIconStyle;
        return $this;
    }

    /**
     * It's similar to showLine, but it keeps in the current line.
     *
     * @param string  $content
     * @param ?string $stream =['stdout','stderr','memory'][$i]
     * @return CliOne
     * @see CliOne::showLine
     */
    public function show(string $content, ?string $stream = null): CliOne
    {
        switch ($stream ?? $this->defaultStream) {
            case 'stderr':
                $str = STDERR;
                break;
            case 'memory':
                $str = $this->MEMORY;
                break;
            default:
                $str = STDOUT;
                break;
        }
        $r = $this->colorText($content);
        if ($this->echo) {
            fwrite($str, $r);
        } else {
            fwrite($this->MEMORY, $r);
        }
        return $this;
    }

    /**
     * It shows a breadcrumb.<br/>
     * To add values you could use the method uplevel()<br/>
     * To remove a value (going down a level) you could use the method downlevel()<br/>
     * You can also change the style using setPattern1(),setPattern2(),setPattern3()<br/>
     * ```php
     * $cli->setPattern1('{value}{type}') // the level
     *      ->setPattern2('<bred>{value}</bred>{type}') // the current level
     *      ->setPattern3(' -> ') // the separator
     *      ->showBread();
     * ```
     * It shows the current BreadCrumb if any.
     * @param bool $showIfEmpty if true then it shows the breadcrumb even if it is empty (empty line)<br/>
     *                          if false (default) then it doesn't show the breadcrumb if it is empty.
     * @return $this
     */
    public function showBread(bool $showIfEmpty = false): CliOne
    {
        $this->initStack();
        if ($showIfEmpty === false && count($this->bread) === 0) {
            $this->resetStack();
            return $this;
        }
        $txt = [];
        $patternNormal = $this->patternTitleStack ?: '{value}{type}';
        $patternCurrent = $this->patternCurrentStack ?: '<bold>{value}{type}</bold>';
        $patternSeparator = $this->patternSeparatorStack ?: ' > ';
        foreach ($this->bread as $k => $v) {
            if ($v[1]) {
                [$value, $type] = $v;
            } else {
                $value = $v[0];
                $type = '';
            }
            if ($k === count($this->bread) - 1) {
                $txt[] = str_replace(['{value}', '{type}'], [$value, $type], $patternCurrent);
            } else {
                $txt[] = str_replace(['{value}', '{type}'], [$value, $type], $patternNormal);
            }
        }
        $txt = implode($patternSeparator, $txt);
        $content = "\n$txt";
        $this->show($content);
        $this->resetStack();
        $this->showLine();
        return $this;
    }

    /**
     * It shows a label messages in a single line, example: <color>[ERROR]</color> Error message
     * @param string|array $label
     * @param string       $color  =['red','yellow','green','white','blue','black',cyan','magenta'][$i]
     * @param string|array $content
     * @param string       $stream =['stdout','stderr','memory'][$i]
     * @return void
     */
    public function showCheck($label, string $color, $content, string $stream = 'stdout'): void
    {
        $label = !is_array($label) ? ['[' . $label . ']'] : $label;
        $content = !is_array($content) ? [$content] : $content;
        $numLines = max(count($label), count($content));
        $label = $this->alignLinesVertically($label, $numLines);
        $content = $this->alignLinesVertically($content, $numLines);
        $maxWidth = $this->maxWidth($label);
        $label = $this->alignText($label, $maxWidth, 'middle');
        $label = !is_array($label) ? [$label] : $label;
        foreach ($label as $k => $l) {
            $r = $this->colorText("<$color>$l</$color> " . $content[$k]) . "\n";
            $this->show($r, $stream);
        }
    }

    /**
     * It shows a border frame.
     *
     * @param string|string[]      $lines  the content.
     * @param string|string[]|null $titles if null then no title.
     * @return void
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function showFrame($lines, $titles = null): void
    {
        $this->initstack();
        $styleFrame = $this->styleStack;
        [$cutl, $cutt, $cutr, $cutd, $cutm] = $this->borderCut($styleFrame);
        [$alignTitle, $alignContent, $alignContentNumeric] = $this->alignStack;
        [$ul, $um, $ur, $ml, $mm, $mr, $dl, $dm, $dr, $mmv] = $this->border($styleFrame);
        $contentw = $this->colSize - $this->strlen($ml) - $this->strlen($mr);
        if (is_string($lines)) {
            // transform into array
            $lines = [$lines];
        }
        if (is_string($titles)) {
            // transform into array
            $titles = [$titles];
        }
        if ($ul) {
            $this->showLine($ul . str_repeat($um, $contentw) . $ur);
        }
        if ($titles) {
            foreach ($titles as $line) {
                $this->showLine($ml . $this->alignText($line, $contentw, $alignTitle) . $mr);
            }
            $this->showLine($cutl . str_repeat($mm, $contentw) . $cutr);
        }
        foreach ($lines as $k => $line) {
            $this->show($ml . $this->alignText($line, $contentw, $alignContent) . $mr);
            if ($k !== count($lines) - 1) {
                $this->showLine();
            }
        }
        if ($dl) {
            $this->showLine();
            $this->show($dl . str_repeat($dm, $contentw) . $dr);
        }
        $this->resetStack();
        $this->showLine();
    }

    /**
     * It shows (echo) a colored line. The syntax of the color is similar to html as follows:<br/>
     * ```php
     * &lt;red&gt;error&lt;/red&gt; (color red)
     * &lt;yellow&gt;warning&lt;/yellow&gt; (color yellow)
     * &lt;blue&gt;information&lt;/blue&gt; (blue)
     * &lt;yellow&gt;yellow&lt;/yellow&gt; (yellow)
     * &lt;green&gt;green&lt;/green&gt;  (color green)
     * &lt;italic&gt;italic&lt;/italic&gt;
     * &lt;bold&gt;bold&lt;/bold&gt;
     * &lt;dim&gt;dim&lt;/dim&gt;
     * &lt;underline&gt;underline&lt;/underline&gt;
     * &lt;strikethrough&gt;strikethrough&lt;/strikethrough&gt;
     * &lt;cyan&gt;cyan&lt;/cyan&gt; (color light cyan)
     * &lt;magenta&gt;magenta&lt;/magenta&gt; (color magenta)
     * &lt;col0/&gt;&lt;col1/&gt;&lt;col2/&gt;&lt;col3/&gt;&lt;col4/&gt;&lt;col5/&gt;  columns. col0=0
     * (left),col1--col5 every column of the page.
     * &lt;option/&gt; it shows all the options available (if the input has some options)
     * ```
     *
     *
     * @param string|string[] $content content to display
     * @param ?CliOneParam    $cliOneParam
     * @param ?string         $stream  =['stdout','stderr','memory'][$i]
     * @return void
     */
    public function showLine($content = '', ?CliOneParam $cliOneParam = null, ?string $stream = null): void
    {
        $content = !is_array($content) ? [$content] : $content;
        foreach ($content as $c) {
            $r = $this->colorText($c, $cliOneParam) . "\n";
            $this->show($r, $stream ?? $this->defaultStream);
        }
    }

    /**
     * It shows a message box consisting of two columns.
     * @param string|string[] $lines     (right side)
     * @param string|string[] $titles    (left side)
     * @param bool            $wrapLines if true, then $lines could be wrapped (if the lines are too long)
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function showMessageBox($lines, $titles = [], bool $wrapLines = false): void
    {
        $this->initstack();
        $patternTitle = $this->patternTitleStack ?? '{value}';
        $patternContent = $this->patternContentStack ?? '{value}';
        $style = $this->styleStack;
        [$ul, $um, $ur, $ml, $mm, $mr, $dl, $dm, $dr, $mmv] = $this->border($style);
        [$alignTitle, $alignContent, $alignContentNumeric] = $this->alignStack;
        // message box
        [$cutl, $cutt, $cutr, $cutd, $cutm] = $this->borderCut($style);
        $contentw = $this->colSize - $this->strlen($ml) - $this->strlen($mr);
        if (is_string($lines)) {
            // transform into array
            if (strpos($lines, "\n") !== false) {
                $lines = explode("\n", $lines);
            } else {
                $lines = [$lines];
            }
        }
        if (is_string($titles)) {
            // transform into array
            if (strpos($titles, "\n") !== false) {
                $titles = explode("\n", $titles);
            } else {
                $titles = [$titles];
            }
        }
        $maxTitleL = 0;
        // max title width
        foreach ($titles as &$title) {
            $title = $this->colorText($title);
            $maxTitleL = ($this->strlen($title) > $maxTitleL) ? $this->strlen($title) : $maxTitleL;
        }
        unset($title);
        if ($wrapLines) {
            $lines = $this->wrapLine($lines, $contentw - $maxTitleL - 1);
        }
        if (count($titles) > count($lines)) {
            $lines = $this->alignLinesVertically($lines, count($titles));
        }
        if (count($titles) < count($lines)) {
            // align to the center by adding the missing lines at the top and bottom.
            $titles = $this->alignLinesVertically($titles, count($lines));
        }
        $this->showLine($ul . str_repeat($um, $maxTitleL) . $cutt . str_repeat($um, $contentw - $maxTitleL - 1) . $ur);
        foreach ($lines as $k => $line) {
            $ttitle = str_replace(['{value}'],
                $this->alignText($titles[$k], $maxTitleL, $alignTitle),
                $patternTitle);
            $tline = str_replace(['{value}'],
                $this->alignText($line, $contentw - $maxTitleL - 1, $alignContent),
                $patternContent);
            $this->showLine($ml . $ttitle . $mmv . $tline . $mr);
        }
        $this->show($dl . str_repeat($um, $maxTitleL) . $cutd . str_repeat($um, $contentw - $maxTitleL - 1) . $dr);
        $this->resetStack();
        $this->showLine();
    }

    /**
     * @param string[] $lines       The lines to align
     * @param int      $numberLines the number of lines vertically to use to align the text.
     * @param string   $align       =['middle','top','bottom'][$i]
     * @return array
     */
    public function alignLinesVertically(array $lines, int $numberLines, string $align = 'middle'): array
    {
        $dif = $numberLines - count($lines);
        $tmp = [];
        switch ($align) {
            case 'top':
                $dtop = 0;
                $dbottom = $dif;
                break;
            case 'bottom':
                $dtop = $dif;
                $dbottom = 0;
                break;
            default:
            case 'middle':
                $dtop = floor($dif / 2);
                $dbottom = ceil($dif / 2);
                break;
        }
        for ($i = 0;
             $i < $dtop;
             $i++) {
            $tmp[] = '';
        }
        foreach ($lines as $line) {
            $tmp[] = $line;
        }
        for ($i = 0; $i < $dbottom; $i++) {
            $tmp[] = '';
        }
        return $tmp;
    }

    public function maxWidth(array $lines)
    {
        $maxWidth = 0;
        foreach ($lines as $l) {
            $l = $this->strlen($l);
            if ($l > $maxWidth) {
                $maxWidth = $l;
            }
        }
        return $maxWidth;
    }

    /**
     * It shows the syntax of a parameter.
     * @param string $key        the key to show. "*" means all keys.
     * @param int    $tab        the first separation. Values are between 0 and 5.
     * @param int    $tab2       the second separation. Values are between 0 and 5.
     * @param array  $excludeKey the keys to exclude. It must be an indexed array with the keys to skip.
     * @return void
     * @throws JsonException
     */
    public function showParamSyntax(string $key, int $tab = 0, int $tab2 = 1, array $excludeKey = []): void
    {
        if ($key === '*') {
            foreach ($this->parameters as $parameter) {
                if (($parameter->type !== 'none') && !in_array($parameter->key, $excludeKey, true)) {
                    $this->showParamSyntax($parameter->key, $tab, $tab2);
                }
            }
            $this->errorType = 'show';
            return;
        }
        $parameter = $this->getParameter($key);
        /** @noinspection PhpUnusedLocalVariableInspection */
        [$paramprefix, $paramprefixalias, $position] = $this->prefixByType($parameter->type);
        if (!$parameter->isValid()) {
            $this->throwError("Parameter $key not defined");
            $this->errorType = 'show';
            return;
        }
        $v = $this->showParamValue($parameter);
        /** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
        $this->showLine("<col$tab/><green>$paramprefix{$parameter->key}</green><col{$tab2}/>{$parameter->description} <bold><cyan>[$v]</cyan></bold>");
        if (count($parameter->alias) > 0) {
            $this->showLine("<col$tab2/>Alias: <green>$paramprefixalias" . implode(' -', $parameter->alias) . '</green>', $parameter);
        }
        foreach ($parameter->getHelpSyntax() as $help) {
            $this->showLine("<col$tab2/>$help", $parameter);
        }
        $this->errorType = 'show';
    }

    /**
     * It shows the syntax of the parameters.
     * @param ?string     $title       A title (optional)
     * @param array       $typeParam   =['command','first','last','second','flag','longflag','onlyinput','none'][$i] the
     *                                 type of parameter
     * @param array       $excludeKey  the keys to exclude
     * @param array|null  $includeKeys the whitelist of elements that only could be included.<br/>
     *                                 Only keys that are in this list are added.
     * @param string|null $related     if not null then it only shows all the parameteres that are related.<br/>
     *                                 use $param->setRelated() to set the relation.
     * @param ?int        $size        the minimum size of the first column
     * @return void
     * @throws JsonException
     */
    public function showParamSyntax2(?string $title = '',
                                     array   $typeParam = ['flag', 'longflag', 'first', 'command'],
                                     array   $excludeKey = [],
                                     ?array  $includeKeys = null,
                                     ?string $related = null,
                                     ?int    $size = 20): void
    {
        $col1Corrected = [];
        $col2Corrected = [];
        if ($title) {
            $this->showLine("<yellow>$title</yellow>");
        }
        foreach ($this->parameters as $parameter) {
            $col1 = [];
            $col2 = [];
            if (in_array($parameter->type, $typeParam, true) &&
                !in_array($parameter->key, $excludeKey, true) && $parameter->isValid() &&
                ($includeKeys === null || in_array($parameter->key, $includeKeys, true)) &&
                ($related === null || in_array($related, $parameter->getRelated(), true))
            ) {
                /** @noinspection PhpUnusedLocalVariableInspection */
                [$paramprefix, $paramprefixalias, $position] = $this->prefixByType($parameter->type);
                $v = $this->showParamValue($parameter);
                $key = $paramprefix . $parameter->key;
                if ($parameter->getNameArg()) {
                    if ($parameter->required) {
                        $key .= ' <cyan><' . $parameter->getNameArg() . '</cyan><green> ';
                    } else {
                        $key .= ' <cyan><' . $parameter->getNameArg() . '></cyan><green> ';
                    }
                }
                if (count($parameter->alias) > 0) {
                    $key .= ', ';
                    $key .= $paramprefixalias . implode(', ' . $paramprefixalias, $parameter->alias);
                }
                $col1[] = $this->colorText("<green>$key</green>");
                $col2[] = $this->colorText("$parameter->description <bold><cyan>[$v]</cyan></bold>");
                if ($parameter->inputValue !== null) {
                    $assoc = array_keys($parameter->inputValue) !== range(0, count($parameter->inputValue) - 1);
                    if (!$assoc) {
                        $options = implode(',', $parameter->inputValue);
                        foreach ($parameter->getHelpSyntax() as $help) {
                            $help = str_replace('<option/>', $options, $this->colorText($help, $parameter));
                            $col1[] = '';
                            $col2[] = $help;
                        }
                    } else {
                        foreach ($parameter->getHelpSyntax() as $help) {
                            $foundOptions = strpos($help, '<option/>') !== false;
                            if (!$foundOptions) {
                                $col1[] = '';
                                $col2[] = $this->colorText($help, $parameter);
                            } else {
                                foreach ($parameter->inputValue as $k => $v) {
                                    $helpTmp = str_replace(['<optionkey/>', '<option/>'], [$k, $v], $help);
                                    $col1[] = '';
                                    $col2[] = $this->colorText($helpTmp);
                                }
                            }
                        }
                    }
                } else {
                    foreach ($parameter->getHelpSyntax() as $help) {
                        $col1[] = '';
                        $col2[] = $this->colorText($help);
                    }
                }
            }
            $col1 = $this->wrapLine($col1, $size - 4, true); // -4 is for the left alignment
            $col2 = $this->wrapLine($col2, $this->colSize - $size - 2, true); // -1 is for the spacing in between.
            $max = max(count($col1), count($col2));
            $col1 = $this->alignLinesVertically($col1, $max, 'top');
            $col2 = $this->alignLinesVertically($col2, $max, 'top');
            $countCol1 = count($col1);
            for ($i = 1; $i < $countCol1; $i++) {
                $col1[$i] = '  ' . $col1[$i];
            }
            array_push($col1Corrected, ...$col1);
            array_push($col2Corrected, ...$col2);
        }
        $col2 = $col2Corrected;
        $col1 = $col1Corrected;
        foreach ($col1 as $k => $c1) {
            $c1 = $this->alignText($c1, $size, 'left');
            $this->showLine('  ' . $c1 . ' ' . $col2[$k]);
        }
    }

    /**
     * @param string $stream =['stdout','stderr','memory'][$i]
     * @return CliOne
     */
    public function setDefaultStream(string $stream): CliOne
    {
        $this->defaultStream = $stream;
        return $this;
    }

    /**
     * @param bool $noColor if <b>true</b> then it will not show colors<br/>
     *                      if <b>false</b>, then it will show the colors.
     * @return $this
     */
    public function setNoColor(bool $noColor = true): CliOne
    {
        $this->noColor = $noColor;
        return $this;
    }

    /**
     * @return bool returns if it is returning the values with colors or not
     */
    public function isNoColor(): bool
    {
        return $this->noColor;
    }

    /**
     * if true then the console is in old-cmd mode (no colors, no utf-8 characters, etc.)
     * @param bool $noANSI
     * @return $this
     */
    public function setNoANSI(bool $noANSI = true): CliOne
    {
        $this->noANSI = $noANSI;
        return $this;
    }

    /**
     * returns true if the
     * @return bool
     */
    public function isNoANSI(): bool
    {
        return $this->noANSI;
    }

    /**
     * it wraps a line and returns one or multiples lines<br/>
     * The lines wrapped does not open or close tags.
     * @param string|array $texts     The text already formatted.
     * @param int          $width     The expected width
     * @param bool         $keepStyle if true then it keeps the initial and end style tag for every new line.<br/>
     *                                if false, then it just wraps the lines.
     *
     * @return array
     * @noinspection PhpRedundantVariableDocTypeInspection
     */
    public function wrapLine($texts, int $width, bool $keepStyle = false): array
    {
        if ($texts === '') {
            return [''];
        }
        $texts = !is_array($texts) ? [$texts] : $texts;
        $result = [];
        $initial = '';
        foreach ($texts as $text) {
            $masked = $this->colorMask($text);
            if ($keepStyle) {
                $this->initialEndStyle($text, $initial, $end);
                if ($initial === '' || $end === '') {
                    $initial = '';
                    $end = '';
                }
            } else {
                $initial = '';
                $end = '';
            }
            $textLen = strlen($text);
            $counter = 0;
            $position0 = 0;
            /** @var int $positionSpace we store the position of the last space (or other character) */
            $positionSpace = 0;
            $positionSpace2 = 0;
            $spacePattern = ' /.,'; // values we will use to cut the line
            $firstElem = true;
            for ($i = 0; $i < $textLen; $i++) {
                if (strpos($spacePattern, $masked[$i]) !== false) {
                    $positionSpace = $i;
                    $positionSpace2 = 0;
                }
                if ($masked[$i] !== chr(250)) {
                    $counter++;
                    $positionSpace2++;
                    if ($counter >= $width) {
                        $tmp = $firstElem ? '' : $initial;
                        $firstElem = false;
                        $tmp .= trim(substr($text, $position0, $positionSpace - $position0)) . $end;
                        $result[] = $tmp;
                        $position0 = $positionSpace;
                        $counter = $positionSpace2;
                    }
                }
            }
            if ($position0 !== $textLen - 1) {
                // wrap the last line
                $result[] = ($firstElem ? '' : $initial) . trim(substr($text, $position0));
            }
        }
        return $result;
    }

    /**
     * @param numeric $currentValue         the current value
     * @param numeric $max                  the max value to fill the bar.
     * @param int     $columnWidth          the size of the bar (in columns)
     * @param ?string $currentValueText     the current value to display at the left.<br/>
     *                                      if null then it will show the current value (with a space in between)
     * @return void
     */
    public function showProgressBar($currentValue, $max, int $columnWidth, ?string $currentValueText = null): void
    {
        if ($this->noANSI) {
            // progress bar is not compatible in old-cmd mode.
            return;
        }
        $this->initstack();
        $style = $this->styleStack;
        // [$alignTitle, $alignContentText, $alignContentNumber] = $this->alignStack;
        $bf = $this->shadow($style, 'full');
        $bl = $this->shadow($style, 'light');
        $prop = $columnWidth / $max;
        $currentValueText = $currentValueText ?? ' ' . $currentValue;
        $this->show(str_repeat($bf, floor($currentValue * $prop)) .
            str_repeat($bl, floor($max * $prop) - floor($currentValue * $prop)) .
            $currentValueText . "\e[" . (floor($max * $prop) + $this->strlen($currentValueText)) . "D");
        $this->resetStack();
    }

    /** it gets the size of the page (number of rows) to display in a table */
    public function getPageSize(int $reduceRows = 8): int
    {
        return $this->rowSize - $reduceRows;
    }

    /**
     * It shows an associative array.  This command is the end of a stack.
     * @param array $assocArray An associative array with the values to show. The key is used for the index.
     * @param bool  $notop      if true then it will not show the top border
     * @param bool  $nosides    if true then it will not show the side borders
     * @param bool  $nobottom   if true then it will not show the bottom border
     * @param int   $maxColumns The max number of columns to show.<br/>
     *                          If the table has 15 columns and maxColumns is 5, then only the
     *                          first 5 columns will be displayed.
     * @param int   $reduceRows The number of rows to reduce considering the size of the screen.<br/>
     *                          If the screen has 30 rows, then the table will use 30-3=27 rows<br/>
     *                          If set to >-99999, then it will display all rows.
     * @param int   $curpage    The number of page (base 1) to display.
     * @return void
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function showTable(array $assocArray,
                              bool  $notop = false,
                              bool  $nosides = false,
                              bool  $nobottom = false,
                              int   $maxColumns = 5,
                              int   $reduceRows = 3,
                              int   $curpage = 1): void
    {
        $this->initstack();
        $style = $this->styleStack;
        [$alignTitle, $alignContentText, $alignContentNumber] = $this->alignStack;
        [$ul, $um, $ur, $ml, $mm, $mr, $dl, $dm, $dr, $mmv] = $this->border($style);
        [$cutl, $cutt, $cutr, $cutd, $cutm] = $this->borderCut($style);
        if (count($assocArray) === 0) {
            return;
        }
        if ($nosides) {
            $ml = '';
            $mr = '';
            $cutl = '';
            $cutr = '';
            $dl = '';
            $dr = '';
        }
        $contentw = $this->colSize - $this->strlen($ml) - $this->strlen($mr);
        $columns = array_keys($assocArray[0]);
        $maxColumnSize = [];
        foreach ($columns as $numCol => $column) {
            if ($numCol >= $maxColumns) {
                unset($columns[$numCol]);
            }
        }
        // initialize the data
        foreach ($columns as $column) {
            $maxColumnSize[$column] = 0;
        }
        // $maxColumnSize indicates the maximum size (according to the size of the content)
        foreach ($assocArray as $row) {
            foreach ($columns as $column) {
                if ($this->strlen($row[$column]) > $maxColumnSize[$column]) {
                    $maxColumnSize[$column] = $this->strlen($row[$column]);
                }
            }
        }
        // we also include the title of the column
        foreach ($columns as $column) {
            if ($maxColumnSize[$column] < strlen($column)) {
                $maxColumnSize[$column] = strlen($column);
            }
        }
        $contentwCorrected = $contentw - count($columns) + 1;
        $totalCol = array_sum($maxColumnSize);
        foreach ($columns as $column) {
            $maxColumnSize[$column] = (int)round($maxColumnSize[$column] * $contentwCorrected / $totalCol);
            if ($maxColumnSize[$column] <= 2) {
                $maxColumnSize[$column] = 3;
            }
        }
        if (array_sum($maxColumnSize) > $contentwCorrected) {
            // we correct the precision error of round by removing 1 to the first column that is bigger than 3
            foreach ($columns as $column) {
                if ($maxColumnSize[$column] > 3) {
                    $maxColumnSize[$column]--;
                    break;
                }
            }
        }
        if (array_sum($maxColumnSize) < $contentwCorrected) {
            // we correct the precision error of round by removing 1 to the first column
            $maxColumnSize[$columns[0]]++;
        }
        $curRow = 0;
        // top
        if ($ul && $notop === false) {
            $txt = $ul;
            foreach ($maxColumnSize as $size) {
                $txt .= str_repeat($um, $size) . $cutt;
            }
            $txt = $this->removechar($txt, $this->strlen($cutt, false)) . $ur;
            $curRow++;
            $this->showLine($txt);
        }
        // title
        $txt = $ml;
        foreach ($maxColumnSize as $colName => $size) {
            $txt .= $this->alignText($colName, $size, $alignTitle) . $mmv;
        }
        $txt = $this->removechar($txt, $this->strlen($mmv, false)) . $mr;
        $curRow++;
        $this->showLine($txt);
        // botton title
        $txt = $cutl;
        foreach ($maxColumnSize as $size) {
            $txt .= str_repeat($mm, $size) . $cutm;
        }
        $txt = rtrim($txt, $cutm) . $cutr;
        $curRow++;
        $this->showLine($txt);
        $pageSize = $this->getPageSize();
        $rowSwift = $pageSize * ($curpage - 1);
        $totalPage = ceil(count($assocArray) / $pageSize);
        // content
        foreach ($assocArray as $k => $line) {
            $txt = $ml;
            $lineDisplay = @$assocArray[$k + $rowSwift];
            if ($lineDisplay) {
                foreach ($maxColumnSize as $colName => $size) {
                    if ($k > $this->rowSize - $curRow - $reduceRows - 3) {
                        $lineDisplay[$colName] = '...';
                    }
                    $lineDisplay[$colName] = $lineDisplay[$colName] ?? '(null)';
                    $txt .= $this->alignText(
                            $lineDisplay[$colName],
                            $size,
                            is_numeric($lineDisplay[$colName]) ? $alignContentNumber : $alignContentText) . $mmv;
                }
            }
            $txt = rtrim($txt, $mmv) . $mr;
            if (!$lineDisplay || $k > $this->rowSize - $curRow - $reduceRows - 3 || $k === count($assocArray) - 1) {
                // last line
                // if($lineDisplay) {
                $this->show($txt);
                break;
            }
            $this->showLine($txt);
        }
        // botton table
        if (($dl || $dm) && $nobottom === false) {
            $this->showLine();
            $txt = $dl;
            $count = "Page #$curpage of $totalPage";
            foreach ($maxColumnSize as $size) {
                $txt .= str_repeat($dm, $size) . $cutd;
            }
            $txt = rtrim($txt, $cutd) . $dr;
            $txt = substr_replace($txt, $count, strlen($txt) - (strlen($count) * 3) - 6, strlen($count) * 3);
            $this->show($txt);
        }
        $this->resetStack();
        $this->showLine();
    }

    /**
     * It shows the values as columns.
     * @param array   $values        the values to show. It could be an associative array or an indexed array.
     * @param string  $type          ['multiple','multiple2','multiple3','multiple4','option','option2','option3','option4'][$i]
     * @param ?string $patternColumn the pattern to be used, example: "<cyan>[{key}]</cyan> {value}"
     * @return void
     * @throws JsonException
     */
    public function showValuesColumn(array $values, string $type, ?string $patternColumn = null): void
    {
        $p = new CliOneParam('dummy', false, null, null);
        $p->setPattern($patternColumn);
        $p->inputValue = $values;
        $p->inputType = $type;
        $this->internalShowOptions($p, []);
    }

    /**
     * It shows a waiting cursor.<br>
     * <b>Example:</b><br/>
     * ```php
     * $this->hideCursor()->showWaitCursor(true);
     * $this->showWaitCursor(); // inside a loop.
     * $this->hideWaitCursor()->showCursor(); // at the end of the loop
     * ```
     * @param bool   $init               the first time this method is called, you must set this value as true. Then,
     *                                   every update must be false.
     * @param string $postfixValue       if you want to set a profix value such as percentage, advance, etc.
     * @return CliOne
     */
    public function showWaitCursor(bool $init = true, string $postfixValue = ''): CliOne
    {
        if ($this->noANSI) {
            // progress bar is not compatible in old-cmd mode.
            return $this;
        }
        if ($init) {
            $this->wait = 0;
        }
        $this->wait++;
        switch ($this->styleIconStack) {
            case 'arc':
                $styleItem = ["◜", "◠", "◝", "◞", "◡", "◟"];
                break;
            case 'bar3':
                $styleItem=["▰▱▱▱▱▱▱", "▰▰▱▱▱▱▱", "▰▰▰▱▱▱▱", "▰▰▰▰▱▱▱", "▰▰▰▰▰▱▱",
                    "▰▰▰▰▰▰▱", "▰▰▰▰▰▰▰",];
                break;
            case 'waiting':
                $styleItem = ['wait...', 'wait.. ', 'wait.  ', 'wait   ', 'wait.  ', 'wait.. ', 'wait...'];
                break;
            case 'pipe':
                $styleItem = ['┤', '┘', '┴', '└', '├', '┌', '┬', '┐'];
                break;
            case 'bar':
                $styleItem = ['▁', '▂', '▃', '▄', '▅', '▆', '▇', '█', '▇', '▆', '▅', '▄', '▃', '▁'];
                break;
            case 'bar2':
                $styleItem = ['▉', '▊', '▋', '▌', '▍', '▎', '▏', '▎', '▍', '▌', '▋', '▊', '▉'];
                break;
            case 'braille2':
                $styleItem = ['⠁', '⠂', '⠄', '⡀', '⢀', '⠠', '⠐', '⠈'];
                break;
            case 'triangle':
                $styleItem = ['◢', '◣', '◤', '◥'];
                break;
            case 'braille':
                $styleItem = ['⣾', '⣽', '⣻', '⢿', '⡿', '⣟', '⣯', '⣷'];
                break;
            default:
                $styleItem = ['|', '/', '-', '\\'];
        }
        if ($this->wait >= count($styleItem)) {
            $this->wait = 0;
        }
        $c = $styleItem[$this->wait];
        $this->waitSize = $this->strlen($c);
        if ($init) {
            $this->show($c . $postfixValue);
        } else {
            $this->show("\e[" . ($this->strlen($this->waitPrev) + $this->waitSize) . "D" . $c . $postfixValue); // [2D 2 left, [C 1 right
        }
        $this->waitPrev = $postfixValue;
        return $this;
    }

    public function hideWaitCursor(bool $init = false): CliOne
    {
        if ($init) {
            $this->show(' ');
        } else {
            $this->show("\e[" . ($this->strlen($this->waitPrev) + $this->waitSize) . "D" . str_repeat(' ', $this->waitSize)); // [2D 2 left, [C 1 right
        }
        return $this;
    }

    public function hideCursor(): CliOne
    {
        if($this->noANSI) {
            return $this;
        }
        $this->show("\e[?25l");
        return $this;
    }

    public function showCursor(): CliOne
    {
        if($this->noANSI) {
            return $this;
        }
        $this->show("\e[?25h");
        return $this;
    }

    /**
     * It will show all the parameters by showing the key, the default value and the value<br/>
     * It is used for debugging and testing.
     * @return void
     */
    public function showparams(): void
    {
        foreach ($this->parameters as $parameter) {
            try {
                $this->showLine("$parameter->key = [" .
                    json_encode($parameter->default, JSON_THROW_ON_ERROR) . "] value:" .
                    $this->showParamValue($parameter));
            } catch (Exception $e) {
            }
        }
    }

    /**
     * @param CliOneParam $parameter
     * @return string
     * @throws JsonException
     */
    public function showParamValue(CliOneParam $parameter): string
    {
        if ($parameter->inputType === 'password') {
            return '*****';
        }
        if ($parameter->value === null) {
            return '(null)';
        }
        if (is_array($parameter->value)) {
            return json_encode($parameter->value, JSON_THROW_ON_ERROR);
        }
        return $parameter->value;
    }

    /**
     * It determines the size of a string
     * @param      $content
     * @param bool $visual visual means that it considers the visual lenght, false means it considers characters.
     * @return false|int
     */
    public function strlen($content, bool $visual = true)
    {
        $contentClear = $this->colorLess($content);
        if ($this->multibyte && $visual) {
            /** @noinspection PhpComposerExtensionStubsInspection */
            return mb_strlen($contentClear);
        }
        return strlen($contentClear);
    }

    /**
     * remove visible characters at the end of the string. It ignores invisible (such as colors) characters.
     * @param string $content
     * @param int    $numchar
     * @return string
     */
    public function removechar(string $content, int $numchar): string
    {
        $contentMask = $this->colorMask($content);
        $l = strlen($content);
        $count = 0;
        for ($i = $l - 1; $i >= 0; $i--) {
            if ($contentMask[$i] !== chr(250)) {
                $content = substr($content, 0, $i) . substr($content, $i + 1);
                $count++;
                if ($count >= $numchar) {
                    break;
                }
            }
        }
        return $content;
    }

    /**
     * Up a level in the breadcrumb
     * @param string $content the content of the new line
     * @param string $type    the type of the content (optional)
     * @return CliOne
     */
    public function upLevel(string $content, string $type = ''): CliOne
    {
        $this->bread[] = [$content, $type];
        return $this;
    }

    /**
     * @param string|array $text
     * @param int          $width
     * @param string       $align =['left','right','middle'][$i]
     * @return string[]|string
     */
    protected function alignText($text, int $width, string $align)
    {
        $text = !is_array($text) ? [$text] : $text;
        $result = [];
        foreach ($text as $txt) {
            $len = $this->strlen($txt);
            if ($len > $width) {
                $txt = $this->ellipsis($txt, $width);
                $len = $width;
            }
            $padnum = $width - $len;
            switch ($align) {
                case 'left':
                    $result[] = $txt . str_repeat(' ', $padnum);
                    break;
                case 'right':
                    $result[] = str_repeat(' ', $padnum) . $txt;
                    break;
                case 'middle':
                    $padleft = floor($padnum / 2);
                    $padright = ceil($padnum / 2);
                    $result[] = str_repeat(' ', $padleft) . $txt . str_repeat(' ', $padright);
                    break;
                default:
                    trigger_error("align incorrect $align");
            }
        }
        // if the original text is 1 row, then it returns a string instead of a string[]
        return count($result) === 1 ? $result[0] : $result;
    }

    /**
     * With the value of the parameter, the system assign the valuekey of the parameter<br/>
     * If the parameter doesn't have inputvalues, or the value is not in the list of inputvalues, then it does nothing.
     * @param CliOneParam $parameter
     * @return void
     */
    protected function refreshParamValueKey(CliOneParam $parameter): void
    {
        $parameter->setValue($parameter->value);
    }
    //

    /**
     * ```php
     * // up left, up middle, up right, middle left, middle right, down left, down middle, down right.
     * [$ul, $um, $ur, $ml, $mm, $mr, $dl, $dm, $dr, $mmv]=$this->border();
     * ```
     * @param string $style =['mysql','simple','double','style']
     * @return string[]
     */
    protected function border(string $style): array
    {
        switch ($style) {
            case 'style':
                return $this->noANSI ? [
                    'É', 'Í', '»',
                    'º', 'Í', 'º',
                    'È', 'Í', '¼', 'º'
                ]
                    : [
                        '╒', '═', '╕',
                        '│', '═', '│',
                        '╘', '═', '╛', '│'];
            case 'mysql':
                return [
                    '+', '-', '+',
                    '|', '-', '|',
                    '+', '-', '+', '|'];
            case 'double':
                //Notepad: ┌┬┐ ├┼┤ └┴┘ ─ │
                //cmd.exe: ÚÂ¿ ÃÅ´ ÀÁÙ Ä ³
                //
                //Notepad: ╔╦╗ ╠╬╣ ╚╩╝ ═ ║
                //cmd.exe: ÉË» ÌÎ¹ ÈÊ¼ Í º
                $r = $this->noANSI ? [
                    'É', 'Í', '»',
                    'º', 'Í', 'º',
                    'È', 'Í', '¼', 'º'
                ]
                    : [
                        '╔', '═', '╗',
                        '║', '═', '║',
                        '╚', '═', '╝', '║'];
                if ($this->noANSI) {
                    foreach ($r as $k => $v) {
                        $r[$k] = iconv("UTF-8", "Windows-1252", $v);
                    }
                }
                return $r;
            case 'simple':
                //Notepad: ┌┬┐ ├┼┤ └┴┘ ─ │
                //cmd.exe: ÚÂ¿ ÃÅ´ ÀÁÙ Ä ³
                $r = $this->noANSI ? [
                    'Ú', 'Ä', '¿',
                    '³', 'Ä', '³',
                    'À', 'Ä', 'Ù', '³'
                ]
                    : [
                        '┌', '─', '┐',
                        '│', '─', '│',
                        '└', '─', '┘', '│'];
                if ($this->noANSI) {
                    foreach ($r as $k => $v) {
                        $r[$k] = iconv("UTF-8", "Windows-1252", $v);
                    }
                }
                return $r;
            case 'minimal':
                return [
                    '', '', '',
                    '', '-', '',
                    '', '', '', ' ']; // note: the last is a space
            default:
                trigger_error("style not defined $style");
        }
        return [];
    }

    /**
     * ```php
     * // cut left, cut top, cut right, cut bottom , cut middle
     * [$cutl, $cutt, $cutr, $cutd, $cutm] = $this->borderCut($style);
     * ```
     * @param string $style =['mysql','simple','double']
     * @return string[]
     */
    protected function borderCut(string $style): array
    {
        switch ($style) {
            case 'mysql':
                return ['+', '+', '+', '+', '+'];
            case 'double':
                //Notepad: ╔╦╗ ╠╬╣ ╚╩╝ ═ ║
                //cmd.exe: ÉË» ÌÎ¹ ÈÊ¼ Í º
                $r = $this->noANSI ?
                    ['Ì', 'Ë', '¹', 'Ê', 'Î'] :
                    ['╠', '╦', '╣', '╩', '╬'];
                if ($this->noANSI) {
                    foreach ($r as $k => $v) {
                        $r[$k] = iconv("UTF-8", "Windows-1252", $v);
                    }
                }
                return $r;
            case 'simple':
                //Notepad: ┌┬┐ ├┼┤ └┴┘ ─ │
                //cmd.exe: ÚÂ¿ ÃÅ´ ÀÁÙ Ä ³
                $r = $this->noANSI ?
                    ['Ã', 'Â', '´', 'Á', 'Å'] :
                    ['├', '┬', '┤', '┴', '┼'];
                if ($this->noANSI) {
                    foreach ($r as $k => $v) {
                        $r[$k] = iconv("UTF-8", "Windows-1252", $v);
                    }
                }
                return $r;
            case 'minimal':
                return ['', '', '', '', ' '];
            default:
                trigger_error("style not defined $style");
        }
        return [];
    }

    /**
     * It calculates the size of the columns in the console
     * @param int $min
     * @return false|int|string
     */
    protected function calculateColSize(int $min = 40)
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                $a1 = shell_exec('mode con');
                /*
                 * Estado para dispositivo CON:
                 * ----------------------------
                 * Líneas: 9001
                 * Columnas: 85
                 * Ritmo del teclado: 31
                 * Retardo del teclado: 1
                 * Página de códigos: 65001
                 */
                $arr = explode("\n", $a1);
                $col = trim(explode(':', $arr[4])[1]);
                if ($this->noColor) {
                    $col--;
                }
            } else {
                $col = exec('tput cols');
                /*
                 * 184
                 */
            }
        } catch (Exception $ex) {
            $col = 80;
        }
        return $col < $min ? $min : $col;
    }

    /**
     * It calculates the size of rows in the console
     * @param int $min
     * @return mixed
     */
    protected function calculateRowSize(int $min = 5)
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                //$row = shell_exec('$Host.UI.RawUI.WindowSize.height'); however it chances the screen of the shell
                $row = 30; // cmd.exe by default (modern windows) uses 120x30.
            } else {
                $row = trim(exec('tput lines'));
            }
        } catch (Exception $ex) {
            $row = 25;
        }
        return max($row, $min);
    }

    /**
     * Add an ellispis "..." to the end of a text, for example, to trip a long text.
     * @param string $text
     * @param int    $lenght
     * @return string
     */
    protected function ellipsis(string $text, int $lenght): string
    {
        $l = $this->strlen($text);
        if ($l <= $lenght) {
            return $text;
        }
        return $this->removechar($text, $l - $lenght + 3) . '...';
    }

    /**
     * Internal. it starts the stack.
     * @return void
     */
    protected function initStack(): void
    {
        foreach ($this->colorStack as $color) {
            $this->show("<$color>");
        }
    }

    /**
     * Internal. It ends the visual stack
     * @return $this
     */
    protected function resetStack(): CliOne
    {
        foreach ($this->colorStack as $color) {
            $this->show("</$color>");
        }
        $this->styleStack = 'simple';
        $this->alignStack = ['middle', 'middle', 'middle'];
        $this->colorStack = [];
        $this->patternTitleStack = null;
        $this->patternCurrentStack = null;
        $this->patternSeparatorStack = null;
        $this->patternContentStack = null;
        return $this;
    }

    /**
     * It shows the listing of options
     *
     * @param CliOneParam  $parameter
     * @param array|string $result used by multiple
     * @return void
     * @throws JsonException
     */
    protected function internalShowOptions(CliOneParam $parameter, $result): void
    {
        // pattern
        switch ($parameter->inputType) {
            case 'multiple':
                $pattern = '{selection}<bold><cyan>[{key}]</cyan></bold> {value}';
                $foot = "\t<bold><cyan>[a]</cyan></bold> select all, <bold><cyan>[n]</cyan></bold> select none, <bold><cyan>[]</cyan></bold> end selection, [*] (is marked as selected)";
                $columns = 1;
                break;
            case 'multiple2':
                $pattern = '{selection}<bold><cyan>[{key}]</cyan></bold> {value}';
                $foot = "\t<bold><cyan>[a]</cyan></bold> select all, <bold><cyan>[n]</cyan></bold> select none, <bold><cyan>[]</cyan></bold> end selection, [*] (is marked as selected)";
                $columns = 2;
                break;
            case 'multiple3':
                $pattern = '{selection}<bold><cyan>[{key}]</cyan></bold> {value}';
                $foot = "\t<bold><cyan>[a]</cyan></bold> select all, <bold><cyan>[n]</cyan></bold> select none, <bold><cyan>[]</cyan></bold> end selection, [*] (is marked as selected)";
                $columns = 3;
                break;
            case 'multiple4':
                $pattern = '{selection}<bold><cyan>[{key}]</cyan></bold> {value}';
                $foot = "\t<bold><cyan>[a]</cyan></bold> select all, <bold><cyan>[n]</cyan></bold> select none, <bold><cyan>[]</cyan></bold> end selection, [*] (is marked as selected)";
                $columns = 4;
                break;
            case 'option':
                $pattern = '<bold><cyan>[{key}]</cyan></bold> {value}';
                $foot = "";
                $columns = 1;
                break;
            case 'option2':
                $pattern = '<bold><cyan>[{key}]</cyan></bold> {value}';
                $foot = "";
                $columns = 2;
                break;
            case 'option3':
                $pattern = '<bold><cyan>[{key}]</cyan></bold> {value}';
                $foot = "";
                $columns = 3;
                break;
            case 'option4':
                $pattern = '<bold><cyan>[{key}]</cyan></bold> {value}';
                $foot = "";
                $columns = 4;
                break;
            default:
                return;
        }
        /** @noinspection PhpUnusedLocalVariableInspection */
        [$p, $pq, $f] = $parameter->getPatterColumns();
        if ($p !== null) {
            $pattern = $p;
        }
        if ($f !== null) {
            $pattern = $f;
        }
        $kvalues = [];
        $ivalues = [];
        foreach ($parameter->inputValue as $k => $v) {
            $kvalues[] = $k;
            $ivalues[] = $v;
        }
        $assoc = array_keys($parameter->inputValue) !== range(0, count($parameter->inputValue) - 1); //!isset($parameter->inputValue[0]);
        $chalf = (int)ceil(count($ivalues) / $columns);
        $colW = (int)ceil($this->colSize / $columns);
        $maxL = 0;
        $iMax = count($ivalues);
        for ($i = 0; $i < $iMax; $i++) {
            $keybase = $kvalues[$i];
            $keydisplay = $assoc ? $keybase : ($keybase + 1);
            $maxL = max($this->strlen($keydisplay), $maxL);
        }
        for ($i = 0; $i < $chalf; $i++) {
            for ($kcol = 1; $kcol < 5; $kcol++) {
                if ($kcol <= $columns) {
                    $shift = $chalf * ($kcol - 1);
                    if (array_key_exists($i + $shift, $kvalues)) {
                        $keybase = $kvalues[$i + $shift];
                        $keydisplay = $assoc ? $keybase : ($keybase + 1);
                        if ($assoc) {
                            // for padding the keys (assoc)
                            $keydisplay = $this->alignText($keydisplay, $maxL, 'middle');
                            //$keydisplay .= str_repeat(' ', $maxL - $this->strlen($keydisplay));
                        } else {
                            // for padding the keys (numeric)
                            $keydisplay = $this->alignText($keydisplay, $maxL, 'right');
                        }
                        if (strpos($parameter->inputType, 'multiple') === 0) {
                            $selection = $result[$keybase] ? "[*]" : "[ ]";
                        } else {
                            $selection = "[*]";
                        }
                        $v = $ivalues[$i + $shift];
                        $txt = $this->showPattern($parameter, $keydisplay, $v, $selection, $colW, '', $pattern);
                        $col = ($kcol - 1) * $colW;
                        if ($this->noANSI) {
                            $this->show($txt);
                        } else {
                            $this->show("\e[" . ($col) . "G" . $txt);
                        }
                    }
                }
            }
            $this->show("\n");
        }
        if ($foot) {
            $this->showLine($foot);
        }
    }

    /**
     * @param CliOneParam $parameter
     * @return array|?string
     * @throws JsonException
     */
    protected function readParameterInput(CliOneParam $parameter)
    {
        $result = '';
        if (strpos($parameter->inputType, 'multiple') === 0) {
            $multiple = true;
            $result = [];
            foreach ($parameter->inputValue as $k => $v) {
                $result[$k] = is_array($parameter->default) && isset($parameter->default[$k]);
            }
            // default is used to set the current selection
            $parameter->default = '';
        } else {
            $multiple = false;
        }
        do {
            $this->internalShowOptions($parameter, $result);
            $this->validate($parameter);
            $input = $parameter->value;
            if (strpos($parameter->inputType, 'multiple') === 0) {
                switch ($input) {
                    case $this->emptyValue . 'a':
                        foreach ($result as $k => $item) {
                            $result[$k] = true;
                        }
                        break;
                    case $this->emptyValue . 'n':
                        foreach ($result as $k => $item) {
                            $result[$k] = false;
                        }
                        break;
                    case $this->emptyValue:
                        $multiple = false;
                        $final = [];
                        foreach ($result as $k => $item) {
                            if ($item === true) {
                                $final[] = $parameter->inputValue[$k];
                            }
                        }
                        $result = $final;
                        break;
                    default:
                        $pos = array_search($input, $parameter->inputValue, true);
                        if ($pos !== false) {
                            $result[$pos] = !$result[$pos];
                        } else {
                            $this->throwError("unknow selection $input");
                        }
                }
            } else {
                $result = $input;
            }
        } while ($multiple);
        $this->errorType = 'show';
        return $result;
    }

    /**
     * It reads a line input that the user must enter the information<br/>
     * <b>Note:</b> It could be simulated using the static self::$fakeReadLine (array)
     * , where the first value must be 0, and the other values must be the input emulated
     * @param string      $content The prompt.
     * @param CliOneParam $parameter
     * @return false|mixed|string returns the user input.
     * @throws JsonException
     */
    protected function readline(string $content, CliOneParam $parameter)
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->show($content);
        } else {
            $this->show("\0337$content\0338");    // \0337 save point \0338 return save point.
            $largo = $this->strlen($content);
        }
        // globals is used for phpunit.
        if (self::$fakeReadLine !== null) {
            self::$fakeReadLine[0]++;
            if (self::$fakeReadLine[0] >= count(self::$fakeReadLine)) {
                if (self::$throwNoInput) {
                    throw new RuntimeException('Test incorrect, it is waiting for read more CliOne::$fakeReadLine ' . json_encode(self::$fakeReadLine, JSON_THROW_ON_ERROR));
                }
                self::$fakeReadLine = null; // end running ??load
            } else {
                $this->showLine('<green><underline>[' . self::$fakeReadLine[self::$fakeReadLine[0]] . ']</underline></green>');
                if ($this->debug) {
                    $this->debugHistory[] = self::$fakeReadLine[self::$fakeReadLine[0]];
                }
                return self::$fakeReadLine[self::$fakeReadLine[0]];
            }
        }
        if (is_array($parameter->inputValue) && count($parameter->inputValue) > 0) {
            $assoc = array_keys($parameter->inputValue) !== range(0, count($parameter->inputValue) - 1);
            if ($assoc) {
                if ($parameter->inputType === 'optionshort') {
                    self::$autocomplete = $parameter->inputValue;
                } else {
                    self::$autocomplete = array_keys($parameter->inputValue);
                }
            } else if ($parameter->inputType === 'optionshort') {
                self::$autocomplete = $parameter->inputValue;
            } else {
                self::$autocomplete = [];
                $iMax = count($parameter->inputValue);
                for ($i = 0; $i < $iMax; $i++) {
                    self::$autocomplete[] = $i + 1;
                }
            }
            if (strpos($parameter->inputType, 'multiple') === 0) {
                self::$autocomplete[] = 'a';
                self::$autocomplete[] = 'n';
                self::$autocomplete[] = '';
            }
        } else {
            self::$autocomplete = [$parameter->default];
        }
        if (count($parameter->getHistory()) > 0) {
            // if the parameter has its own history, then we will use it
            $prevhistory = $this->listHistory();
            $this->setHistory($parameter->getHistory());
        }
        if (PHP_OS_FAMILY === 'Windows') {
            $r = readline();
        } else {
            /** @noinspection PhpUndefinedVariableInspection */
            $r = readline(str_repeat(' ', $largo));
        }
        $r = $r === false ? false : trim($r);
        if ($this->debug) {
            $this->debugHistory[] = $r;
        }
        if (count($parameter->getHistory()) > 0) {
            // if we use a parameter history, then we return to the previous history
            /** @noinspection PhpUndefinedVariableInspection */
            $this->setHistory($prevhistory);
        }
        return $r;
    }

    /**
     * It sets the color of the cli<br/>
     * ```php
     * <red>error</red> (color red)
     * <yellow>warning</yellow> (color yellow)
     * <blue>information</blue> (blue)
     * <yellow>yellow</yellow> (yellow)
     * <green>green</green>  (color green)
     * <italic>italic</italic>
     * <bold>bold</bold>
     * <underline>underline</underline>
     * <strikethrough>strikethrough</strikethrough>
     * <cyan>cyan</cyan> (color light cyan)
     * <magenta>magenta</magenta> (color magenta)
     * <col0/><col1/><col2/><col3/><col4/><col5/>  columns. col0=0 (left),col1--col5 every column of the page.
     * <option/> it shows all the options available (if the input has some options)
     * ```
     *
     * @param string       $content
     * @param ?CliOneParam $cliOneParam
     * @return string
     */
    public function colorText(string $content, ?CliOneParam $cliOneParam = null): string
    {
        if ($cliOneParam !== null) {
            if (is_array($cliOneParam->inputValue)) {
                $v = implode('/', $cliOneParam->inputValue);
            } else {
                $v = '';
            }
            $content = str_replace('<option/>', $v, $content);
        } else {
            $content = str_replace(['<option/>', '<optionkey/>'], ['', ''], $content);
        }
        $content = $this->replaceCurlyVariable($content, true);
        return str_replace([...$this->colorTags, ...$this->styleTextTags, ...$this->columnTags],
            [...$this->noColor ?
                array_fill(0, count($this->colorTags), '')
                : $this->colorEscape, ...$this->noColor
                ? array_fill(0, count($this->styleTextEscape), '')
                : $this->styleTextEscape, ...$this->noColor ? $this->columnEscapeCmd
                : $this->columnEscape]
            , $content);
    }

    /**
     * It removes all the escape characters of a content
     * @param string $content
     * @return string
     */
    public function colorLess(string $content): string
    {
        return str_replace(
            [...$this->colorEscape, ...$this->styleTextEscape, ...$this->columnEscape],
            [...array_fill(0, count($this->colorEscape), ''),
                ...array_fill(0, count($this->styleTextEscape), ''),
                ...array_fill(0, count($this->columnEscape), '')],
            $content);
    }

    /**
     * It masks (with a char 250) all the escape characters.
     * @param $content
     * @return array|string|string[]
     */
    public function colorMask($content)
    {
        $m5 = str_repeat(chr(250), 5); // colorescape and styletextescape uses \e[00m notation
        $m6 = str_repeat(chr(250), 6); // columnEscape uses \e[000m notation
        return str_replace(
            [...$this->colorEscape, ...$this->styleTextEscape, ...$this->columnEscape],
            [...array_fill(0, count($this->colorEscape), $m5),
                ...array_fill(0, count($this->styleTextEscape), $m5),
                ...array_fill(0, count($this->columnEscape), $m6)],
            $content);
    }

    /**
     * It returns the initial and end style of a text.<br/>
     * If the text only contains an initial or final style, then nothing is returned
     *
     * @param string  $contentAnsi the content text already formatted in Ansi
     * @param ?string $initial     (this value is returned)
     * @param ?string $end         (this value is returned)
     * @return void
     */
    public function initialEndStyle(string $contentAnsi, ?string &$initial, ?string &$end): void
    {
        $mask = $this->colorMask($contentAnsi);
        $l = strlen($contentAnsi);
        $l0 = $l - strlen(ltrim($mask, chr(250)));
        $l1 = $l - strlen(rtrim($mask, chr(250)));
        $initial = substr($contentAnsi, 0, $l0);
        $sst = substr($contentAnsi, -$l1);
        $sst = ($sst === false) ? '' : $sst;
        $end = $l1 === 0 ? '' : $sst;
    }

    /**
     * [full,light,soft,double], light usually it is a space.
     * ```php
     * [$bf,$bl,$bm,$bd]=$this->shadow();
     * ```
     * @param string $style       =['mysql','simple','double','style']
     * @param string $returnValue =['full','light','soft','double'][$i]
     * @return string|array
     */
    protected function shadow(string $style = 'simple', string $returnValue = '*')
    {
        switch ($style) {
            case 'mysql':
                $r = ['#', ' ', '-', '='];
                break;
            case 'style':
                $r = $this->noColor ?
                    ['#', ' ', '-', '=']:
                    ['▰', '▱', '▱', '▰'];
                break;
            case 'simple':
                $r = $this->noColor ?
                    ['Û', ' ', '°', '²'] :
                    ['█', ' ', '░', '▓'];
                if ($this->noColor) {
                    foreach ($r as $k => $v) {
                        $r[$k] = iconv("UTF-8", "Windows-1252", $v);
                    }
                }
                break;
            case 'double':
                $r = $this->noColor ?
                    ['Û', '°', '±', '²'] :
                    ['█', '░', '▒', '▓'];
                if ($this->noColor) {
                    foreach ($r as $k => $v) {
                        $r[$k] = iconv("UTF-8", "Windows-1252", $v);
                    }
                }
                break;
            case 'minimal':
                $r = ['*', ' ', ' ', ' '];
                break;
            default:
                trigger_error("style not defined $style");
                $r = ['', '', '', ''];
        }
        switch ($returnValue) {
            case 'full':
                return $r[0];
            case 'light':
                return $r[1];
            case 'soft':
                return $r[2];
            case 'double':
                return $r[3];
            default:
            case '*':
                return $r;
        }
    }

    /**
     * It shows a pattern
     * @param CliOneParam $parameter $param
     * @param string      $key       the key to show
     * @param mixed       $value     the value to show
     * @param string      $selection the selection (used by multiple to show [*])
     * @param int         $colW      the size of the col
     * @param string      $prefix    A prefix
     * @param string      $pattern   the pattern to use.
     * @return string
     * @throws JsonException
     */
    protected function showPattern(CliOneParam $parameter, string $key, $value, string $selection, int $colW, string $prefix, string $pattern): string
    {
        $desc = $parameter->question ?: $parameter->description;
        $def = (is_array($parameter->default) ? implode(',', $parameter->default) : $parameter->default);
        if ($parameter->inputType === 'password') {
            $def = '*****';
        }
        $valueToShow = (is_object($value) || is_array($value)) ? json_encode($value, JSON_THROW_ON_ERROR) : $value;
        if (is_array($value)) {
            $valueinit = reset($value);
            $valuenext = next($value);
            $valueend = end($value);
        } else {
            $valueinit = '';
            $valuenext = '';
            $valueend = '';
        }
        // $patern='{selection} {key}{value}';
        $text = str_replace(
            ['{selection}', '{key}', '{value}', '{valueinit}', '{valuenext}', '{valueend}', '{desc}', '{def}', '{prefix}'],
            [$selection, $key, $valueToShow, $valueinit, $valuenext, $valueend, $desc, $def, $prefix], $pattern);
        $text = $this->colorText($text);
        return $this->ellipsis($text, $colW - 1);
    }

    /**
     * @param CliOneParam $parameter
     * @param bool        $askInput
     * @return bool
     * @throws JsonException
     */
    protected function validate(CliOneParam $parameter, bool $askInput = true): bool
    {
        $ok = false;
        $cause = 'no cause found';
        while (!$ok) {
            switch ($parameter->inputType) {
                case 'range':
                    $prefix = @$parameter->inputValue[0] . '-' . @$parameter->inputValue[1];
                    break;
                case 'optionshort':
                    $uniques = array_map(static function($v) {
                        return substr($v, 0, 1);
                    }, $parameter->inputValue);
                    if (count(array_unique($uniques)) !== count($uniques)) {
                        // there is some  repeated valued
                        $prefix = implode('/', $parameter->inputValue);
                    } else {
                        $values2 = array_map(static function($v) {
                            return "<underline>" . substr($v, 0, 1) . "</underline>" . substr($v, 1);
                        }, $parameter->inputValue);
                        $prefix = implode('/', $values2);
                    }
                    break;
                default:
                    $prefix = '';
            }
            if ($askInput) {
                $pattern = $parameter->getPatterColumns()['1'] ?: "{desc} <bold><cyan>[{def}]</cyan></bold> {prefix}:";
                // the 9999 is to indicate to never ellipses this input.
                $txt = $this->showPattern($parameter, $parameter->key, $this->showParamValue($parameter), '', 9999, $prefix, $pattern);
                while (true) {
                    $origInput = $this->readline($txt, $parameter);
                    if ($this->debug && strpos($origInput, '??') === 0) {
                        switch ($origInput) {
                            case strpos($origInput, '??save') === 0:
                                $part = explode(':', $origInput, 2);
                                $file = $part[1] ?? '_save';
                                array_pop($this->debugHistory);
                                $r = @file_put_contents($file . '.json', json_encode($this->debugHistory, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
                                if ($r === false) {
                                    $this->showCheck('error', 'red', 'unable to save file ' . $file . '.json');
                                } else {
                                    $this->showCheck('ok', 'green', 'file saved ' . $file . '.json');
                                }
                                break;
                            case strpos($origInput, '??load') === 0:
                                $part = explode(':', $origInput, 2);
                                $file = $part[1] ?? '_save';
                                array_pop($this->debugHistory);
                                $r = @file_get_contents($file . '.json');
                                if ($r === false) {
                                    $this->showCheck('error', 'red', 'unable to load file ' . $file . '.json');
                                }
                                $r = json_decode($r, true, 512, JSON_THROW_ON_ERROR);
                                self::testUserInput(null);
                                self::testUserInput($r, false);
                                break;
                            case '??history':
                                echo "\n-------history-----\n";
                                array_pop($this->debugHistory); // we remove '??history'
                                var_export($this->debugHistory);
                                echo "\n-------------------\n";
                                break;
                            case '??clear':
                                echo "\n-------history-----\n";
                                $this->debugHistory = [];
                                var_export($this->debugHistory);
                                echo "\n-------------------\n";
                                break;
                        }
                    } else if ($origInput === '?' || $origInput === '??') {
                        $this->showHelp($parameter, $origInput === '??');
                    } else {
                        break;
                    }
                }
                $parameter->value = $origInput;
                $parameter->origin = 'input';
                $this->refreshParamValueKey($parameter);
                $parameter->missing = false;
                //if($parameter->value==='' && $parameter->allowEmpty===true) {
                //}
                //$parameter->value = (!$parameter->value) ? $parameter->default : $parameter->value;
                /*if($parameter->value!=='' || !$parameter->allowEmpty) {
                    $parameter->value = (!$parameter->value) ? $parameter->default : $parameter->value;
                }*/
                if ($parameter->value === '') {
                    if ($parameter->allowEmpty === true) {
                        $parameter->value = $parameter->default === false ? '' : $parameter->default;
                    } else {
                        $parameter->value = $parameter->default;
                    }
                    $this->refreshParamValueKey($parameter);
                }
                switch ($parameter->inputType) {
                    case 'multiple':
                    case 'multiple2':
                    case 'multiple3':
                    case 'multiple4':
                    case 'option':
                    case 'option2':
                    case 'option3':
                    case 'option4':
                        $assoc = array_keys($parameter->inputValue) !== range(0, count($parameter->inputValue) - 1);
                        if (!$assoc) {
                            if ($parameter->value === 'a' || $parameter->value === 'n' || $parameter->value === '') {
                                $parameter->value = $this->emptyValue . $parameter->value;
                                $this->refreshParamValueKey($parameter);
                            } else if (is_numeric($parameter->value) && ($parameter->value <= count($parameter->inputValue))) {
                                $parameter->valueKey = $parameter->value;
                                $parameter->value = $parameter->inputValue[$parameter->value - 1] ?? null;
                            } else {
                                $parameter->valueKey = null;
                                $parameter->value = null;
                            }
                        } else if ($this->array_key_exists_i($parameter->value, $parameter->inputValue)) {
                            $parameter->valueKey = $this->get_array_key_i($parameter->value, $parameter->inputValue);
                            $lowerArray = array_change_key_case($parameter->inputValue);
                            $parameter->value = $lowerArray[strtolower($parameter->value)] ?? null;
                        } else if ($parameter->value === 'a' || $parameter->value === 'n' || $parameter->value === '') {
                            $parameter->valueKey = $parameter->value;
                            $parameter->value = $this->emptyValue . $parameter->value;
                        } else {
                            $parameter->valueKey = null;
                            $parameter->value = null;
                        }
                        break;
                }
            }
            switch ($parameter->inputType) {
                case 'number':
                    $ok = ($parameter->value === '' && $parameter->allowEmpty) || is_numeric($parameter->value);
                    $cause = 'it must be a number';
                    break;
                case 'range':
                    $ok = ($parameter->value === '' && $parameter->allowEmpty) || (is_numeric($parameter->value) && $parameter->value >= @$parameter->inputValue[0] && $parameter->value <= @$parameter->inputValue[1]);
                    $cause = 'it must be a number between the range ' . $parameter->inputValue[0] . ' and ' . $parameter->inputValue[1];
                    break;
                case 'string':
                case 'password':
                    if ($parameter->allowEmpty) {
                        $ok = true;
                    } else if ($parameter->value === '' || $parameter->value === null || $parameter->value === false) {
                        $ok = false;
                    } else {
                        $ok = true;
                    }
                    //$ok = ($parameter->value === '' && $parameter->allowEmpty) || is_string($parameter->value);
                    $cause = 'it must be a string not empty';
                    break;
                case 'multiple':
                case 'multiple2':
                case 'multiple3':
                case 'multiple4':
                    if ($parameter->value === null) {
                        $validateValues[] = ''; // to exit
                    } else if (strpos($parameter->value, ',') === false) {
                        $validateValues = [$parameter->value];
                    } else {
                        $validateValues = explode(',', $parameter->value);
                        $validateValues[] = ''; // to exit
                    }
                    foreach ($validateValues as $valueTmp) {
                        if ($valueTmp === $this->emptyValue . 'a' || $valueTmp === $this->emptyValue . 'n' || $valueTmp === $this->emptyValue) {
                            $ok = true;
                            break;
                        }
                        $ok = $parameter->value === '' || $this->in_array_i($valueTmp, $parameter->inputValue, true);
                        $valueTmp = str_replace($this->emptyValue, '', $valueTmp);
                        $cause = "it must be a valid value [$valueTmp]";
                        if (!$ok) {
                            break;
                        }
                    }
                    break;
                case 'option':
                case 'option2':
                case 'option3':
                case 'option4':
                    if ($parameter->value === $this->emptyValue) {
                        $parameter->valueKey = $parameter->value;
                        $parameter->value = '';
                    }
                    $ok = ($parameter->value === '' && $parameter->allowEmpty) || $parameter->valueKey !== null;
                    $vtmp = is_array($parameter->value) ? reset($parameter->value) : $parameter->value;
                    $cause = "this option does not exist [$vtmp]";
                    break;
                case 'optionshort':
                    if ($parameter->value === $this->emptyValue) {
                        $parameter->valueKey = $parameter->value;
                        $parameter->value = '';
                    }
                    $ok = ($parameter->value === '' && $parameter->allowEmpty) || $this->in_array_i($parameter->value, $parameter->inputValue, true);
                    if ($ok === false) {
                        $uniques = array_map(static function($v) {
                            return substr($v, 0, 1);
                        }, $parameter->inputValue);
                        if (count(array_unique($uniques)) === count($uniques)) {
                            // we find a short option but only if short option is available (if the first letter is unique)
                            foreach ($uniques as $k => $shortName) {
                                if ($parameter->value === $shortName) {
                                    $parameter->value = $parameter->inputValue[$k];
                                    $ok = true;
                                    break;
                                }
                            }
                        }
                    }
                    $cause = "the option does not exist [$parameter->value]";
                    break;
                default:
                    $ok = false;
                    $cause = 'unknown $parameter->inputType inputtype';
            }
            if (!$ok) {
                $this->showWarning("The value $parameter->key is not valid, $cause");
            }
            if ($askInput === false) {
                break;
            }
        }
        $this->errorType = 'show';
        return $ok;
    }

    private function in_array_i($needle, array $haystack, bool $strict = false): bool
    {
        return in_array(strtolower($needle), array_map('strtolower', $haystack), $strict);
    }

    /**
     * It is used for array that ignores clases. If we found the needle "Ab" in the array ["AB"=>1,...]
     * Then we return the original key "AB"
     * @param       $needle
     * @param array $haystack
     * @return int|string
     */
    private function get_array_key_i($needle, array $haystack)
    {
        $needle = strtolower($needle);
        foreach ($haystack as $k => $v) {
            if (strtolower($k) === $needle) {
                return $k;
            }
        }
        return $needle;
    }

    private function array_key_exists_i($key, $array): bool
    {
        if (!is_array($array)) {
            return false;
        }
        $lowerArray = array_change_key_case($array);
        return array_key_exists(strtolower($key), $lowerArray);
    }

    /**
     * Replaces all variables defined between {{ }} by a variable inside the dictionary of values.<br/>
     * Example:<br/>
     *      replaceCurlyVariable('hello={{var}}',['var'=>'world']) // hello=world<br/>
     *      replaceCurlyVariable('hello={{var}}',['varx'=>'world']) // hello=<br/>
     *      replaceCurlyVariable('hello={{var}}',['varx'=>'world'],true) // hello={{var}}<br/>
     *
     * @param string $string           The input value. It could contain variables defined as {{namevar}}
     * @param bool   $notFoundThenKeep [false] If true and the value is not found, then it keeps the value.
     *                                 Otherwise, it is replaced by an empty value
     *
     * @return string
     * @noinspection PhpVariableIsUsedOnlyInClosureInspection
     */
    public function replaceCurlyVariable(string $string, bool $notFoundThenKeep = false): string
    {
        if (strpos($string, '{{') === false) {
            return $string;
        } // nothing to replace.
        $me = $this;
        //$this->callVariablesCallBack();
        return preg_replace_callback('/{{\s?(\w+)\s?}}/u', static function($matches) use ($notFoundThenKeep, $me) {
            if (is_array($matches)) {
                $item = substr($matches[0], 2, -2); // removes {{ and }}
                return $me->getVariable($item, $notFoundThenKeep ? $matches[0] : '');
            }
            $item = substr($matches, 2, -2); // removes {{ and }}
            return $me->getVariable($item, $notFoundThenKeep ? $matches : '');
        }, $string);
    }
}
