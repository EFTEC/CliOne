<?php /** @noinspection PhpMissingFieldTypeInspection */

namespace Eftec\CliOne;

/**
 * CliOne - A simple creator of command line argument program.
 *
 * @package   CliOne
 * @author    Jorge Patricio Castro Castillo <jcastro arroba eftec dot cl>
 * @copyright Copyright (c) 2022 Jorge Patricio Castro Castillo. Dual Licence: MIT License and Commercial.
 *            Don't delete this comment, its part of the license.
 * @version   0.3
 * @link      https://github.com/EFTEC/CliOne
 */
class CliOneParam
{
    /** @var CliOne */
    private $parent;
    public $key;
    public $subkey;
    public $question = '';
    public $default=false;
    public $description = '';
    public $required = false;
    public $input = false;
    /**
     * @var string=['number','range','string','options','option','optionshort'][$i]
     */
    public $inputType = 'string';
    public $inputValue = [];
    public $value;

    /** @noinspection PhpUnused */
    public function getWithoutParent(): CliOneParam
    {
        $this->parent=null;
        return $this;
    }


    //,$keyFriendly=null,$default='',$description='',$required=false,$input=false,$inputtype='string',$inputvalue=[]

    /**
     * @param CliOne $parent
     * @param null   $key
     * @param null   $subkey
     */
    public function __construct($parent, $key = null, $subkey = null)
    {
        $this->parent = $parent;
        $this->key = $key;

        $this->subkey = $subkey;
        $this->question=$subkey?? $key;
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
     * @param mixed $description
     * @param null|string $question
     * @return CliOneParam
     */
    public function setDescription($description, $question = null): CliOneParam
    {
        $this->question = $question ?? 'Select the value of '.($this->subkey??$this->key);
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
     * @param string $inputType =['number','range','string','options'][$i]
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


    public function add(): void
    {
        //'number','range','string','options','option','optionshort
        switch ($this->inputType) {
            case 'range':
                if(!is_array($this->inputValue) || count($this->inputValue)!==2) {
                    echo "error in creation of input $this->key/$this->subkey inputType for range must be an array\n";
                }
                break;
            case 'options':
            case 'option':
            case 'optionshort':
                if(!is_array($this->inputValue)) {
                    echo "error in creation of input $this->key/$this->subkey inputType for $this->inputType must be an array\n";
                }
                break;
        }
        $this->parent->parameters[] = $this;
        $this->parent = null;
    }


}
