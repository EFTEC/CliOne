<?php /** @noinspection ReturnTypeCanBeDeclaredInspection */
/** @noinspection PhpUnused */
/** @noinspection PhpMissingFieldTypeInspection */

namespace eftec\CliOne;
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
    protected $helpSyntax = [];
    protected $patterColumns;
    protected $patternQuestion;
    protected $footer;


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
     */
    public function setHelpSyntax(array $helpSyntax): CliOneParam
    {
        $this->helpSyntax = $helpSyntax;
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
     * $this->setPattern('<c>[{key}]</c> {value}','{desc} <c>[{def}]</c> {prefix}:','it is the footer');
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
     * It gets the pattern, patternquestion and footer
     * @return array=[pattern,patternquest,footer]
     */
    public function getPatterColumns()
    {
        return [$this->patterColumns, $this->patternQuestion, $this->footer];
    }


    /**
     * It resets the user input and marks the value as missing.
     * @return CliOneParam
     */
    public function resetInput()
    {
        //$this->input=true;
        $this->value = null;
        $this->valueKey = null;
        $this->currentAsDefault = false;
        $this->missing = true;
        return $this;
    }

    /**
     * The constructor. It is used internally
     * @param CliOne  $parent
     * @param ?string $key
     * @param bool    $isOperator
     */
    public function __construct($parent, $key = null, $isOperator = true, $value = null, $valueKey = null)
    {
        $this->parent = $parent;
        $this->key = $key;
        $this->isOperator = $isOperator;
        /** @noinspection ProperNullCoalescingOperatorUsageInspection */
        $this->question = $isOperator ?? $key;
        $this->value = $value;
        $this->valueKey = $valueKey;
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
     * if true then it set the current value as the default value but only if the value is not missing.<br>
     * The default value is assigned every time evalParam() is called.
     * @param bool $currentAsDefault
     * @return void
     */
    public function setCurrentAsDefault($currentAsDefault = true)
    {
        $this->currentAsDefault = $currentAsDefault;
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


    /**
     * It sets the input type
     * @param bool   $input     if true, then the value could be input via user. If false, the value could only be
     *                          entered as argument.
     * @param string $inputType =['number','range','string','password','multiple','multiple2','multiple3','multiple4','option','option2','option3','option4','optionshort'][$i]
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

    /**
     * It creates an argument and eval the parameter.<br>
     * It is a macro of add() and CliOne::evalParam()
     * @param bool $forceInput if false and the value is already digited, then it is not input anymore
     * @return CliOneParam|false|mixed
     */
    public function evalParam($forceInput = false)
    {
        $this->add(true);
        return $this->parent->evalParam($this->key, $forceInput);
    }

    /**
     * It adds an argument but it is not evaluated.
     * @param bool $override if false (default) and the argument exists, then it trigger an exception.<br>
     *                       if true and the argument exists, then it is replaced.
     * @return void
     */
    public function add($override = false): void
    {
        $fail = false;
        /*if($this->allowEmpty===true && $this->default===false) {
            $this->parent->showLine("<e>error in creation of input $this->key. setAllowEmpty() must be accompained by a default (not false) value</e>");
            $fail = true;

        }*/
        //'number','range','string','password','multiple','multiple2','multiple3','multiple4','option','option2','option3','option4','optionshort
        switch ($this->inputType) {
            case 'range':
                if (!is_array($this->inputValue) || count($this->inputValue) !== 2) {
                    $this->parent->showLine("<e>error in creation of input $this->key inputType for range must be an array</e>");
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
                    $this->parent->showLine("<e>error in creation of input $this->key inputType for $this->inputType must be an array</e>");
                    $fail = true;
                }
                break;
        }
        foreach ($this->parent->parameters as $numParam => $param) {
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
