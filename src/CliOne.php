<?php /** @noinspection PhpUnused
 * @noinspection PhpMissingFieldTypeInspection
 * @noinspection ReturnTypeCanBeDeclaredInspection
 * @noinspection AlterInForeachInspection
 */

namespace Eftec\CliOne;

use Exception;
use RuntimeException;

/**
 * CliOne - A simple creator of command line argument program.
 *
 * @package   CliOne
 * @author    Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @copyright Copyright (c) 2022 Jorge Patricio Castro Castillo. Dual Licence: MIT License and Commercial.
 *            Don't delete this comment, its part of the license.
 * @version   0.6
 * @link      https://github.com/EFTEC/CliOne
 */
class CliOne
{
    public $origin;
    /** @var CliOneParam[] */
    public $parameters = [];
    protected $colSize = 80;


    public function __construct($origin)
    {
        $this->origin = $origin;
        $this->colSize = $this->calculateColSize();
    }

    /**
     * @return int
     */
    public function getColSize()
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
     * @return CliOneParam|false Returns false if not value is found.
     */
    public function evalParam($key = '*', $forceInput = false)
    {
        $valueK = null;
        $notfound = true;
        foreach ($this->parameters as $k => $param) {
            if ($param->key === $key || ($key === '*' && $param->isOperator === true)) {
                $notfound = false;
                if ($param->missing === false && !$forceInput) {
                    // the parameter is already read, skipping.
                    return $param;
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
        return $this->parameters[$valueK];
    }

    /**
     * It sets the value of a parameter manually.<br>
     * Once the value its set, then the system skip to read the values from the command line or ask for an input.
     *
     * @param string $key
     * @param mixed  $value
     * @return bool
     */
    public function setParam($key, $value)
    {
        foreach ($this->parameters as $param) {
            if ($param->key === $key) {
                $param->value = $value;
                $param->missing = false;
                return true;
            }
        }
        return false;
    }

    /**
     * It shows (echo) a colored line. The syntax of the color is similar to html as follows:<br>
     * <pre>
     * <e>error</e> (color red)
     * <w>warning</w> (color yellow)
     * <i>information</i> (blue)
     * <g>green</g> <s>success</s> (color green)
     * <italic>italic</italic>
     * <bold>bold</body>
     * <underline>underline</underline>
     * <c>cyan</c> (color light cyan)
     * <m>magenta</m> (color magenta)
     * </pre>
     *
     *
     * @param string $content
     * @return void
     */
    public function showLine($content = '')
    {
        echo $this->replaceColor($content) . "\n";
    }

    /**
     * It shows a label messages in a single line, example: <color>[ERROR]</color> Error message
     * @param string $label
     * @param string $color =['e','i','w','g','c'][$i]
     * @param string $content
     * @return void
     */
    public function showCheck($label, $color, $content)
    {
        echo $this->replaceColor("<$color>[$label]</$color> $content") . "\n";
    }

    public function readline($content)
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
        return readline("");
    }

    /**
     * It's similar to showLine, but it keeps in the current line.
     *
     * @param string $content
     * @return void
     * @see \Eftec\CliOne\CliOne::showLine
     */
    public function show($content)
    {
        echo $this->replaceColor($content);
    }

    /**
     * It gets the parameter by the key or false if not found.
     *
     * @param string $key
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

    public function showparams()
    {
        foreach ($this->parameters as $v) {
            try {
                $this->showLine("  - $v->key = [" . json_encode($v->default) . "] ");
            } catch (Exception $e) {
            }
        }
    }

    public function isCli()
    {
        if (defined('PHPUNIT_COMPOSER_INSTALL') || defined('__PHPUNIT_PHAR__')) {
            // phpunit is running
            return false;
        }
        if (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) !== $this->origin) {
            // it is not running the right file.
            return false;
        }
        // false if it is running a web.
        return !http_response_code();
    }

    /**
     * @param CliOneParam $parameter
     * @return mixed|string
     * @noinspection DuplicatedCode
     */
    public function readParameterInput($parameter)
    {
        $result = '';
        if ($parameter->inputType === 'options') {
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
            switch ($parameter->inputType) {
                case 'options':
                    foreach ($parameter->inputValue as $k => $v) {
                        if (is_object($v) || is_array($v)) {
                            $this->showLine(($result[$k] ? "[*]" : "[ ]") . "<c>[" . ($k + 1) . "]</c> " . json_encode($v));
                        } else {
                            $this->showLine(($result[$k] ? "[*]" : "[ ]") . "<c>[" . ($k + 1) . "]</c> $v");
                        }

                    }
                    $this->showLine("\t<c>[a]</c> select all, <c>[n]</c> select none, <c>[]</c> end selection, [*] (is marked as selected)");
                    break;
                case 'option':
                    foreach ($parameter->inputValue as $k => $v) {
                        $this->showLine("<c>[" . ($k + 1) . "]</c> $v");
                    }
                    break;
                case 'option2':
                    $chalf = ceil(count($parameter->inputValue) / 2);
                    $colW = floor($this->colSize / 2);

                    for ($i = 0; $i < $chalf; $i++) {
                        $this->show("<c>[" . ($i + 1) . "]</c> " . $this->ellipsis($parameter->inputValue[$i], $colW - 5));
                        $shift = $chalf;
                        if (isset($parameter->inputValue[$i + $shift])) {
                            $this->show("\e[" . $colW . "G<c>[" . ($i + 1 + $shift) . "]</c> " . $this->ellipsis($parameter->inputValue[$i + $shift], $colW - 5));
                        }
                        $this->show("\n");
                    }
                    break;
                case 'option3':
                    $chalf = ceil(count($parameter->inputValue) / 3);
                    $colW = floor($this->colSize / 3);
                    for ($i = 0; $i < $chalf; $i++) {
                        $this->show("<c>[" . ($i + 1) . "]</c> " . $this->ellipsis($parameter->inputValue[$i], $colW - 5));
                        $shift = $chalf;
                        if (isset($parameter->inputValue[$i + $shift])) {
                            $this->show("\e[" . $colW . "G<c>[" . ($i + 1 + $shift) . "]</c> " . $this->ellipsis($parameter->inputValue[$i + $shift], $colW - 5));
                        }
                        $shift = $chalf + $chalf;
                        if (isset($parameter->inputValue[$i + $shift])) {
                            $this->show("\e[" . ($colW + $colW) . "G<c>[" . ($i + 1 + $shift) . "]</c> " . $this->ellipsis($parameter->inputValue[$i + $shift], $colW - 5));
                        }
                        $this->show("\n");
                    }
                    break;
                case 'option4':
                    $chalf = ceil(count($parameter->inputValue) / 4);
                    $colW = floor($this->colSize / 4);
                    for ($i = 0; $i < $chalf; $i++) {
                        $this->show("<c>[" . ($i + 1) . "]</c> " . $this->ellipsis($parameter->inputValue[$i], $colW - 5));
                        $shift = $chalf;
                        if (isset($parameter->inputValue[$i + $shift])) {
                            $this->show("\e[" . $colW . "G<c>[" . ($i + 1 + $shift) . "]</c> " . $this->ellipsis($parameter->inputValue[$i + $shift], $colW - 5));
                        }
                        $shift = $chalf + $chalf;
                        if (isset($parameter->inputValue[$i + $shift])) {
                            $this->show("\e[" . ($colW + $colW) . "G<c>[" . ($i + 1 + $shift) . "]</c> " . $this->ellipsis($parameter->inputValue[$i + $shift], $colW - 5));
                        }
                        $shift = $chalf + $chalf + $chalf;
                        if (isset($parameter->inputValue[$i + $shift])) {
                            $this->show("\e[" . ($colW + $colW + $colW) . "G<c>[" . ($i + 1 + $shift) . "]</c> " . $this->ellipsis($parameter->inputValue[$i + $shift], $colW - 5));
                        }
                        $this->show("\n");
                    }
                    break;
            }


            $this->validate($parameter);
            $input = $parameter->value;
            if ($parameter->inputType === 'options') {
                switch ($input) {
                    case '___input_a':
                        foreach ($result as &$item) {
                            $item = true;
                        }
                        break;
                    case '___input_n':
                        foreach ($result as &$item) {
                            $item = false;
                        }
                        break;
                    case '___input_':
                        $multiple = false;
                        $resultOld = $result;
                        $result = [];
                        foreach ($resultOld as $k => $item) {

                            if ($item) {
                                $result[] = $parameter->inputValue[$k];
                            }
                        }
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

    protected function ellipsis($text, $lenght)
    {
        $l = strlen($text);
        if ($l <= $lenght) {
            return $text;
        }
        return substr($text, 0, $lenght - 3) . '...';
    }

    /**
     * @param CliOneParam $parameter
     * @param bool        $askInput
     * @return bool
     */
    public function validate($parameter, $askInput = true)
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
                $desc = $parameter->question ?: $parameter->description;
                $origInput = $this->readline("$desc <c>[" . (is_array($parameter->default) ? implode(',', $parameter->default) : $parameter->default) . "]</c> $prefix:");
                $parameter->value = $origInput;
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
                }
                switch ($parameter->inputType) {
                    case 'options':
                    case 'option':
                    case 'option2':
                    case 'option3':
                    case 'option4':
                        if ($parameter->value === 'a' || $parameter->value === 'n' || $parameter->value === '') {
                            $parameter->value = '___input_' . ($parameter->value ?? '');
                        } else if (is_numeric($parameter->value) && ($parameter->value <= count($parameter->inputValue))) {
                            $parameter->value = $parameter->inputValue[$parameter->value - 1] ?? null;
                        } else {
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
                    if ($parameter->allowEmpty) {
                        $ok = true;
                    } else if ($parameter->value === '' || $parameter->value === null || $parameter->value === false) {
                        $ok = false;
                    } else {
                        $ok = true;
                    }
                    //$ok = ($parameter->value === '' && $parameter->allowEmpty) || is_string($parameter->value);
                    $cause = 'it must be a string';
                    break;
                case 'options':
                    if (strpos($parameter->value, ',') === false) {
                        $validateValues = [$parameter->value];
                    } else {
                        $validateValues = explode(',', $parameter->value);
                        $validateValues[] = ''; // to exit
                    }
                    foreach ($validateValues as $valueTmp) {
                        if ($valueTmp === '___input_a' || $valueTmp === '___input_n' || $valueTmp === '___input_') {
                            $ok = true;
                            break;
                        }

                        $ok = $parameter->value === '' || in_array($valueTmp, $parameter->inputValue, true);
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
                    if ($parameter->value === '___input_') {
                        $parameter->value = '';
                    }
                    $ok = ($parameter->value === '' && $parameter->allowEmpty) || in_array($parameter->value, $parameter->inputValue, true);
                    $cause = "the option does not exist [$parameter->value]";
                    break;
                case 'optionshort':
                    if ($parameter->value === '___input_') {
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
     * It sets the color of the cli<br>
     * <pre>
     * e = error (red)
     * </pre>
     *
     * @param $content
     * @return array|string|string[]
     */
    public function replaceColor($content)
    {
        return str_replace(['<e>', '</e>', '<w>', '</w>', '<g>', '</g>'
                , '<s>', '</s>', '<i>', '</i>'
                , '<italic>', '</italic>', '<bold>', '</bold>', '<underline>', '</underline>'
                , '<c>', '</c>', '<m>', '</m>']
            , ["\033[31m", "\033[0m", "\033[33m", "\033[0m", "\033[32m", "\033[0m"
                , "\033[32m", "\033[0m", "\036[34m", "\033[0m"
                , "\e[3m", "\e[0m", "\e[1m", "\e[0m", "\e[4m", "\e[0m"
                , "\033[96m", "\033[0m", "\033[95m", "\033[0m"]
            , $content);
    }


    /**
     * @param CliOneParam $parameter
     * @return array it returns the value if the field is assigned<br>
     *                      the default value if the field exists, but it doesn't have value<br>
     *                      or false if the field is not defined
     */
    public function readParameterCli($parameter)
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
            return [true, $next];
        }
        if ($parameter->default !== '') {
            return [true, $parameter->default];
        }
        return [true, ''];
    }


    public function createParam($key, $isOperator = true)
    {
        return new CliOneParam($this, $key, $isOperator);
    }

    protected static function removeTrailSlash($txt)
    {
        return rtrim($txt, '/\\');
    }

    /** @noinspection GrazieInspection */
    protected function calculateColSize($min = 80)
    {
        try {
            if (PHP_OS_FAMILY === 'Windows') {
                $a1 = shell_exec('mode con');
                /*
                 * Estado para dispositivo CON:
                 * ----------------------------
                 * Líneas:              9001
                 * Columnas:            85
                 * Ritmo del teclado:   31
                 * Retardo del teclado: 1
                 * Página de códigos:    65001
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
}
