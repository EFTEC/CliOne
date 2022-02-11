<?php /** @noinspection ReturnTypeCanBeDeclaredInspection */
/** @noinspection PhpUnused */

/** @noinspection PhpMissingFieldTypeInspection */

namespace Eftec\CliOne;

/**
 * CliOne - A simple creator of command line argument program.
 *
 * @package   CliOne
 * @author    Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @copyright Copyright (c) 2022 Jorge Patricio Castro Castillo. Dual Licence: MIT License and Commercial.
 *            Don't delete this comment, its part of the license.
 * @version   0.5
 * @link      https://github.com/EFTEC/CliOne
 */
class CliOneParam
{
    /** @var CliOne */
    private $parent;
    public $key;
    public $isOperator = true;
    public $question = '';
    /** @var mixed */
    public $default = false;
    /** @var
     * bool if true then it allows empty values as valid values.<br>
     * However, "options" allows "empty" regardless of this option<br>
     * Also, if the default value is empty, then it is also allowed<br>
     */
    public $allowEmpty = false;
    public $description = '';
    public $required = false;
    public $input = false;
    /** @var bool if true then the value is not entered, but it could have a value (default value) */
    public $missing = true;

    /**
     * @var string=['number','range','string','options','option','option2','option3','optionshort'][$i]
     */
    public $inputType = 'string';
    public $inputValue = [];
    public $value;


    public function resetInput()
    {
        //$this->input=true;
        $this->value = null;
        $this->missing = true;
    }
    //,$keyFriendly=null,$default='',$description='',$required=false,$input=false,$inputtype='string',$inputvalue=[]

    /**
     * @param CliOne $parent
     * @param null   $key
     * @param bool   $isOperator
     */
    public function __construct($parent, $key = null, $isOperator = true, $value = null)
    {
        $this->parent = $parent;
        $this->key = $key;
        $this->isOperator = $isOperator;
        $this->question = $isOperator ?? $key;
        $this->value = $value;
    }


    /**
     * @param mixed $default
     * @return CliOneParam
     */
    public function setDefault($default): CliOneParam
    {
        $this->default = $default;
        return $this;
    }

    /**
     * It sets to allow empty values.<br>
     * If true, and the user inputs nothing, then the default value is never used (unless it is an option), and it returns an empty "".<br>
     * If false, and the user inputs nothing, then the default value is used.<br>
     * <b>Note</b>: If you are using an option, you are set a default value, and you enter nothing, then the default value is still used.
     * @param bool $allowEmpty
     * @return $this
     */
    public function setAllowEmpty($allowEmpty = true): CliOneParam
    {
        $this->allowEmpty = $allowEmpty;
        return $this;
    }

    /**
     * @param mixed       $description
     * @param null|string $question
     * @return CliOneParam
     */
    public function setDescription($description, $question = null): CliOneParam
    {
        $this->question = $question ?? "Select the value of $this->key";
        $this->description = $description;
        return $this;
    }


    /**
     * @param boolean $required
     * @return CliOneParam
     */
    public function setRequired($required = true): CliOneParam
    {
        $this->required = $required;
        return $this;
    }


    /**
     * @param bool   $input
     * @param string $inputType =['number','range','string','options','option','option2','option3','optionsimple'][$i]
     * @param mixed  $inputValue
     * @return CliOneParam
     */
    public function setInput($input = true, $inputType = 'string', $inputValue = null): CliOneParam
    {
        $this->input = $input;
        $this->inputType = $inputType;
        $this->inputValue = $inputValue;
        return $this;
    }

    public function evalParam($forceInput = false)
    {
        $this->add(true);
        return $this->parent->evalParam($this->key, $forceInput);
    }

    public function add($override = false): void
    {
        $fail = false;
        /*if($this->allowEmpty===true && $this->default===false) {
            $this->parent->showLine("<e>error in creation of input $this->key. setAllowEmpty() must be accompained by a default (not false) value</e>");
            $fail = true;

        }*/
        //'number','range','string','options','option','option2','option3','optionshort
        switch ($this->inputType) {
            case 'range':
                if (!is_array($this->inputValue) || count($this->inputValue) !== 2) {
                    $this->parent->showLine("<e>error in creation of input $this->key inputType for range must be an array</e>");
                    $fail = true;
                }
                break;
            case 'options':
            case 'option':
            case 'option2':
            case 'option3':
            case 'optionshort':
                if (!is_array($this->inputValue)) {
                    $this->parent->showLine("<e>error in creation of input $this->key inputType for $this->inputType must be an array</e>");
                    $fail = true;
                }
                break;
        }
        foreach ($this->parent->parameters as $numParam=>$param) {
            if ($param->key === $this->key) {
                if ($override) {
                    // override
                    $this->parent->parameters[$numParam] = $this;
                    //$this->parent->parameters[$numParam]->parent=null;
                    return;
                }
                $this->parent->showLine("<e>error in creation of input $this->key, parameter already defined</e>");
                $fail = true;
                break;
            }
        }
        if (!$fail) {
            $this->parent->parameters[] = $this;
            //$this->parent = null;
        }
    }


}
