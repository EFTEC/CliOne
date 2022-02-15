<?php /** @noinspection PhpUnused
 * @noinspection PhpMissingFieldTypeInspection
 * @noinspection AlterInForeachInspection
 */

namespace eftec\CliOne;

use Exception;
use RuntimeException;

/**
 * CliOne - A simple creator of command line argument program.
 *
 * @package   CliOne
 * @author    Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @copyright Copyright (c) 2022 Jorge Patricio Castro Castillo. Dual Licence: MIT License and Commercial.
 *            Don't delete this comment, its part of the license.
 * @version   1.3
 * @link      https://github.com/EFTEC/CliOne
 */
class CliOne
{
    public const VERSION = '1.3';
    /**
     * @var string it is the empty value, but it is also used to mark values that aren't selected directly "a" all, "n"
     *      nothing, "" enter exit
     */
    public $emptyValue = '__INPUT_';
    public static $autocomplete = [];
    public $origin;
    /** @var CliOneParam[] */
    public $parameters = [];
    protected $colSize = 80;
    protected $bread = [];

    /**
     * The constructor
     * @param ?string $origin you can specify the origin file. If you specific the origin file, then isCli will only
     *                        return true if the file is called directly.
     */
    public function __construct($origin = null)
    {
        $this->origin = $origin;
        $this->colSize = $this->calculateColSize();
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

    protected function calculateColSize($min = 80)
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
     * It returns the number of columns present in the screen. The columns are calculated in the constructor.
     * @return int
     */
    public function getColSize(): int
    {
        return $this->colSize;
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
     * @param string $key    the key to read.<br>
     *                       If $key='*' then it reads all the first keys and returns the first key
     *                       found if it has a value.
     * @return mixed Returns false if not value is found.
     */
    public function evalParam($key = '*', $forceInput = false, $returnValue = false)
    {
        $valueK = null;
        $notfound = true;
        foreach ($this->parameters as $k => $param) {
            if ($param->key === $key || ($key === '*' && $param->isOperator === true)) {
                $notfound = false;
                if ($param->missing === false && !$forceInput) {
                    // the parameter is already read, skipping.
                    return $returnValue === true ? $param->value : $param;
                }
                if ($param->currentAsDefault && $param->value !== null && $param->missing === true) {
                    $param->value = $param->currentAsDefault;
                    $this->assignParamValueKey($param);
                    return $returnValue === true ? $param->value : $param;
                }
                [$def, $param->value] = $this->readParameterCli($param);
                if ($key === '*' && $def === false) {
                    // value not found, not asking for input.
                    continue;
                }
                if ($def === false) {
                    // the value is not defined as an argument
                    if ($param->input === true) {
                        $def = true;
                        $param->value = $this->readParameterInput($param);
                    }
                    if ($def === false || $param->value === false) {
                        $param->value = $param->default;
                        if ($param->required && $param->value === false) {
                            $this->showLine("<e>Field $param->key is missing</e>");
                            $param->value = false;
                        }
                    }
                } else {
                    // the value is defined as an argument.
                    $ok = $this->validate($param, false);
                    if (!$ok) {
                        $param->value = false;
                    }
                }
                $valueK = $k;
            }
            if ($key === '*' && $param->value !== false) {
                // value found, exiting.
                break;
            }
        }
        if ($notfound) {
            $this->showLine("<e>parameter $key not defined</e>");
        }
        if ($valueK === false || $valueK === null) {
            return false;
        }
        return $returnValue === true ? $this->parameters[$valueK]->value : $this->parameters[$valueK];
    }

    /**
     * @param CliOneParam $param
     * @return void
     */
    protected function assignParamValueKey($param) : void
    {
        if (!is_array($param->inputValue)) {
            return;
        }
        if ($param->value !== null && strpos($param->value, $this->emptyValue) === 0) {
            $param->valueKey = str_replace($this->emptyValue, '', $param->value);
            return;
        }
        $k = array_search($param->value, $param->inputValue, true);
        $param->valueKey = $k === false ? null : $k;
    }

    /**
     * @param CliOneParam $parameter
     * @return array it returns the value if the field is assigned<br>
     *                      the default value if the field exists, but it doesn't have value<br>
     *                      or false if the field is not defined
     */
    public function readParameterCli($parameter): array
    {
        global $argv;
        $p = array_search('-' . $parameter->key, $argv, true);
        if ($p === false) {
            // the parameter is not found there.
            $parameter->missing = true;
            return [false, false];
        }
        $parameter->missing = false;
        if (count($argv) > $p + 1) {
            $next = self::removeTrailSlash($argv[$p + 1]);
            if (strpos($next, '-', true) === 0) {
                // -argument1 -argument2 (-argument1 is equals to "" and not -argument2)
                return [true, ''];
            }
            return [true, trim($next, " \t\n\r\0\x0B\"'")];
        }
        if ($parameter->default !== '') {
            return [true, $parameter->default];
        }
        return [true, ''];
    }

    /**
     * It removes trail slashes.
     * @param string $txt
     * @return string
     */
    protected static function removeTrailSlash($txt): string
    {
        return rtrim($txt, '/\\');
    }

    /**
     * @param CliOneParam $parameter
     * @return mixed|string
     * @noinspection DuplicatedCode
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
                        } else {
                            $this->showLine("<e>unknow selection $input</e>");
                        }
                }
            } else {
                $result = $input;
            }
        } while ($multiple);
        return $result;
    }

    /**
     * It shows the values as columns.
     * @param array       $values        the values to show. It could be an associative array or an indexed array.
     * @param string      $type          ['multiple','multiple2','multiple3','multiple4','option','option2','option3','option4'][$i]
     * @param null|string $patternColumn the pattern to be used, example: "<c>[{key}]</c> {value}"
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
                $pattern = '{selection}<c>[{key}]</c> {value}';
                $foot = "\t<c>[a]</c> select all, <c>[n]</c> select none, <c>[]</c> end selection, [*] (is marked as selected)";
                $columns = 1;
                break;
            case 'multiple2':
                $pattern = '{selection}<c>[{key}]</c> {value}';
                $foot = "\t<c>[a]</c> select all, <c>[n]</c> select none, <c>[]</c> end selection, [*] (is marked as selected)";
                $columns = 2;
                break;
            case 'multiple3':
                $pattern = '{selection}<c>[{key}]</c> {value}';
                $foot = "\t<c>[a]</c> select all, <c>[n]</c> select none, <c>[]</c> end selection, [*] (is marked as selected)";
                $columns = 3;
                break;
            case 'multiple4':
                $pattern = '{selection}<c>[{key}]</c> {value}';
                $foot = "\t<c>[a]</c> select all, <c>[n]</c> select none, <c>[]</c> end selection, [*] (is marked as selected)";
                $columns = 4;
                break;
            case 'option':
                $pattern = '<c>[{key}]</c> {value}';
                $foot = "";
                $columns = 1;
                break;
            case 'option2':
                $pattern = '<c>[{key}]</c> {value}';
                $foot = "";
                $columns = 2;
                break;
            case 'option3':
                $pattern = '<c>[{key}]</c> {value}';
                $foot = "";
                $columns = 3;
                break;
            case 'option4':
                $pattern = '<c>[{key}]</c> {value}';
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
            $maxL = max(strlen($keydisplay), $maxL);
        }
        for ($i = 0; $i < $chalf; $i++) {
            for ($kcol = 1; $kcol < 5; $kcol++) {
                if ($kcol <= $columns) {
                    $shift = $chalf * ($kcol - 1);
                    if (array_key_exists($i + $shift, $kvalues)) {
                        $keybase = $kvalues[$i + $shift];
                        $keydisplay = $assoc ? $keybase : ($keybase + 1);
                        $padnum = $maxL - strlen($keydisplay);
                        if ($assoc) {
                            // for padding the keys (assoc)
                            $padleft = floor($padnum / 2);
                            $padright = ceil($padnum / 2);
                            $keydisplay = str_repeat(' ', $padleft) . $keydisplay . str_repeat(' ', $padright);
                            //$keydisplay .= str_repeat(' ', $maxL - strlen($keydisplay));
                        } else {
                            // for padding the keys (numeric)
                            $keydisplay = str_repeat(' ', $padnum) . $keydisplay;
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
     * @param CliOneParam $parameter $param
     * @param string      $key       the key to show
     * @param mixed       $value     the value to show
     * @param string      $selection the selection (used by multiple to show [*])
     * @param int         $colW      the size of the col
     * @param string      $prefix    A prefix
     * @param string      $pattern   the pattern to use.
     * @return void
     */
    protected function showPattern($parameter, $key, $value, $selection, $colW, $prefix, $pattern)
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
        return $this->ellipsis(
            str_replace(
                array('{selection}', '{key}', '{value}', '{valueinit}', '{valuenext}', '{valueend}', '{desc}', '{def}', '{prefix}'),
                array($selection, $key, $valueToShow, $valueinit, $valuenext, $valueend, $desc, $def, $prefix), $pattern),
            $colW - 1);
    }

    protected function ellipsis($text, $lenght)
    {
        $l = strlen($text);
        if ($l <= $lenght) {
            return $text;
        }
        return substr($text, 0, $lenght - 3) . '...';
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
        echo $this->replaceColor($content);
    }

    /**
     * It sets the color of the cli<br>
     * <pre>
     * e = error (red)
     * </pre>
     *
     * @param                  $content
     * @param CliOneParam|null $cliOneParam
     * @return array|string|string[]
     */
    protected function replaceColor($content, $cliOneParam = null)
    {
        $t = floor($this->colSize / 6);
        if ($cliOneParam !== null) {
            if (is_array($cliOneParam->inputValue)) {
                $v = implode('/', $cliOneParam->inputValue);
            } else {
                $v = '';
            }
            $content = str_replace('<option/>', $v, $content);
        }
        $reset = "\033[0m";
        return str_replace(['<e>', '</e>', '<w>', '</w>', '<g>', '</g>'
                , '<s>', '</s>', '<i>', '</i>'
                , '<italic>', '</italic>', '<bold>', '</bold>', '<underline>', '</underline>'
                , '<c>', '</c>', '<m>', '</m>', '<y>', '</y>'
                , '<col0/>', '<col1/>', '<col2/>', '<col3/>', '<col4/>', '<col5/>']
            , ["\033[31m", $reset, "\033[33m", $reset, "\033[32m", $reset
                , "\033[32m", $reset, "\033[34m", $reset
                , "\e[3m", "\e[0m", "\e[1m", "\e[0m", "\e[4m", "\e[0m"
                , "\033[96m", $reset, "\033[95m", $reset, "\033[93m", $reset
                , "\e[0G", "\e[" . ($t) . "G", "\e[" . ($t * 2) . "G", "\e[" . ($t * 3) . "G", "\e[" . ($t * 4) . "G", "\e[" . ($t * 5) . "G"
            ]
            , $content);
    }

    /**
     * It shows (echo) a colored line. The syntax of the color is similar to html as follows:<br>
     * <pre>
     * <e>error</e> (color red)
     * <w>warning</w> (color yellow)
     * <i>information</i> (blue)
     * <y>yellow</y> (yellow)
     * <g>green</g> <s>success</s> (color green)
     * <italic>italic</italic>
     * <bold>bold</body>
     * <underline>underline</underline>
     * <c>cyan</c> (color light cyan)
     * <m>magenta</m> (color magenta)
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
        echo $this->replaceColor($content, $cliOneParam) . "\n";
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
                $pattern = $parameter->getPatterColumns()['1'] ?: "{desc} <c>[{def}]</c> {prefix}:";
                // the 9999 is to indicate to never ellipses this input.
                $txt = $this->showPattern($parameter, $parameter->key, $parameter->value, '', 9999, $prefix, $pattern);
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
            if (!$ok) {
                $this->showLine("<w>The value $parameter->key is not correct, $cause</w>");
            }
            if ($askInput === false) {
                break;
            }
        }
        return $ok;
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
        echo $this->replaceColor($content);
        // globals is used for phpunit.
        if (array_key_exists('PHPUNIT_FAKE_READLINE', $GLOBALS)) {
            $GLOBALS['PHPUNIT_FAKE_READLINE'][0]++;
            if ($GLOBALS['PHPUNIT_FAKE_READLINE'][0] >= count($GLOBALS['PHPUNIT_FAKE_READLINE'])) {
                throw new RuntimeException('Test incorrect, it is waiting for read more PHPUNIT_FAKE_READLINE ' . json_encode($GLOBALS['PHPUNIT_FAKE_READLINE']));
            }
            $this->showLine('<g><underline>[' . $GLOBALS['PHPUNIT_FAKE_READLINE'][$GLOBALS['PHPUNIT_FAKE_READLINE'][0]] . ']</underline></g>');
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
        return readline("");
    }

    /**
     * It shows the syntax of a parameter.
     * @param string $key  the key to show. "*" means all keys.
     * @param int    $tab  the first separation. Values are between 0 and 5.
     * @param int    $tab2 the second separation. Values are between 0 and 5.
     * @param array  $excludeKey the keys to exclude. It must be an indexed array with the keys to skip.
     * @return void
     */
    public function showParamSyntax($key, $tab = 0, $tab2 = 1, $excludeKey = []): void
    {
        if ($key === '*') {
            foreach ($this->parameters as $p) {
                if (!in_array($p->key, $excludeKey, true)) {
                    $this->showParamSyntax($p->key, $tab, $tab2);
                }
            }
            return;
        }
        $param = $this->getParameter($key);
        if ($param === false) {
            $this->showLine("<e>[ERROR]</e> Parameter $key not defined");
            return;
        }
        $v = $param->value;
        $v = is_array($v) ? json_encode($v) : $v;
        if ($param->inputType === 'password') {
            $v = '*****';
        }
        /** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
        $this->showLine("<col$tab/><g>-{$param->key}</g><col{$tab2}/>{$param->description} <c>[$v]</c>");
        foreach ($param->getHelpSyntax() as $help) {
            $this->showLine("<col$tab2/>$help", $param);
        }
    }

    /**
     * It gets the parameter by the key or false if not found.
     *
     * @param string $key the key of the parameter
     * @return CliOneParam|false
     */
    public function getParameter($key)
    {
        foreach ($this->parameters as $v) {
            if ($v->key === $key) {
                return $v;
            }
        }
        return false;
    }

    /**
     * It sets the value of a parameter manually.<br>
     * Once the value its set, then the system skip to read the values from the command line or ask for an input.
     *
     * @param string $key   the key of the parameter
     * @param mixed  $value the value to assign.
     * @return bool
     */
    public function setParam($key, $value): bool
    {
        foreach ($this->parameters as $param) {
            if ($param->key === $key) {
                $param->value = $value;
                $param->valueKey = null;
                $this->assignParamValueKey($param);
                $param->missing = false;
                return true;
            }
        }
        return false;
    }

    /**
     * It shows a label messages in a single line, example: <color>[ERROR]</color> Error message
     * @param string $label
     * @param string $color =['e','i','w','g','c'][$i]
     * @param string $content
     * @return void
     */
    public function showCheck($label, $color, $content): void
    {
        echo $this->replaceColor("<$color>[$label]</$color> $content") . "\n";
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
        $p = $this->getParameter($key);
        if ($p === false) {
            return null;
        }
        return $p->value;
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
        $p = $this->getParameter($key);
        if ($p === false) {
            return null;
        }
        return $p->valueKey;
    }

    /**
     * It will show all the parameters by showing the key, the default value and the value<br>
     * It is used for debug and testing.
     * @return void
     */
    public function showparams(): void
    {
        foreach ($this->parameters as $v) {
            try {
                $this->showLine("$v->key = [" . json_encode($v->default) . "] value:" . json_encode($v->value));
            } catch (Exception $e) {
            }
        }
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
     * It saves information into a file. The content will be serialized.
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
     * It returns an associative array with all the parameters of the form [key=>value]
     * @param array $excludeKeys you can add a key that you want to exclude.
     * @return array
     */
    public function getArrayParams($excludeKeys = []): array
    {
        $array = [];
        foreach ($this->parameters as $param) {
            if (!in_array($param->key, $excludeKeys, true)) {
                $array[$param->key] = $param->value;
            }
        }
        return $array;
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
        foreach ($this->parameters as $param) {
            if (!in_array($param->key, $excludeKeys, true)) {
                foreach ($array as $k => $v) {
                    if ($param->key === $k) {
                        $param->value = $v;
                        $this->assignParamValueKey($param);
                        $param->missing = false;
                        break;
                    }
                }
            }
        }
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
     * It creates a new parameter to be read from the command line or to be input by the user.
     * @param string $key The key or the parameter. It must be unique.
     * @param bool   $isOperator
     * @return CliOneParam
     */
    public function createParam($key, $isOperator = true): CliOneParam
    {
        return new CliOneParam($this, $key, $isOperator);
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
     * Down a level in the breadcrub.
     * @return CliOne
     */
    public function downLevel(): CliOne
    {
        array_pop($this->bread);
        return $this;
    }

    public function showBread(): CliOne
    {
        $txt = '';
        foreach ($this->bread as $v) {
            if ($v[1]) {
                $txt .= $v[0] . '(' . $v[1] . ')' . ' > ';
            } else {
                $txt .= $v[0] . ' > ';
            }
        }
        if (strlen($txt) > 3) {
            $txt = substr($txt, 0, -3);
        }
        $content = "\n<y>$txt</y>\n";
        $this->show($content);
        return $this;
    }
}
