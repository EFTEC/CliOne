<?php /** @noinspection PhpComposerExtensionStubsInspection */

namespace eftec\CliOne;

use Exception;
use RuntimeException;

/**
 * CliOne - A simple creator of command-line argument program.
 *
 * @package   CliOne
 * @author    Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @copyright Copyright (c) 2022 Jorge Patricio Castro Castillo. Dual Licence: MIT License and Commercial.
 *            Don't delete this comment, its part of the license.
 * @version   1.5.5
 * @link      https://github.com/EFTEC/CliOne
 */
class CliOne
{
    public const VERSION = '1.5.5';
    public static $autocomplete = [];
    /**
     * @var string it is the empty value, but it is also used to mark values that aren't selected directly "a" all, "n"
     *      nothing, "" enter exit
     */
    public $emptyValue = '__INPUT_';
    public $origin;
    /** @var CliOneParam[] */
    public $parameters = [];
    protected $colSize = 80;
    protected $bread = [];
    /** @var bool if true then mb_string library is loaded, otherwise it is false. it is calculated in the constructor */
    protected $multibyte = false;
    protected $styleStack = 'simple';
    protected $alignStack = ['middle', 'middle', 'middle'];
    protected $colorStack = [];
    protected $patternTitleStack;
    protected $patternCurrentStack;
    protected $patternSeparatorStack;
    protected $patternContentStack;
    protected $wait = 0;
    protected $silentError=false;

    /**
     * @return bool
     */
    public function isSilentError(): bool
    {
        return $this->silentError;
    }

    /**
     * @param bool $silentError
     * @return CliOne
     */
    public function setSilentError(bool $silentError): CliOne
    {
        $this->silentError = $silentError;
        return $this;
    }
    protected $waitPrev = '';
    /**
     * the arguments as a couple key/value. If the value is missing, then it is ''
     * @var array
     */
    protected $argv = [];
    /** @var bool if true then it will not show colors */
    public $noColor = false;

    public $colorTags = ['<red>', '</red>', '<yellow>', '</yellow>', '<green>', '</green>',
        '<white>', '</white>', '<blue>', '</blue>', '<black>', '</black>',
        '<cyan>', '</cyan>', '<magenta>', '</magenta>',
        '<bred>', '</bred>', '<byellow>', '</byellow>', '<bgreen>', '</bgreen>',
        '<bwhite>', '</bwhite>', '<bblue>', '</bblue>', '<bblack>', '</bblack>',
        '<bcyan>', '</bcyan>', '<bmagenta>', '</bmagenta>'];
    public $styleTextTags = ['<italic>', '</italic>', '<bold>', '</bold>', '<dim>', '</dim>',
        '<underline>', '</underline>', '<strikethrough>', '</strikethrough>'];
    public $columnTags = ['<col0/>', '<col1/>', '<col2/>',
        '<col3/>', '<col4/>', '<col5/>',];

    public $colorEscape = ["\e[31m", "\e[39m", "\e[33m", "\e[39m", "\e[32m", "\e[39m",
        "\e[37m", "\e[39m", "\e[34m", "\e[39m", "\e[30m", "\e[39m",
        "\e[36m", "\e[39m", "\e[35m", "\e[39m",
        "\e[41m", "\e[49m", "\e[43m", "\e[49m", "\e[42m", "\e[49m",
        "\e[47m", "\e[49m", "\e[44m", "\e[49m", "\e[40m", "\e[49m",
        "\e[46m", "\e[49m", "\e[45m", "\e[49m",];
    /** @var string[] note, it must be 2 digits */
    public $styleTextEscape = ["\e[03m", "\e[23m", "\e[01m", "\e[22m", "\e[02m", "\e[22m",
        "\e[04m", "\e[24m", "\e[09m", "\e[29m"];
    public $columnEscape = [];

    /**
     * The constructor
     * @param ?string $origin you can specify the origin file. If you specify the origin file, then isCli will only
     *                        return true if the file is called directly.
     */
    public function __construct($origin = null)
    {
        global $argv;
        if (getenv('NO_COLOR')) {
            $this->noColor = true;
        }
        $t = floor($this->colSize / 6);
        $this->columnEscape = ["\e[000G", "\e[" . sprintf('%03d', $t) . "G", "\e[" . sprintf('%03d', $t * 2) . "G",
            "\e[" . sprintf('%03d', $t * 3) . "G", "\e[" . sprintf('%03d', $t * 4) . "G", "\e[" . sprintf('%03d', $t * 5) . "G"];
        $this->argv = [];
        $c = count($argv);
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
        $this->origin = $origin;
        $this->colSize = $this->calculateColSize();
        $this->multibyte = function_exists('mb_strlen');
        // it is used by readline
        readline_completion_function(static function ($input) {
            // Filter Matches
            $matches = array();
            foreach (CliOne::$autocomplete as $cmd) {
                if (stripos($cmd, $input) === 0) {
                    $matches[] = $cmd;
                }
            }
            return $matches;
        });
    }

    /**
     * It finds the vendor path starting from a route. The route must be inside the application path.
     * @param string $initPath the initial path, example __DIR__, getcwd(), 'folder1/folder2'. If null, then __DIR__
     * @return string It returns the relative path to where is the vendor path. If not found then it returns the
     *                         initial path
     */
    public static function findVendorPath($initPath = null): string
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
    protected static function removeTrailSlash($txt): string
    {
        return rtrim($txt, '/\\');
    }

    /**
     * It creates a new parameter to be read from the command line and/or to be input manually by the user<br>
     * <b>Example:</b><br>
     * <pre>
     * $this->createParam('k1','first'); // php program.php thissubcommand
     * $this->createParam('k1','flag',['flag2','flag3']); // php program.php -k1 <val> or --flag2 <val> or --flag3
     * <val>
     * </pre>
     * @param string       $key   The key or the parameter. It must be unique.
     * @param string       $type  =['first','last','second','flag','longflag','onlyinput','none'][$i]<br>
     *                            <b>flag</b>: (default) it reads a flag "php program.php -thisflag value"<br>
     *                            <b>first</b>: it reads the first argument "php program.php thisarg" (without
     *                            value)<br>
     *                            <b>second</b>: it reads the second argument "php program.php sc1 thisarg" (without
     *                            value)<br>
     *                            <b>last</b>: it reads the second argument "php program.php ... thisarg" (without
     *                            value)<br>
     *                            <b>longflag</b>: it reads a longflag "php program --thislongflag value<br>
     *                            <b>last</b>: it reads the second argument "php program.php ... thisvalue" (without
     *                            value)<br>
     *                            <b>onlyinput</b>: the value means to be user-input, and it is stored<br>
     *                            <b>none</b>: the value it is not captured via argument, so it could be user-input,
     *                            but it is not stored<br> none parameters could always be overridden, and they are
     *                            used to "temporary" input such as validations (y/n).
     * @param array|string $alias A simple array with the name of the arguments to read (without - or --)<br>
     *                            if the type is a flag, then the alias is a double flag "--".<br>
     *                            if the type is a double flag, then the alias is a flag.
     * @return CliOneParam
     */
    public function createParam($key, $type = 'flag', $alias = []): CliOneParam
    {
        return new CliOneParam($this, $key, $type, $alias);
    }

    /**
     * Down a level in the breadcrub.<br>
     * If down more than the number of levels available, then it clears the stack.
     * @param int $number number of levels to down.
     * @return CliOne
     */
    public function downLevel($number = 1): CliOne
    {
        for ($i = 0; $i < $number; $i++) {
            array_pop($this->bread);
        }
        return $this;
    }

    /**
     * It evaluates the parameters obtained from the syntax of the command.<br>
     * The parameters must be defined before call this method<br>
     * <b>Example:</b><br>
     * <pre>
     * // shell:
     * php mycode.php -argument1 hello -argument2 world
     *
     * // php code:
     * $t=new CliOne('mycode.php');
     * $t->createParam('argument1')->add();
     * $result=$t->evalParam('argument1'); // an object ClieOneParam where value is "hello"
     * </pre>
     * @param string $key         the key to read.<br>
     *                            If $key='*' then it reads the first flag and returns its value (if any).
     * @param bool   $forceInput  it forces input no matter if the value is already inserted.
     * @param bool   $returnValue If true, then it returns the value obtained.<br>
     *                            If false (default value), it returns an instance of CliOneParam.
     * @return mixed Returns false if not value is found.
     */
    public function evalParam($key = '*', $forceInput = false, $returnValue = false)
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
                if ($parameter->currentAsDefault && $parameter->value !== null && $parameter->missing === true) {
                    $parameter->value = $parameter->currentAsDefault;
                    $this->assignParamValueKey($parameter);
                    if ($parameter->isAddHistory()) {
                        $this->addHistory($parameter->value);
                    }
                    return $returnValue === true ? $parameter->value : $parameter;
                }
                [$def, $parameter->value] = $this->readParameterArgFlag($parameter);
                if ($key === '*' && $def === false) {
                    // value not found, not asking for input.
                    continue;
                }
                if ($parameter->currentAsDefault && $currentValue !== null) {
                    $parameter->default = $currentValue;
                }
                if ($def === false && $currentValue !== null && $forceInput === false) {
                    $def = true;
                    $parameter->value = $currentValue;
                    $this->assignParamValueKey($parameter);
                }
                if ($def === false) {
                    // the value is not defined as an argument
                    if ($parameter->input === true) {
                        $def = true;
                        $parameter->value = $this->readParameterInput($parameter);
                    }
                    if ($def === false || $parameter->value === false) {
                        $parameter->value = $parameter->default;
                        if ($parameter->required && $parameter->value === false) {
                            if(!$this->isSilentError()) {
                                $this->showCheck('ERROR', 'red', "Field $parameter->key is missing");
                            }
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
        if ($notfound && !$this->isSilentError()) {
            $this->showCheck('ERROR', 'red', "parameter $key not defined");
        }
        if ($valueK === false || $valueK === null) {
            return false;
        }
        if ($this->parameters[$valueK]->isAddHistory()) {
            $this->addHistory($this->parameters[$valueK]->value);
        }
        return $returnValue === true ? $this->parameters[$valueK]->value : $this->parameters[$valueK];
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

    public function clearHistory(): CliOne
    {
        if (function_exists('readline_clear_history')) {
            readline_clear_history();
        }
        return $this;
    }

    public function listHistory(): array
    {
        if (function_exists('readline_list_history')) {
            return readline_list_history();
        }
        return [];
    }


    /**
     * It returns an associative array with all the parameters of the form [key=>value]
     * @param array $excludeKeys you can add a key that you want to exclude.
     * @return array
     */
    public function getArrayParams($excludeKeys = []): array
    {
        $array = [];
        foreach ($this->parameters as $parameter) {
            if (!in_array($parameter->key, $excludeKeys, true)) {
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
     * It gets the parameter by the key or false if not found.
     *
     * @param string $key the key of the parameter
     * @return CliOneParam
     */
    public function getParameter($key): CliOneParam
    {
        foreach ($this->parameters as $v) {
            if ($v->key === $key) {
                return $v;
            }
        }
        return new CliOneParam($this,null);
    }

    /**
     * It reads a value of a parameter.
     * <b>Example:</b><bt>
     * <pre>
     * // [1] option1
     * // [2] option2
     * // select a value [] 2
     * $v=$this->getValueKey('idparam'); // it will return "option2".
     * </pre>
     * @param string $key the key of the parameter to read the value
     * @return mixed|null It returns the value of the parameter or null if not found.
     */
    public function getValue($key)
    {
        $parameter = $this->getParameter($key);
        if (!$parameter->isValid()) {
            return null;
        }
        return $parameter->value;
    }

    /**
     * It reads the value-key of a parameter selected. It is useful for a list of elements.<br>
     * <b>Example:</b><br>
     * <pre>
     * // [1] option1
     * // [2] option2
     * // select a value [] 2
     * $v=$this->getValueKey('idparam'); // it will return 2 instead of "option2"
     * </pre>
     * @param string $key the key of the parameter to read the value-key
     * @return mixed|null It returns the value of the parameter or null if not found.
     *
     */
    public function getValueKey($key)
    {
        $parameter = $this->getParameter($key);
        if (!$parameter->isValid()) {
            return null;
        }
        return $parameter->valueKey;
    }

    /**
     * It will return true if the PHP is running on CLI<br>
     * If the constructor specified a file, then it is also used for validation.
     * <b>Example:</b><br>
     * <pre>
     * // page.php:
     * $inst=new CliOne('page.php'); // this security avoid calling the cli when this file is called by others.
     * if($inst->isCli()) {
     *    echo "Is CLI and the current page is page.php";
     * }
     * </pre>
     * @return bool
     */
    public function isCli(): bool
    {
        if (defined('PHPUNIT_COMPOSER_INSTALL') || defined('__PHPUNIT_PHAR__')) {
            // phpunit is running
            return false;
        }
        if ($this->origin !== null && isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) !== $this->origin) {
            // it is not running the right file.
            return false;
        }
        // false if it is running a web.
        return !http_response_code();
    }

    /**
     * It reads information from a file. The information will be de-serialized.
     * @param string $filename the filename with or without extension.
     * @return array it returns an array of the type [bool,mixed]<br>
     *                         In error, it returns [false,"error message"]<br>
     *                         In success, it returns [true,values de-serialized]<br>
     */
    public function readData($filename): ?array
    {
        $path = pathinfo($filename, PATHINFO_EXTENSION);
        if ($path === '') {
            $filename .= '.php';
        }
        try {
            $content = @file_get_contents($filename);
            if ($content === false) {
                throw new RuntimeException("Unable to read file $filename");
            }
            $content = substr($content, strpos($content, "\n") + 1); // remove the first line.
            return [true, json_decode($content, true)];
        } catch (Exception $ex) {
            return [false, $ex->getMessage()];
        }
    }

    /**
     * @param CliOneParam $parameter
     * @return array it returns the value if the field is assigned<br>
     *                      the default value if the field exists, but it doesn't have value<br>
     *                      or false if the field is not defined
     */
    public function readParameterArgFlag($parameter): array
    {
        //
        $position = false;
        switch ($parameter->type) {
            case 'last':
            case 'second':
            case 'first':
                $position = true;
                $trueName = $parameter->key;
                $extraName = '-';
                break;
            case 'flag':
                $trueName = '-' . $parameter->key;
                $extraName = '--';
                break;
            case 'longflag':
                $trueName = '--' . $parameter->key;
                $extraName = '-';
                break;
            default:
                $parameter->missing = true;
                return [false, null];
        }
        if ($position === false) {
            $value = $this->argv[$trueName] ?? null;
        } else {
            $keys = array_keys($this->argv);
            switch ($parameter->type) {
                case 'first':
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
            if ($value !== null && $keyP[0] === '-') {
                // positional argument exists however it is a flag.
                $value = null;
            }
        }
        if ($value === null) {
            // if the value is not found.
            $parameter->missing = true;
            // we try find in the alias (if any).
            foreach ($parameter->alias as $ali) {
                $value = $this->argv[$extraName . $ali] ?? null;
                if ($value !== null) {
                    $parameter->missing = false;
                    return [true, $value];
                }
            }
            return [false, false];
        }
        // the value is found and we return the value.
        $parameter->missing = false;
        return [true, $value];
    }

    /**
     * It saves the information into a file. The content will be serialized.
     * @param string $filename the filename (without extension) to where the value will be saved.
     * @param mixed  $content  The content to save. It will be serialized.
     * @return string empty string if the operation is correct, otherwise it will return a message with the error.
     */
    public function saveData($filename, $content): string
    {
        $path = pathinfo($filename, PATHINFO_EXTENSION);
        if ($path === '') {
            $filename .= '.php';
        }
        $contentData = "<?php http_response_code(404); die(1); // eftec/CliOne configuration file ?>\n";
        $contentData .= json_encode($content, JSON_PRETTY_PRINT);
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
     * It sets the alignment.  This method is stackable.<br>
     * <b>Example:</b><br>
     * <pre>
     * $cli->setAlign('left','left','right')->setStyle('double')->showTable($values);
     * </pre>>
     * @param string $title          =['left','right','middle'][$i] the alignment of the title
     * @param string $content        =['left','right','middle'][$i] the alignment of the content
     * @param string $contentNumeric =['left','right','middle'][$i] the alignment of the content (numeric)
     * @return $this
     */
    public function setAlign($title = 'middle', $content = 'middle', $contentNumeric = 'middle'): self
    {
        $this->alignStack = [$title, $content, $contentNumeric];
        return $this;
    }

    /**
     * It sets the parameters using an array of the form [key=>value]<br>
     * It also marks the parameters as missing=false
     * @param array $array       the associative array to use to set the parameters.
     * @param array $excludeKeys you can add a key that you want to exclude.
     * @return void
     */
    public function setArrayParam($array, $excludeKeys = []): void
    {
        foreach ($this->parameters as $parameter) {
            if (!in_array($parameter->key, $excludeKeys, true)) {
                foreach ($array as $k => $v) {
                    if ($parameter->key === $k) {
                        $parameter->value = $v;
                        $this->assignParamValueKey($parameter);
                        $parameter->missing = false;
                        break;
                    }
                }
            }
        }
    }

    /**
     * It sets the color in the stack
     * @param array $colors =['black','green','yellow','cyan','magenta','blue'][$i]
     * @return $this
     */
    public function setColor($colors): self
    {
        $this->colorStack = $colors;
        return $this;
    }

    /**
     * It sets the value of a parameter manually.<br>
     * If the value is present as argument, then the value of the argument is used<br>
     * If the value is not present as argument, then the user input is skipped.
     *
     * @param string $key   the key of the parameter
     * @param mixed  $value the value to assign.
     * @return CliOneParam true if the parameter is set, otherwise false
     */
    public function setParam($key, $value): CliOneParam
    {
        foreach ($this->parameters as $parameter) {
            if ($parameter->key === $key) {
                $parameter->value = $value;
                $parameter->valueKey = null;
                $this->assignParamValueKey($parameter);
                $parameter->missing = false;
                return $parameter;
            }
        }
        return new CliOneParam($this,null);
    }

    /**
     * {value} {type}
     * @param string|null string $pattern1Stack if null then it will use the default value.
     * @return $this
     */
    public function setPatternTitle($pattern1Stack = null): CliOne
    {
        $this->patternTitleStack = $pattern1Stack;
        return $this;
    }

    /**
     * <bold>{value}{type}</bold>
     * @param string|null $pattern2Stack if null then it will use the default value.
     * @return $this
     */
    public function setPatternCurrent($pattern2Stack = null): CliOne
    {
        $this->patternCurrentStack = $pattern2Stack;
        return $this;
    }

    /**
     * ">"
     * @param string|null $pattern3Stack if null then it will use the default value.
     * @return $this
     */
    public function setPatternSeparator($pattern3Stack = null): CliOne
    {
        $this->patternSeparatorStack = $pattern3Stack;
        return $this;
    }

    /**
     * Not used yet.
     * @param null|string $pattern4Stack if null then it will use the default value.
     * @return $this
     * @noinspection PhpUnused
     */
    public function setPatternContent($pattern4Stack = null): CliOne
    {
        $this->patternContentStack = $pattern4Stack;
        return $this;
    }


    /**
     * @param string $style =['mysql','simple','double','minimal'][$i]
     * @return $this
     */
    public function setStyle($style = 'simple'): self
    {
        $this->styleStack = $style;
        return $this;
    }

    /**
     * It's similar to showLine, but it keeps in the current line.
     *
     * @param string $content
     * @return void
     * @see \eftec\CliOne\CliOne::showLine
     */
    public function show($content): void
    {
        echo $this->colorText($content);
    }

    /**
     * It shows a breadcrumb.<br>
     * To add values you could use the method uplevel()<br>
     * To remove a value (going down a level) you could use the method downlevel()<br>
     * You can also change the style using setPattern1(),setPattern2(),setPattern3()<br>
     * <pre>
     * $cli->setPattern1('{value}{type}') // the level
     *      ->setPattern2('<bred>{value}</bred>{type}') // the current level
     *      ->setPattern3(' -> ') // the separator
     *      ->showBread();
     * </pre>
     * It shows the current BreadCrumb if any.
     * @param bool $showIfEmpty if true then it shows the breadcrumb even if it is empty (empty line)<br>
     *                          if false (default) then it doesn't show the breadcrumb if it is empty.
     * @return $this
     */
    public function showBread($showIfEmpty = false): CliOne
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
     * @param string $label
     * @param string $color =['black','green','yellow','cyan','magenta','blue'][$i]
     * @param string $content
     * @return void
     */
    public function showCheck($label, $color, $content): void
    {
        echo $this->colorText("<$color>[$label]</$color> $content") . "\n";
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
     * It shows (echo) a colored line. The syntax of the color is similar to html as follows:<br>
     * <pre>
     * <red>error</red> (color red)
     * <yellow>warning</yellow> (color yellow)
     * <blue>information</blue> (blue)
     * <yellow>yellow</yellow> (yellow)
     * <green>green</green> <green>success</green> (color green)
     * <italic>italic</italic>
     * <bold>bold</bold>
     * <dim>dim</dim>
     * <underline>underline</underline>
     * <cyan>cyan</cyan> (color light cyan)
     * <magenta>magenta</magenta> (color magenta)
     * <col0/><col1/><col2/><col3/><col4/><col5/>  columns. col0=0 (left),col1--col5 every column of the page.
     * <option/> it shows all the options available (if the input has some options)
     * </pre>
     *
     *
     * @param string $content content to display
     * @param null   $cliOneParam
     * @return void
     */
    public function showLine($content = '', $cliOneParam = null): void
    {
        echo $this->colorText($content, $cliOneParam) . "\n";
    }

    /**
     * @param string|string[] $lines
     * @param string|string[] $titles
     * @return void
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function showMessageBox($lines, $titles = []): void
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
            $lines = [$lines];
        }
        if (is_string($titles)) {
            // transform into array
            $titles = [$titles];
        }
        if (count($titles) > count($lines)) {
            $lines = $this->alignLinesMiddle($lines, count($titles));
        }
        if (count($titles) < count($lines)) {
            // align to the center by adding the missing lines at the top and bottom.
            $titles = $this->alignLinesMiddle($titles, count($lines));
        }
        $maxTitleL = 0;
        // max title width
        foreach ($titles as $title) {
            $maxTitleL = ($this->strlen($title) > $maxTitleL) ? $this->strlen($title) : $maxTitleL;
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

    public function alignLinesMiddle($lines, $numberLines): array
    {
        $dif = $numberLines - count($lines);
        $dtop = floor($dif / 2);
        $dbottom = ceil($dif / 2);
        $tmp = [];
        for ($i = 0; $i < $dtop; $i++) {
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

    /**
     * It shows the syntax of a parameter.
     * @param string $key        the key to show. "*" means all keys.
     * @param int    $tab        the first separation. Values are between 0 and 5.
     * @param int    $tab2       the second separation. Values are between 0 and 5.
     * @param array  $excludeKey the keys to exclude. It must be an indexed array with the keys to skip.
     * @return void
     */
    public function showParamSyntax($key, $tab = 0, $tab2 = 1, $excludeKey = []): void
    {
        if ($key === '*') {
            foreach ($this->parameters as $parameter) {
                if (($parameter->type !== 'none') && !in_array($parameter->key, $excludeKey, true)) {
                    $this->showParamSyntax($parameter->key, $tab, $tab2);
                }
            }
            return;
        }
        $parameter = $this->getParameter($key);
        $paramprefix = $parameter->type === 'longflag' ? '--' : '-';
        $paramprefixalias = $parameter->type === 'longflag' ? '-' : '--';
        if (!$parameter->isValid()) {
            if(!$this->isSilentError()) {
                $this->showCheck('ERROR', 'red', "Parameter $key not defined");
            }
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
    }

    /**
     * @param numeric     $currentValue     the current value
     * @param numeric     $max              the max value to fill the bar.
     * @param int         $columnWidth      the size of the bar (in columns)
     * @param string|null $currentValueText the current value to display at the left.<br>
     *                                      if null then it will show the current value (with a space in between)
     * @return void
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function showProgressBar($currentValue, $max, $columnWidth, $currentValueText = null): void
    {
        $this->initstack();
        $style = $this->styleStack;
        [$alignTitle, $alignContentText, $alignContentNumber] = $this->alignStack;
        [$bf, $bl, $bm, $bd] = $this->shadow($style);
        $prop = $columnWidth / $max;
        $currentValueText = $currentValueText ?? ' ' . $currentValue;
        $this->show(str_repeat($bf, floor($currentValue * $prop)) . str_repeat($bl, floor($max * $prop) - floor($currentValue * $prop)) . $currentValueText . "\e[" . (floor($max * $prop) + $this->strlen($currentValueText)) . "D");
    }

    /**
     * It shows an associative array.  This command is the end of a stack.
     * @param array $assocArray An associative array with the values to show. The key is used for the index.
     * @return void
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function showTable($assocArray): void
    {
        $this->initstack();
        $style = $this->styleStack;
        [$alignTitle, $alignContentText, $alignContentNumber] = $this->alignStack;
        [$ul, $um, $ur, $ml, $mm, $mr, $dl, $dm, $dr, $mmv] = $this->border($style);
        [$cutl, $cutt, $cutr, $cutd, $cutm] = $this->borderCut($style);
        if (count($assocArray) === 0) {
            return;
        }
        $contentw = $this->colSize - $this->strlen($ml) - $this->strlen($mr);
        $columns = array_keys($assocArray[0]);
        $maxColumnSize = [];
        foreach ($columns as $column) {
            $maxColumnSize[$column] = 0;
        }
        foreach ($assocArray as $row) {
            foreach ($columns as $column) {
                if ($this->strlen($row[$column]) > $maxColumnSize[$column]) {
                    $maxColumnSize[$column] = $this->strlen($row[$column]);
                }
            }
        }
        $contentwCorrected = $contentw - count($columns) + 1;
        $totalCol = array_sum($maxColumnSize);
        foreach ($columns as $column) {
            $maxColumnSize[$column] = (int)round($maxColumnSize[$column] * $contentwCorrected / $totalCol);
        }
        if (array_sum($maxColumnSize) > $contentwCorrected) {
            // we correct the precision error of round by removing 1 to the first column
            $maxColumnSize[$columns[0]]--;
        }
        if (array_sum($maxColumnSize) < $contentwCorrected) {
            // we correct the precision error of round by removing 1 to the first column
            $maxColumnSize[$columns[0]]++;
        }
        // top
        if ($ul) {
            $txt = $ul;
            foreach ($maxColumnSize as $size) {
                $txt .= str_repeat($um, $size) . $cutt;
            }
            $txt = $this->removechar($txt, $this->strlen($cutt, false)) . $ur;
            $this->showLine($txt);
        }
        // title
        $txt = $ml;
        foreach ($maxColumnSize as $colName => $size) {
            $txt .= $this->alignText($colName, $size, $alignTitle) . $mmv;
        }
        $txt = $this->removechar($txt, $this->strlen($mmv, false)) . $mr;
        $this->showLine($txt);
        // botton title
        $txt = $cutl;
        foreach ($maxColumnSize as $size) {
            $txt .= str_repeat($mm, $size) . $cutm;
        }
        $txt = rtrim($txt, $cutm) . $cutr;
        $this->showLine($txt);
        // content
        foreach ($assocArray as $k => $line) {
            $txt = $ml;
            foreach ($maxColumnSize as $colName => $size) {
                $line[$colName] = $line[$colName] ?? '(null)';
                $txt .= $this->alignText(
                        $line[$colName],
                        $size,
                        is_numeric($line[$colName]) ? $alignContentNumber : $alignContentText) . $mmv;
            }
            $txt = rtrim($txt, $mmv) . $mr;
            if ($k === count($assocArray) - 1) {
                $this->show($txt);
            } else {
                $this->showLine($txt);
            }
        }
        // botton table
        if ($dl) {
            $this->showLine();
            $txt = $dl;
            foreach ($maxColumnSize as $size) {
                $txt .= str_repeat($dm, $size) . $cutd;
            }
            $txt = rtrim($txt, $cutd) . $dr;
            $this->show($txt);
        }
        $this->resetStack();
        $this->showLine();
    }

    /**
     * It shows the values as columns.
     * @param array       $values        the values to show. It could be an associative array or an indexed array.
     * @param string      $type          ['multiple','multiple2','multiple3','multiple4','option','option2','option3','option4'][$i]
     * @param null|string $patternColumn the pattern to be used, example: "<cyan>[{key}]</cyan> {value}"
     * @return void
     */
    public function showValuesColumn($values, $type, $patternColumn = null): void
    {
        $p = new CliOneParam($this, 'dummy', false, null, null);
        $p->setPattern($patternColumn);
        $p->inputValue = $values;
        $p->inputType = $type;
        $this->internalShowOptions($p, []);
    }

    /**
     * It shows a waiting cursor.
     * @param bool   $init         the first time this method is called, you must set this value as true. Then, every
     *                             update must be false.
     * @param string $postfixValue if you want to set a profix value such as percentage, advance, etc.
     * @return void
     */
    public function showWaitCursor($init = true, $postfixValue = ''): void
    {
        if ($init) {
            $this->wait = 0;
        }
        $this->wait++;
        switch ($this->wait) {
            case 0:
            case 4:
                $c = '|';
                break;
            case 1:
                $c = '/';
                break;
            case 2:
            case 5:
                $c = '-';
                break;
            case 3:
            case 6:
                $c = '\\';
                break;
            default:
                $this->wait = 0;
                $c = '|';
                break;
        }
        if ($init) {
            $this->show($c . $postfixValue);
        } else {
            $this->show("\e[" . ($this->strlen($this->waitPrev) + 1) . "D" . $c . $postfixValue); // [2D 2 left, [C 1 right
        }
        $this->waitPrev = $postfixValue;
    }

    /**
     * It will show all the parameters by showing the key, the default value and the value<br>
     * It is used for debugging and testing.
     * @return void
     */
    public function showparams(): void
    {
        foreach ($this->parameters as $parameter) {
            try {
                $this->showLine("$parameter->key = [" .
                    json_encode($parameter->default) . "] value:" .
                    $this->showParamValue($parameter->value));
            } catch (Exception $e) {
            }
        }
    }

    /**
     * @param CliOneParam $parameter
     * @return string
     */
    public function showParamValue($parameter): string
    {
        if ($parameter->inputType === 'password') {
            return '*****';
        }
        if ($parameter->value === null) {
            return '(null)';
        }
        if (is_array($parameter->value)) {
            return json_encode($parameter->value);
        }
        return $parameter->value;
    }

    /**
     * It determines the size of a string
     * @param      $content
     * @param bool $visual visual means that it considers the visual lenght, false means it considers characters.
     * @return false|int
     */
    public function strlen($content, $visual = true)
    {
        $contentClear = $this->colorLess($content);
        if ($this->multibyte && $visual) {
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
    public function removechar($content, $numchar): string
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
    public function upLevel($content, $type = ''): CliOne
    {
        $this->bread[] = [$content, $type];
        return $this;
    }

    /**
     * @param string $text
     * @param int    $width
     * @param string $align =['left','right','middle'][$i]
     * @return mixed|string
     */
    protected function alignText($text, $width, $align)
    {
        $len = $this->strlen($text);
        if ($len > $width) {
            $text = $this->ellipsis($text, $width);
            $len = $width;
        }
        $padnum = $width - $len;
        switch ($align) {
            case 'left':
                return $text . str_repeat(' ', $padnum);
            case 'right':
                return str_repeat(' ', $padnum) . $text;
            case 'middle':
                $padleft = floor($padnum / 2);
                $padright = ceil($padnum / 2);
                return str_repeat(' ', $padleft) . $text . str_repeat(' ', $padright);
            default:
                trigger_error("align incorrect $align");
        }
        return $text;
    }

    /**
     * With the value of the parameter, the system assign the valuekey of the parameter<br>
     * If the parameter doesn't have inputvalues, or the value is not in the list of inputvalues, then it does nothing.
     * @param CliOneParam $parameter
     * @return void
     */
    protected function assignParamValueKey($parameter): void
    {
        if (!is_array($parameter->inputValue)) {
            return;
        }
        if ($parameter->value !== null && strpos($parameter->value, $this->emptyValue) === 0) {
            $parameter->valueKey = str_replace($this->emptyValue, '', $parameter->value);
            return;
        }
        $k = array_search($parameter->value, $parameter->inputValue, true);
        $parameter->valueKey = $k === false ? null : $k;
    }

    /**
     * <pre>
     * // up left, up middle, up right, middle left, middle right, down left, down middle, down right.
     * [$ul, $um, $ur, $ml, $mm, $mr, $dl, $dm, $dr, $mmv]=$this->border();
     * </pre>
     * @param string $style =['mysql','simple','double']
     * @return string[]
     */
    protected function border($style): array
    {
        switch ($style) {
            case 'mysql':
                return [
                    '+', '-', '+',
                    '|', '-', '|',
                    '+', '-', '+', '|'];
            case 'double':
                return [
                    '╔', '═', '╗',
                    '║', '═', '║',
                    '╚', '═', '╝', '║'];
            case 'simple':
                return [
                    '┌', '─', '┐',
                    '│', '─', '│',
                    '└', '─', '┘', '│'];
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
     * <pre>
     * // cut left, cut top, cut right, cut bottom , cut middle
     * [$cutl, $cutt, $cutr, $cutd, $cutm] = $this->borderCut($style);
     * </pre>
     * @param string $style =['mysql','simple','double']
     * @return string[]
     */
    protected function borderCut($style): array
    {
        switch ($style) {
            case 'mysql':
                return ['+', '+', '+', '+', '+'];
            case 'double':
                return ['╠', '╦', '╣', '╩', '╬'];
            case 'simple':
                return ['├', '┬', '┤', '┴', '┼'];
            case 'minimal':
                return ['', '', '', '', ' '];
            default:
                trigger_error("style not defined $style");
        }
        return [];
    }

    protected function calculateColSize($min = 40)
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                $a1 = shell_exec('mode con');
                /*
                 * Estado para dispositivo CON:
                 * ----------------------------
                 * Líneas:              9001
                 * Columnas: 85
                 * Ritmo del teclado: 31
                 * Retardo del teclado: 1
                 * Página de códigos: 65001
                 */
                $arr = explode("\n", $a1);
                $col = trim(explode(':', $arr[4])[1]);
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

    protected function ellipsis($text, $lenght)
    {
        $l = $this->strlen($text);
        if ($l <= $lenght) {
            return $text;
        }
        return $this->removechar($text, $l - $lenght + 3) . '...';
    }

    protected function initStack(): void
    {
        foreach ($this->colorStack as $color) {
            $this->show("<$color>");
        }
    }

    /**
     * It shows the listing of options
     *
     * @param CliOneParam $parameter
     * @param array       $result used by multiple
     * @return void
     */
    protected function internalShowOptions($parameter, $result): void
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
        $assoc = !isset($parameter->inputValue[0]);
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
                        $this->show("\e[" . ($col) . "G" . $txt);
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
     * @return array|string|null
     */
    protected function readParameterInput($parameter)
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
                        } else if(!$this->isSilentError()) {
                            $this->showCheck('ERROR', 'red,', "unknow selection $input");
                        }
                }
            } else {
                $result = $input;
            }
        } while ($multiple);
        return $result;
    }

    /**
     * It reads a line input that the user must enter the information<br>
     * <b>Note:</b> It could be simulated using the global $GLOBALS['PHPUNIT_FAKE_READLINE'] (array)
     * , where the first value must be 0, and the other values must be the input emulated
     * @param string      $content The prompt.
     * @param CliOneParam $parameter
     * @return false|mixed|string returns the user input.
     */
    protected function readline($content, $parameter)
    {
        echo $this->colorText($content);
        // globals is used for phpunit.
        if (array_key_exists('PHPUNIT_FAKE_READLINE', $GLOBALS)) {
            $GLOBALS['PHPUNIT_FAKE_READLINE'][0]++;
            if ($GLOBALS['PHPUNIT_FAKE_READLINE'][0] >= count($GLOBALS['PHPUNIT_FAKE_READLINE'])) {
                throw new RuntimeException('Test incorrect, it is waiting for read more PHPUNIT_FAKE_READLINE ' . json_encode($GLOBALS['PHPUNIT_FAKE_READLINE']));
            }
            $this->showLine('<green><underline>[' . $GLOBALS['PHPUNIT_FAKE_READLINE'][$GLOBALS['PHPUNIT_FAKE_READLINE'][0]] . ']</underline></green>');
            return $GLOBALS['PHPUNIT_FAKE_READLINE'][$GLOBALS['PHPUNIT_FAKE_READLINE'][0]];
        }
        if (is_array($parameter->inputValue) && count($parameter->inputValue) > 0) {
            $assoc = !isset($parameter->inputValue[0]);
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
        $r = readline("");
        if (count($parameter->getHistory()) > 0) {
            // if we use a parameter history, then we return to the previous history
            /** @noinspection PhpUndefinedVariableInspection */
            $this->setHistory($prevhistory);
        }
        return $r;
    }


    /**
     * It sets the color of the cli<br>
     * <pre>
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
     * </pre>
     *
     * @param                  $content
     * @param CliOneParam|null $cliOneParam
     * @return array|string|string[]
     */
    public function colorText($content, $cliOneParam = null)
    {
        if ($cliOneParam !== null) {
            if (is_array($cliOneParam->inputValue)) {
                $v = implode('/', $cliOneParam->inputValue);
            } else {
                $v = '';
            }
            $content = str_replace('<option/>', $v, $content);
        }
        $content = str_replace($this->colorTags, $this->noColor ? array_fill(0, count($this->colorTags), '') : $this->colorEscape, $content);
        $content = str_replace($this->styleTextTags, $this->styleTextEscape, $content);
        return str_replace($this->columnTags, $this->columnEscape, $content);
    }

    /**
     * It removes all the escape characters of a content
     * @param string $content
     * @return string
     */
    public function colorLess($content): string
    {
        $content = str_replace($this->colorEscape, array_fill(0, count($this->colorEscape), ''), $content);
        $content = str_replace($this->styleTextEscape, array_fill(0, count($this->styleTextEscape), ''), $content);
        return str_replace($this->columnEscape, array_fill(0, count($this->columnEscape), ''), $content);
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
        $content = str_replace($this->colorEscape, array_fill(0, count($this->colorEscape), $m5), $content);
        $content = str_replace($this->styleTextEscape, array_fill(0, count($this->styleTextEscape), $m5), $content);
        return str_replace($this->columnEscape, array_fill(0, count($this->columnEscape), $m6), $content);
    }

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
     * <pre>
     * [$bf,$bl,$bm,$bd]=$this->shadow();
     * </pre>
     * @param string $style =['mysql','simple','double']
     * @return array|string[]
     */
    protected function shadow($style = 'simple'): array
    {
        switch ($style) {
            case 'mysql':
                return ['#', ' ', '-', '='];
            case 'simple':
                return ['█', ' ', '░', '▓'];
            case 'double':
                return ['█', '░', '▒', '▓'];
            case 'minimal':
                return ['*', ' ', ' ', ' '];
            default:
                trigger_error("style not defined $style");
        }
        return [];
    }

    /**
     * @param CliOneParam $parameter $param
     * @param string      $key       the key to show
     * @param mixed       $value     the value to show
     * @param string      $selection the selection (used by multiple to show [*])
     * @param int         $colW      the size of the col
     * @param string      $prefix    A prefix
     * @param string      $pattern   the pattern to use.
     * @return string
     */
    protected function showPattern($parameter, $key, $value, $selection, $colW, $prefix, $pattern): string
    {
        $desc = $parameter->question ?: $parameter->description;
        $def = (is_array($parameter->default) ? implode(',', $parameter->default) : $parameter->default);
        if ($parameter->inputType === 'password') {
            $def = '*****';
        }
        $valueToShow = (is_object($value) || is_array($value)) ? json_encode($value) : $value;
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
            array('{selection}', '{key}', '{value}', '{valueinit}', '{valuenext}', '{valueend}', '{desc}', '{def}', '{prefix}'),
            array($selection, $key, $valueToShow, $valueinit, $valuenext, $valueend, $desc, $def, $prefix), $pattern);
        $text = $this->colorText($text);
        return $this->ellipsis($text, $colW - 1);
    }

    /**
     * @param CliOneParam $parameter
     * @param bool        $askInput
     * @return bool
     */
    protected function validate($parameter, $askInput = true): bool
    {
        $ok = false;
        $cause = 'no cause found';
        while (!$ok) {
            switch ($parameter->inputType) {
                case 'range':
                    $prefix = @$parameter->inputValue[0] . '-' . @$parameter->inputValue[1];
                    break;
                case 'optionshort':
                    $uniques = array_map(static function ($v) {
                        return substr($v, 0, 1);
                    }, $parameter->inputValue);
                    if (count(array_unique($uniques)) !== count($uniques)) {
                        // there is some  repeated valued
                        $prefix = implode('/', $parameter->inputValue);
                    } else {
                        $values2 = array_map(static function ($v) {
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
                $origInput = $this->readline($txt, $parameter);
                $parameter->value = $origInput;
                $this->assignParamValueKey($parameter);
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
                    $this->assignParamValueKey($parameter);
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
                        $assoc = !isset($parameter->inputValue[0]);
                        if (!$assoc) {
                            if ($parameter->value === 'a' || $parameter->value === 'n' || $parameter->value === '') {
                                $parameter->value = $this->emptyValue . ($parameter->value ?? '');
                                $this->assignParamValueKey($parameter);
                            } else if (is_numeric($parameter->value) && ($parameter->value <= count($parameter->inputValue))) {
                                $parameter->valueKey = $parameter->value;
                                $parameter->value = $parameter->inputValue[$parameter->value - 1] ?? null;
                            } else {
                                $parameter->valueKey = null;
                                $parameter->value = null;
                            }
                        } else if (array_key_exists($parameter->value, $parameter->inputValue)) {
                            $parameter->valueKey = $parameter->value;
                            $parameter->value = $parameter->inputValue[$parameter->value] ?? null;
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
                        $ok = $parameter->value === '' || in_array($valueTmp, $parameter->inputValue, true);
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
                    $cause = "the option does not exist [$vtmp]";
                    break;
                case 'optionshort':
                    if ($parameter->value === $this->emptyValue) {
                        $parameter->valueKey = $parameter->value;
                        $parameter->value = '';
                    }
                    $ok = ($parameter->value === '' && $parameter->allowEmpty) || in_array($parameter->value, $parameter->inputValue, true);
                    if ($ok === false) {
                        $uniques = array_map(static function ($v) {
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
            if (!$ok && !$this->isSilentError()) {
                $this->showCheck('WARNING', 'yellow', "The value $parameter->key is not correct, $cause");
            }
            if ($askInput === false) {
                break;
            }
        }
        return $ok;
    }

    /**
     * Replaces all variables defined between {{ }} by a variable inside the dictionary of values.<br>
     * Example:<br>
     *      replaceCurlyVariable('hello={{var}}',['var'=>'world']) // hello=world<br>
     *      replaceCurlyVariable('hello={{var}}',['varx'=>'world']) // hello=<br>
     *      replaceCurlyVariable('hello={{var}}',['varx'=>'world'],true) // hello={{var}}<br>
     *
     * @param string $string           The input value. It could contain variables defined as {{namevar}}
     * @param array  $values           The dictionary of values.
     * @param bool   $notFoundThenKeep [false] If true and the value is not found, then it keeps the value.
     *                                 Otherwise, it is replaced by an empty value
     *
     * @return string|string[]|null
     * @noinspection PhpUnused
     */
    public static function replaceCurlyVariable($string, $values, $notFoundThenKeep = false)
    {
        if (strpos($string, '{{') === false) {
            return $string;
        } // nothing to replace.
        return preg_replace_callback('/{{\s?(\w+)\s?}}/u', static function ($matches) use ($values, $notFoundThenKeep) {
            if (is_array($matches)) {
                $item = substr($matches[0], 2, -2); // removes {{ and }}
                /** @noinspection NestedTernaryOperatorInspection */
                /** @noinspection NullCoalescingOperatorCanBeUsedInspection */
                /** @noinspection PhpIssetCanBeReplacedWithCoalesceInspection */
                return isset($values[$item]) ? $values[$item] : ($notFoundThenKeep ? $matches[0] : '');
            }
            $item = substr($matches, 2, -2); // removes {{ and }}
            return $values[$item] ?? $notFoundThenKeep ? $matches : '';
        }, $string);
    }
}
