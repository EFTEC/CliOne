<?php /** @noinspection PhpUnused */
/** @noinspection PhpMissingFieldTypeInspection */
/** @noinspection ReturnTypeCanBeDeclaredInspection */

/** @noinspection AlterInForeachInspection */

namespace Eftec\CliOne;

/**
 * CliOne - A simple creator of command line argument program.
 *
 * @package   CliOne
 * @author    Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @copyright Copyright (c) 2022 Jorge Patricio Castro Castillo. Dual Licence: MIT License and Commercial.
 *            Don't delete this comment, its part of the license.
 * @version   0.1
 * @link      https://github.com/EFTEC/CliOne
 */
class CliOne
{
    public $origin;
    /** @var CliOneParam[] */
    public $parameters = [];


    public function __construct($origin)
    {
        $this->origin = $origin;
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
     *                       If $key='*' then it reads all the first keys (without subkeys) and returns the first key
     *                       found if it has a value.
     * @param null   $subkey (optional) the subkey.
     * @return CliOneParam|false Returns false if not value is found.
     */
    public function evalParam($key = '*', $subkey = null)
    {
        $valueK=null;
        foreach ($this->parameters as $k => $v) {
            if (($v->key === $key || $key === '*') && $v->subkey === $subkey) {
                [$def,$v->value] = $this->getParameterCli($v->key,$v->subkey, $v->default);
                if ($key === '*' && $def ===false) {
                    // value not found, not asking for input.
                    continue;
                }

                if ($def === false) {
                    if ($v->input === true) {
                        $def=true;
                        $v->value = $this->inputCli($v->key,$v->subkey,'Select the value of ' . $v->keyFriendly, $v->default, $v->inputType, $v->inputValue);
                    }
                    if ($def===false || $v->value===false) {
                        $v->value = $v->default;
                        if ($v->required && $v->value === false) {
                            echo $this->messageCli("Field $v->key is missing", 'e');
                            $v->value = false;
                        }
                    }
                } else {
                    $ok=$this->validate($v->key,$v->subkey,$v->description,$v->default,$v->inputType,$v->inputValue,false,$v->value);
                    if(!$ok) {
                        $v->value=false;
                    }
                }
            }
            $valueK=$k;
            if($key==='*' && $v->value!==false) {
                // value found, exiting.
                break;
            }
        }
        if($valueK===false || $valueK===null) {
            return false;
        }
        return $this->parameters[$valueK]->getWithoutParent();
    }

    public function showparams()
    {
        foreach ($this->parameters as $k => $v) {
            if ($this->parameters[$k]->subkey === null) {
                $value = $this->parameters[$k]->value;
                if ($v->required && !$value) {
                    echo "  - " . $v->key . ' = [' . $v->default . '] '
                        . $this->messageCli("(value is required)", 'e', false) . "\n    "
                        . $this->messageCli($v->description, 'italic', false) . "\n";
                } else {
                    echo "  - " . $v->key . ' = [' . $v->default . '] '
                        . $this->messageCli($value, 'i', false) . "\n    "
                        . $this->messageCli($v->description, 'italic', false) . "\n";
                }
                foreach ($this->parameters as $k2 => $v2) {
                    $value = $this->parameters[$k2]->value;
                    if ($this->parameters[$k2]->key === $this->parameters[$k]->key && $this->parameters[$k2]->subkey !== null) {
                        if ($v2->required && !$value) {
                            echo "    - " . $v2->key . ' = [' . $v2->default . '] '
                                . $this->messageCli("(value is required)", 'e', false) . "\n      "
                                . $this->messageCli($v2->description, 'italic', false) . "\n";
                        } else {
                            echo "    - " . $v2->key . ' = [' . $v2->default . '] '
                                . $this->messageCli($value, 'i', false) . "\n      "
                                . $this->messageCli($v2->description, 'italic', false) . "\n";
                        }
                    }
                }
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
     * @param        $key
     * @param        $subkey
     * @param        $description
     * @param        $default
     * @param string $inputtype =['number','range','string','options','optionshort'][$i]
     * @param array  $inputvalue
     * @return mixed|string
     */
    public function inputCli($key,$subkey,$description, $default, $inputtype, $inputvalue)
    {
        $alternatives = null;
        $result = '';
        $input=null;
        if ($inputtype === 'options') {
            $multiple = true;
            $result = [];

            foreach ($inputvalue as $k => $v) {
                $result[$k] = true;
            }
        } else {
            $multiple = false;
        }
        do {
            if ($inputtype === 'options') {

                foreach ($inputvalue as $k => $v) {
                    echo ($result[$k] ? "[*]" : "[ ]") . "[" . ($k + 1) . "] $v\n";
                }
                echo "   [a] Select All, [n] Select none, [e] End selection, [*] (is marked as selected)\n";
            }
            if ($inputtype === 'option') {
                foreach ($inputvalue as $k => $v) {
                    echo "[" . ($k + 1) . "] $v\n";
                }
            }
            if ($alternatives !== null) {
                $opts = implode(',', $alternatives);
                $fail = true;
                while ($fail) {
                    $input = readline("$description ($opts): [$default] ");
                    $input = (!$input) ? $default : $input;
                    if (in_array($input, $alternatives, true)) {
                        $fail = false;
                    } else {
                        echo $this->messageCli("The value $key/$subkey is not correct", 'w');
                    }
                }
            } else {
                $this->validate($key,$subkey, $description,$default,$inputtype,$inputvalue,true,$input);
            }
            if ($inputtype === 'options') {
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
                    case '___input_e':
                        $multiple = false;
                        $resultOld = $result;
                        $result = [];
                        foreach ($resultOld as $k => $item) {

                            if ($item) {
                                $result[] = $k;
                            }
                        }
                        break;
                    default:
                        $pos= array_search($input,$inputvalue, true);
                        if($pos!==false) {
                            $result[$pos] = !$result[$pos];
                        } else {
                            echo "unknow selection $input\n";
                        }
                }
            } else {
                $result = $input;
            }

        } while ($multiple);

        return $result;
    }
    public function validate($key,$subkey,$description,$default,$inputtype,$inputvalue,$askInput=true,&$input=null) {
        $ok=false;
        while (!$ok) {
            switch ($inputtype) {
                case 'range':
                    $prefix=@$inputvalue[0].'-'.@$inputvalue[1];
                    break;
                case 'optionshort':
                    $prefix=implode('/',$inputvalue);
                    break;
                default:
                    $prefix='';
            }
            if($askInput) {
                $input = readline("$description [" . (is_array($default) ? implode(',', $default) : $default) . "] $prefix:");
                $input = (!$input) ? $default : $input;
                switch ($inputtype) {
                    case 'options':
                    case 'option':
                        if ($input === 'a' || $input === 'n' || $input === 'e') {
                            $input='___input_'.$input;
                        } else {
                            if ($input <= count($inputvalue)) {
                                $input = $inputvalue[$input -1];
                            } else {
                                $input = null;
                            }
                        }
                        break;
                }
            }
            switch ($inputtype) {
                case 'number':
                    $ok = is_numeric($input);
                    $cause='it must be a number';
                    break;
                case 'range':
                    $ok = is_numeric($input) && ($input >= @$inputvalue[0] && $input <= @$inputvalue[1]);
                    $cause='it must be a number between the range '.$inputvalue[0].' and '.$inputvalue[1];
                    break;
                case 'string':
                    $ok = is_string($input);
                    $cause='it must be a string';
                    break;
                case 'options':
                    if(strpos($input,',')===false) {
                        $validateValues=[$input];
                    }  else {
                        $validateValues=explode(',',$input);
                        $validateValues[]='e'; // to exit
                    }
                    foreach($validateValues as $inputTmp) {
                        if ($inputTmp === '___input_a' || $inputTmp === '___input_n' || $inputTmp === '___input_e') {
                            $ok = true;
                            break;
                        }

                        $ok= in_array($inputTmp, $inputvalue, true);
                        $cause = "it must be a valid value [$inputTmp]";
                        if(!$ok) {
                            break;
                        }
                    }
                    break;
                case 'option':
                case 'optionshort':
                    $ok = in_array($input, $inputvalue, true);
                    $cause="the option does not exist [$input]";
                    break;
                default:
                    $ok=false;
                    $cause='unknown $inputtype inputtype';

            }
            if(!$ok) {
                $key=$subkey??$key;
                echo $this->messageCli("The value $key is not correct, ".$cause, 'w');
            }
            if($askInput===false) {
                break;
            }
        }
        return $ok;
    }

    /**
     * @param array|string $str   The message to show
     * @param string       $color =['e','s','w','i',''][$i]
     * @param bool         $lineCarriage
     * @return string
     */
    public function messageCli($str, $color = '', $lineCarriage = true)
    {
        if (is_array($str)) {
            $str = implode(',', $str);
        }

        switch ($color) {
            case 'italic':
                $r = "\e[3m$str\e[0m";
                break;
            case 'e': //error
                $r = "\033[31m$str\033[0m";
                break;
            case 's': //success
                $r = "\033[32m$str\033[0m";
                break;
            case 'w': //warning
                $r = "\033[33m$str\033[0m";
                break;
            case 'g': //green
                $r = "\033[32m$str\033[0m";
                break;
            case 'i': //info
                $r = "\033[36m$str\033[0m";
                break;
            default:
                $r = "\033[0m$str\033[0m";
                break;
        }
        return $r . ($lineCarriage ? "\n" : '');
    }

    /**
     * @param           $key
     * @param           $subkey
     * @param bool      $default  is the defalut value is the parameter is set
     *                            without value.
     *
     * @return array it returns the value if the field is assigned<br>
     *                      the default value if the field exists, but it doesn't have value<br>
     *                      or false if the field is not defined
     */
    public function getParameterCli($key,$subkey, $default = false)
    {
        global $argv;
        $key=$subkey??$key;
        $p = array_search('-' . $key, $argv, true);
        if ($p === false) {
            return [false,false];
        }

        if (count($argv) > $p + 1) {
            $next=self::removeTrailSlash($argv[$p + 1]);
            if(strpos($next,'-', true)===0) {
                // -argument1 -argument2 (-argument1 is equals to "" and not -argument2)
                return [true,''];
            }
            return [true,$next];
        }
        if ($default !== '') {
            return [true,$default];
        }
        return [true,''];
    }


    public function createParam($key, $subkey = null)
    {
        return new CliOneParam($this, $key, $subkey);
    }

    protected static function removeTrailSlash($txt)
    {
        return rtrim($txt, '/\\');
    }
}
/*
*/
