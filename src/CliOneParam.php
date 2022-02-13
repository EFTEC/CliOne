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
    public $question = '';
    /** @var mixed */
    public $default = false;
    public $currentAsDefault=false;
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

    /**
     * @return array
     */
    public function getHelpSyntax(): array
    {
        return $this->helpSyntax;
    }

    /**
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
     * <li><b>{valuenext}</b> (for table)if the value is an array then it shows the next value (it could be the same, the second or the last one)</li>
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
    public function setPattern($patterColumns=null, $patterQuestion=null, $footer=null) : CliOneParam
    {
        $this->patterColumns=$patterColumns;
        $this->patternQuestion=$patterQuestion;
        $this->footer=$footer;
        return $this;
    }

    /**
     * It gets the pattern, patternquestion and footer
     * @return array=[pattern,patternquest,footer]
     */
    public function getPatterColumns() {
        return [$this->patterColumns,$this->patternQuestion, $this->footer];
    }

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


    public function resetInput()
    {
        //$this->input=true;
        $this->value = null;
        $this->valueKey = null;
        $this->currentAsDefault=false;
        $this->missing = true;
    }
    //,$keyFriendly=null,$default='',$description='',$required=false,$input=false,$inputtype='string',$inputvalue=[]

    /**
     * @param CliOne $parent
     * @param null   $key
     * @param bool   $isOperator
     */
    public function __construct($parent, $key = null, $isOperator = true, $value = null, $valueKey = null)
    {
        $this->parent = $parent;
        $this->key = $key;
        $this->isOperator = $isOperator;
        $this->question = $isOperator ?? $key;
        $this->value = $value;
        $this->valueKey = $valueKey;
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
     * if true then it set the current value as the default value but only if the value is not missing.<br>
     * The default value is assigned every time evalParam() is called.
     * @param bool $currentAsDefault
     * @return void
     */
    public function setCurrentAsDefault($currentAsDefault=true) {
        $this->currentAsDefault=$currentAsDefault;
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
     * @param string      $description
     * @param null|string $question
     * @param string[]    $helpSyntax It adds one or multiple lines of help syntax.
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
     * @param string $inputType =['number','range','string','password','multiple','multiple2','multiple3','multiple4','option','option2','option3','option4','optionsimple'][$i]
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
