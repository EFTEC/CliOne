<?php

namespace eftec\CliOne;
/**
 * CliOne - A simple creator of command line argument program.
 *
 * @package   CliOne
 * @author    Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @copyright Copyright (c) 2022 Jorge Patricio Castro Castillo. Dual Licence: MIT License and Commercial.
 *            Don't delete this comment, its part of the license.
 * @version   1.5.5
 * @link      https://github.com/EFTEC/CliOne
 */
class CliOneParam
{
    /**
     * The key of the parameter. If null then the parameter is invalid.
     * @var string|null
     */
    public $key;
    /** @var string=['first','last','second','flag','longflag','onlyinput','none'][$i] */
    public $type;
    public $alias = [];
    /**
     * @var bool|string|null
     */
    public $question = '';
    /** @var mixed */
    public $default = false;
    public $currentAsDefault = false;
    /** @var
     * bool if true then it allows empty values as valid values.<br>
     * However, "multiple" allows "empty" regardless of this option<br>
     * Also, if the default value is empty, then it is also allowed<br>
     */
    public $allowEmpty = false;
    public $description = '';
    public $required = false;
    public $input = false;
    /** @var bool if true then the value is not entered, but it could have a value (default value) */
    public $missing = true;
    /**
     * @var string=['number','range','string','password','multiple','multiple2','multiple3','multiple4','option','option2','option3','option4','optionshort'][$i]
     */
    public $inputType = 'string';
    public $inputValue = [];
    public $value;
    public $valueKey;
    protected $addHistory = false;
    protected $helpSyntax = [];
    protected $patterColumns;
    protected $patternQuestion;
    protected $footer;
    protected $history = [];
    /** @var CliOne */
    private $parent;

    /**
     * The constructor. It is used internally
     * @param CliOne       $parent
     * @param ?string      $key
     * @param bool         $type =['first','last','second','flag','longflag','onlyinput','none'][$i]
     * @param array|string $alias
     * @param null         $value
     * @param null         $valueKey
     */
    public function __construct($parent, $key = null, $type = true, $alias = [], $value = null, $valueKey = null)
    {
        $this->parent = $parent;
        $this->key = $key;
        $this->type = $type;
        $this->alias = is_array($alias) ? $alias : [$alias];
        /** @noinspection ProperNullCoalescingOperatorUsageInspection */
        $this->question = $type ?? $key;
        $this->value = $value;
        $this->valueKey = $valueKey;
    }

    /**
     * Return the if the parameter is valid (if the key is not null).
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->key!==null;
    }

    /**
     * It adds an argument but it is not evaluated.
     * @param bool $override if false (default) and the argument exists, then it trigger an exception.<br>
     *                       if true and the argument exists, then it is replaced.
     * @return bool
     */
    public function add($override = false): bool
    {
        if($this->key===null) {
            if(!$this->parent->isSilentError()) {
                $this->parent->showCheck('ERROR', 'red', "error in creation of input $this->key inputType for range must be an array");
            }
            return false;
        }
        if ($this->type === 'none') {
            $override = true;
        }
        $fail = false;
        /*if($this->allowEmpty===true && $this->default===false) {
            $this->parent->showLine("<red>error in creation of input $this->key. setAllowEmpty() must be accompained by a default (not false) value</red>");
            $fail = true;

        }*/
        //'number','range','string','password','multiple','multiple2','multiple3','multiple4','option','option2','option3','option4','optionshort
        switch ($this->inputType) {
            case 'range':
                if (!is_array($this->inputValue) || count($this->inputValue) !== 2) {
                    if(!$this->parent->isSilentError()) {
                        $this->parent->showCheck('ERROR', 'red', "error in creation of input $this->key inputType for range must be an array");
                    }
                    $fail = true;
                }
                break;
            case 'multiple':
            case 'multiple2':
            case 'multiple3':
            case 'multiple4':
            case 'option':
            case 'option2':
            case 'option3':
            case 'option4':
            case 'optionshort':
                if (!is_array($this->inputValue)) {
                    if(!$this->parent->isSilentError()) {
                        $this->parent->showCheck('ERROR', 'red', "error in creation of input $this->key inputType for $this->inputType must be an array");
                    }
                    $fail = true;
                }
                break;
        }
        foreach ($this->parent->parameters as $keyParam => $parameter) {
            if ($parameter->key === $this->key) {
                if ($override) {
                    // override
                    $this->parent->parameters[$keyParam] = $this;
                    //$this->parent->parameters[$keyParam]->parent=null;
                    return true;
                }
                if(!$this->parent->isSilentError()) {
                    $this->parent->showCheck('ERROR', 'red',
                        "error in creation of input $this->key,parameter already defined");
                }
                $fail = true;
                break;
            }
            if (in_array($this->key, $parameter->alias, true)) {
                // we found an alias that matches the parameter.
                if(!$this->parent->isSilentError()) {
                    $this->parent->showCheck('ERROR', 'red',
                        "error in creation of input $this->key,parameter already defined as an alias");
                }
                $fail = true;
                break;
            }
            foreach($this->alias as $alias) {
                if(($alias === $parameter->key) && !$this->parent->isSilentError()) {
                    $this->parent->showCheck('ERROR', 'red',
                        "error in creation of alias $this->key/$alias,parameter already defined");
                }
                if (in_array($alias, $parameter->alias, true)) {
                    // we found an alias that matches the parameter.
                    if(!$this->parent->isSilentError()) {
                        $this->parent->showCheck('ERROR', 'red',
                            "error in creation of alias $this->key/$alias,parameter already defined as other alias");
                    }
                    $fail = true;
                    break;
                }
            }
        }
        if (!$fail) {
            $this->parent->parameters[] = $this;
            //$this->parent = null;
        }
        return !$fail;
    }

    public function setAddHistory($add = true): CliOneParam
    {
        $this->addHistory = $add;
        return $this;
    }


    /**
     * It creates an argument and eval the parameter.<br>
     * It is a macro of add() and CliOne::evalParam()
     * @param bool $forceInput  if false and the value is already digited, then it is not input anymore
     * @param bool $returnValue If true, then it returns the value obtained.<br>
     *                          If false (default value), it returns an instance of CliOneParam.
     * @return mixed
     */
    public function evalParam($forceInput = false, $returnValue = false)
    {
        $this->add(true);
        return $this->parent->evalParam($this->key, $forceInput, $returnValue);
    }

    /**
     * It returns the syntax of the help.
     * @return array
     */
    public function getHelpSyntax(): array
    {
        return $this->helpSyntax;
    }

    /**
     * It sets the syntax of help.
     * @param array $helpSyntax
     * @return CliOneParam
     * @noinspection PhpUnused
     */
    public function setHelpSyntax(array $helpSyntax): CliOneParam
    {
        $this->helpSyntax = $helpSyntax;
        return $this;
    }

    /**
     * @return array
     */
    public function getHistory(): array
    {
        return $this->history;
    }

    /**
     * It sets the local history. It could be used to autocomplete.
     * @param array $history
     * @return CliOneParam
     */
    public function setHistory(array $history): CliOneParam
    {
        $this->history = $history;
        return $this;
    }

    /**
     * It gets the pattern, patternquestion and footer
     * @return array=[pattern,patternquest,footer]
     */
    public function getPatterColumns(): array
    {
        return [$this->patterColumns, $this->patternQuestion, $this->footer];
    }

    /**
     * true if the evaluation of this parameter is added automatically in the global history
     * @return bool
     */
    public function isAddHistory(): bool
    {
        return $this->addHistory;
    }



    /**
     * It resets the user input and marks the value as missing.
     * @return CliOneParam
     * @noinspection PhpUnused
     */
    public function resetInput(): CliOneParam
    {
        //$this->input=true;
        $this->value = null;
        $this->valueKey = null;
        $this->currentAsDefault = false;
        $this->missing = true;
        return $this;
    }

    /**
     * It sets to allow empty values.<br>
     * If true, and the user inputs nothing, then the default value is never used (unless it is an option), and it
     * returns an empty "".<br> If false, and the user inputs nothing, then the default value is used.<br>
     * <b>Note</b>: If you are using an option, you are set a default value, and you enter nothing, then the default
     * value is still used.
     * @param bool $allowEmpty
     * @return $this
     */
    public function setAllowEmpty($allowEmpty = true): CliOneParam
    {
        $this->allowEmpty = $allowEmpty;
        return $this;
    }

    /**
     * if true then it set the current value as the default value but only if the value is not missing.<br>
     * The default value is assigned every time evalParam() is called.
     * @param bool $currentAsDefault
     * @return CliOneParam
     */
    public function setCurrentAsDefault($currentAsDefault = true): CliOneParam
    {
        $this->currentAsDefault = $currentAsDefault;
        return $this;
    }

    /**
     * It sets the default value that it is used when the user doesn't input the value<br>
     * Setting a default value could bypass the option isRequired()
     * @param mixed $default
     * @return CliOneParam
     */
    public function setDefault($default): CliOneParam
    {
        $this->default = $default;
        return $this;
    }

    /**
     * It sets the description
     * @param string      $description the initial description (used when we show the syntax)
     * @param null|string $question    The question, it is used in the user input.
     * @param string[]    $helpSyntax  It adds one or multiple lines of help syntax.
     * @return CliOneParam
     */
    public function setDescription($description, $question = null, $helpSyntax = []): CliOneParam
    {
        $this->question = $question ?? "Select the value of $this->key";
        $this->description = $description;
        $this->helpSyntax = $helpSyntax;
        return $this;
    }

    /**
     * It sets the input type
     * <b>Example:</b><br>
     * <pre>
     * $this->createParam('selection')->setInput(true,'optionsimple',['yes','no'])->add();
     * $this->createParam('name')->setInput(true,'string')->add();
     * $this->createParam('many')->setInput(true,'multiple3',['op1','op2,'op3'])->add();
     * </pre>
     *
     * @param bool   $input     if true, then the value could be input via user. If false, the value could only be
     *                          entered as argument.
     * @param string $inputType =['number','range','string','password','multiple','multiple2','multiple3','multiple4','option','option2','option3','option4','optionshort'][$i]
     * @param mixed  $inputValue Depending on the $inputtype, you couls set the list of values.<br>
     *                           This value allows string, arrays and associative arrays<br>
     *                           The values indicated here are used for input and validation<br>
     *                           The library also uses this value for the auto-complete feature (tab-key).
     * @param array  $history   you can add a custom history for this parameter
     * @return CliOneParam
     */
    public function setInput($input = true, $inputType = 'string', $inputValue = null, $history = []): CliOneParam
    {
        $this->input = $input;
        $this->inputType = $inputType;
        $this->inputValue = $inputValue;
        $this->history = $history;
        return $this;
    }

    /**
     * It sets the visual pattern<br>
     * <ul>
     * <li><b>{selection}</b> (for table) used by "multiple", it shows if the value is selected or not</li>
     * <li><b>{key}</b> (for table)it shows the current key</li>
     * <li><b>{value}</b> (for table)it shows the current value. If the value is an array then it is "json"</li>
     * <li><b>{valueinit}</b> (for table)if the value is an array then it shows the first value</li>
     * <li><b>{valuenext}</b> (for table)if the value is an array then it shows the next value (it could be the same,
     * the second or the last one)</li>
     * <li><b>{valueend}</b> (for table)if the value is an array then it shows the last value</li>
     * <li><b>{desc}</b> it shows the description</li>
     * <li><b>{def}</b> it shows the default value</li>
     * <li><b>{prefix}</b> it shows a prefix</li>
     * </ul>
     * <b>Example:</b><br>
     * <pre>
     * $this->setPattern('<cyan>[{key}]</cyan> {value}','{desc} <cyan>[{def}]</cyan> {prefix}:','it is the footer');
     * </pre>
     *
     * @param ?string $patterColumns  if null then it will use the default value.
     * @param ?string $patterQuestion the pattern of the question.
     * @param ?string $footer         the footer line (if any)
     * @return $this
     */
    public function setPattern($patterColumns = null, $patterQuestion = null, $footer = null): CliOneParam
    {
        $this->patterColumns = $patterColumns;
        $this->patternQuestion = $patterQuestion;
        $this->footer = $footer;
        return $this;
    }

    /**
     * It marks the value as required<br>
     * The value could be ignored if it used together with setDefault()
     * @param boolean $required
     * @return CliOneParam
     */
    public function setRequired($required = true): CliOneParam
    {
        $this->required = $required;
        return $this;
    }


}
