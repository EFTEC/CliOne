# Table of contents

- [Classes](#classes)
- [Methods CliOne](#methods-clione)
    - [Parameters:](#parameters)
  - [Method findVendorPath()](#method-findvendorpath)
    - [Parameters:](#parameters)
  - [Method createParam()](#method-createparam)
    - [Parameters:](#parameters)
  - [Method downLevel()](#method-downlevel)
  - [Method evalParam()](#method-evalparam)
    - [Parameters:](#parameters)
  - [Method getArrayParams()](#method-getarrayparams)
    - [Parameters:](#parameters)
  - [Method getColSize()](#method-getcolsize)
  - [Method getParameter()](#method-getparameter)
    - [Parameters:](#parameters)
  - [Method getValue()](#method-getvalue)
    - [Parameters:](#parameters)
  - [Method getValueKey()](#method-getvaluekey)
    - [Parameters:](#parameters)
  - [Method isCli()](#method-iscli)
  - [Method readData()](#method-readdata)
    - [Parameters:](#parameters)
  - [Method readParameterArgFlag()](#method-readparameterargflag)
    - [Parameters:](#parameters)
  - [Method saveData()](#method-savedata)
    - [Parameters:](#parameters)
  - [Method setAlign()](#method-setalign)
    - [Parameters:](#parameters)
  - [Method setArrayParam()](#method-setarrayparam)
    - [Parameters:](#parameters)
  - [Method setColor()](#method-setcolor)
  - [Method setParam()](#method-setparam)
    - [Parameters:](#parameters)
  - [Method setPatternTitle()](#method-setpatterntitle)
    - [Parameters:](#parameters)
  - [Method setPatternCurrent()](#method-setpatterncurrent)
    - [Parameters:](#parameters)
  - [Method setPatternSeparator()](#method-setpatternseparator)
    - [Parameters:](#parameters)
  - [Method setPatternContent()](#method-setpatterncontent)
    - [Parameters:](#parameters)
  - [Method setStyle()](#method-setstyle)
    - [Parameters:](#parameters)
  - [Method show()](#method-show)
    - [Parameters:](#parameters)
  - [Method showBread()](#method-showbread)
  - [Method showCheck()](#method-showcheck)
    - [Parameters:](#parameters)
  - [Method showFrame()](#method-showframe)
    - [Parameters:](#parameters)
  - [Method showLine()](#method-showline)
    - [Parameters:](#parameters)
  - [Method showMessageBox()](#method-showmessagebox)
    - [Parameters:](#parameters)
  - [Method showParamSyntax()](#method-showparamsyntax)
    - [Parameters:](#parameters)
  - [Method showProgressBar()](#method-showprogressbar)
    - [Parameters:](#parameters)
  - [Method showTable()](#method-showtable)
    - [Parameters:](#parameters)
  - [Method showValuesColumn()](#method-showvaluescolumn)
    - [Parameters:](#parameters)
  - [Method showWaitCursor()](#method-showwaitcursor)
  - [Method showparams()](#method-showparams)
  - [Method strlen()](#method-strlen)
    - [Parameters:](#parameters)
  - [Method removechar()](#method-removechar)
    - [Parameters:](#parameters)
  - [Method substr()](#method-substr)
  - [Method upLevel()](#method-uplevel)
    - [Parameters:](#parameters)
  - [Method replaceColor()](#method-replacecolor)
    - [Parameters:](#parameters)
  - [Method replaceCurlyVariable()](#method-replacecurlyvariable)
    - [Parameters:](#parameters)
- [Methods CliOneParam](#methods-clioneparam)
  - [Method getHelpSyntax()](#method-gethelpsyntax)
  - [Method setHelpSyntax()](#method-sethelpsyntax)
    - [Parameters:](#parameters)
  - [Method setPattern()](#method-setpattern)
    - [Parameters:](#parameters)
  - [Method getPatterColumns()](#method-getpattercolumns)
  - [Method resetInput()](#method-resetinput)
  - [Method __construct()](#method-__construct)
    - [Parameters:](#parameters)
  - [Method setDefault()](#method-setdefault)
    - [Parameters:](#parameters)
  - [Method setCurrentAsDefault()](#method-setcurrentasdefault)
    - [Parameters:](#parameters)
  - [Method setAllowEmpty()](#method-setallowempty)
    - [Parameters:](#parameters)
  - [Method setDescription()](#method-setdescription)
    - [Parameters:](#parameters)
  - [Method setRequired()](#method-setrequired)
    - [Parameters:](#parameters)
  - [Method setInput()](#method-setinput)
    - [Parameters:](#parameters)
  - [Method evalParam()](#method-evalparam)
    - [Parameters:](#parameters)
  - [Method add()](#method-add)
    - [Parameters:](#parameters)



## CliOne
CliOne - A simple creator of command-line argument program.
### Field autocomplete ()

### Field emptyValue ()

### Field origin ()

### Field parameters (CliOneParam[])

### Field echo (If)
<b>true</b> (default value), then the values are echo automatically on the screen.<br>
If <b>false</b>, then the values are stored into the memory.<br>
You can access to the memory using getMemory(), setMemory()<br>

### Method isSilentError()


### Method getMemory()


### Method testArguments()
It is used for testing. You can simulate arguments using this function<br>
This function must be called before the creation of the instance
#### Parameters:
* **$arguments** param array $arguments (array)

### Method testUserInput()
It is used for testing. You can simulate user-input using this function<br>
This function must be called before every interactivity<br>
This function is not resetted automatically, to reset it, set $userInput=null<br>
#### Parameters:
* **$userInput** param ?array $userInput (?array)

### Method setMemory()


### Method setSilentError()

#### Parameters:
* **$silentError** param bool $silentError (bool)
### Field noColor (bool)
if true then it will not show colors
### Field colorTags ()

### Field styleTextTags ()

### Field columnTags ()

### Field colorEscape ()

### Field styleTextEscape (string[])
note, it must be 2 digits
### Field columnEscape ()


### Method __construct()
The constructor
#### Parameters:
* **$origin** you can specify the origin file. If you specify the origin file, then isCli will only
return true if the file is called directly. (?string)

### Method findVendorPath()
It finds the vendor path starting from a route. The route must be inside the application path.
#### Parameters:
* **$initPath** the initial path, example __DIR__, getcwd(), 'folder1/folder2'. If null, then
__DIR__ (?string)

### Method createParam()
It creates a new parameter to be read from the command line and/or to be input manually by the user<br>
<b>Example:</b><br>
<pre>
$this->createParam('k1','first'); // php program.php thissubcommand
$this->createParam('k1','flag',['flag2','flag3']); // php program.php -k1 <val> or --flag2 <val> or --flag3
<val>
</pre>
#### Parameters:
* **$key** The key or the parameter. It must be unique. (string)
* **$alias** A simple array with the name of the arguments to read (without - or
<b>flag</b>: (default) it reads a flag "php program.php -thisflag
value"<br>
<b>first</b>: it reads the first argument "php program.php thisarg"
(without value)<br>
<b>second</b>: it reads the second argument "php program.php sc1
thisarg" (without value)<br>
<b>last</b>: it reads the second argument "php program.php ... thisarg"
(without value)<br>
<b>longflag</b>: it reads a longflag "php program --thislongflag
value<br>
<b>last</b>: it reads the second argument "php program.php ...
thisvalue" (without value)<br>
<b>onlyinput</b>: the value means to be user-input, and it is stored<br>
<b>none</b>: the value it is not captured via argument, so it could be
user-input, but it is not stored<br> none parameters could always be
overridden, and they are used to "temporary" input such as validations
(y/n). (array|string)
* **$type** =['first','last','second','flag','longflag','onlyinput','none'][$i]<br>
--)<br> if the type is a flag, then the alias is a double flag "--".<br>
if the type is a double flag, then the alias is a flag. (string)
* **$argumentIsValueKey** <b>true</b> the argument is value-key<br>
<b>false</b> (default) the argument is a value (bool)

### Method downLevel()
Down a level in the breadcrub.<br>
If down more than the number of levels available, then it clears the stack.
#### Parameters:
* **$number** number of levels to down. (int)

### Method evalParam()
It evaluates the parameters obtained from the syntax of the command.<br>
The parameters must be defined before call this method<br>
<b>Example:</b><br>
<pre>
// shell:
php mycode.php -argument1 hello -argument2 world
// php code:
$t=new CliOne('mycode.php');
$t->createParam('argument1')->add();
$result=$t->evalParam('argument1'); // an object ClieOneParam where value is "hello"
</pre>
#### Parameters:
* **$key** the key to read.<br>
If $key='*' then it reads the first flag and returns its value (if any). (string)
* **$forceInput** it forces input no matter if the value is already inserted. (bool)
* **$returnValue** If true, then it returns the value obtained.<br>
If false (default value), it returns an instance of CliOneParam. (bool)

### Method addHistory()
Add a value to the history
#### Parameters:
* **$prompt** the value(s) of the history to add (string|array)

### Method setHistory()
It sets the history (deleting the old history) with the new values
#### Parameters:
* **$prompt** param string|array $prompt (string|array)

### Method clearHistory()


### Method listHistory()


### Method getArrayParams()
It returns an associative array with all the parameters of the form [key=>value]<br>
Parameters of the type "none" are ignored<br>
#### Parameters:
* **$excludeKeys** you can add a key that you want to exclude. (array)

### Method getColSize()
It returns the number of columns present on the screen. The columns are calculated in the constructor.

### Method getParameter()
It gets the parameter by the key or false if not found.
#### Parameters:
* **$key** the key of the parameter (string)

### Method getValue()
It reads a value of a parameter.
<b>Example:</b><bt>
<pre>
// [1] option1
// [2] option2
// select a value [] 2
$v=$this->getValueKey('idparam'); // it will return "option2".
</pre>
#### Parameters:
* **$key** the key of the parameter to read the value (string)

### Method readParameterArgFlag()
It reads a parameter as an argument or flag.
#### Parameters:
* **$parameter** param CliOneParam $parameter (CliOneParam)

### Method getValueKey()
It reads the value-key of a parameter selected. It is useful for a list of elements.<br>
<b>Example:</b><br>
<pre>
// [1] option1
// [2] option2
// select a value [] 2
$v=$this->getValueKey('idparam'); // it will return 2 instead of "option2"
</pre>
#### Parameters:
* **$key** the key of the parameter to read the value-key (string)

### Method isCli()
It will return true if the PHP is running on CLI<br>
If the constructor specified a file, then it is also used for validation.
<b>Example:</b><br>
<pre>
// page.php:
$inst=new CliOne('page.php'); // this security avoid calling the cli when this file is called by others.
if($inst->isCli()) {
echo "Is CLI and the current page is page.php";
}
</pre>

### Method readData()
It reads information from a file. The information will be de-serialized.
#### Parameters:
* **$filename** the filename with or without extension. (string)

### Method isParameterPresent()
Returns true if the parameter is present with or without data.<br>
The parameter is not changed, neither the default values nor user input are applied<br>
Returned Values:<br>
<ul>
<li><b>none</b> the value is not present, ex: </li>
<li><b>empty</b> the value is present but is empty, ex: -arg1</li>
<li><b>value</b> the value is present, and it has a value, ex: -arg1 value</li>
</ul>
#### Parameters:
* **$key** param string $key (string)

### Method saveData()
It saves the information into a file. The content will be serialized.
#### Parameters:
* **$filename** the filename (without extension) to where the value will be saved. (string)
* **$content** The content to save. It will be serialized. (mixed)

### Method setAlign()
It sets the alignment.  This method is stackable.<br>
<b>Example:</b><br>
<pre>
$cli->setAlign('left','left','right')->setStyle('double')->showTable($values);
</pre>>
#### Parameters:
* **$title** =['left','right','middle'][$i] the alignment of the title (string)
* **$content** =['left','right','middle'][$i] the alignment of the content (string)
* **$contentNumeric** =['left','right','middle'][$i] the alignment of the content (numeric) (string)

### Method setArrayParam()
It sets the parameters using an array of the form [key=>value]<br>
It also marks the parameters as missing=false
#### Parameters:
* **$array** the associative array to use to set the parameters. (array)
* **$excludeKeys** you can add a key that you want to exclude.<br>
If the key is in the array and in this list, then it is excluded (array)
* **$includeKeys** the whitelist of elements that only could be included.<br>
Only keys that are in this list are added. (array|null)

### Method setColor()
It sets the color in the stack
#### Parameters:
* **$colors** =['black','green','yellow','cyan','magenta','blue'][$i] (array)

### Method setParam()
It sets the value of a parameter manually.<br>
If the value is present as argument, then the value of the argument is used<br>
If the value is not present as argument, then the user input is skipped.
#### Parameters:
* **$key** the key of the parameter (string)
* **$value** the value to assign. (mixed)

### Method setPatternTitle()
{value} {type}
#### Parameters:
* **$pattern1Stack** if null then it will use the default value. (?string)

### Method setPatternCurrent()
<bold>{value}{type}</bold>
#### Parameters:
* **$pattern2Stack** if null then it will use the default value. (?string)

### Method setPatternSeparator()
">"
#### Parameters:
* **$pattern3Stack** if null then it will use the default value. (?string)

### Method setPatternContent()
Not used yet.
#### Parameters:
* **$pattern4Stack** if null then it will use the default value. (?string)

### Method setStyle()

#### Parameters:
* **$style** =['mysql','simple','double','minimal'][$i] (string)

### Method show()
It's similar to showLine, but it keeps in the current line.
#### Parameters:
* **$content** param string $content (string)

### Method showBread()
It shows a breadcrumb.<br>
To add values you could use the method uplevel()<br>
To remove a value (going down a level) you could use the method downlevel()<br>
You can also change the style using setPattern1(),setPattern2(),setPattern3()<br>
<pre>
$cli->setPattern1('{value}{type}') // the level
->setPattern2('<bred>{value}</bred>{type}') // the current level
->setPattern3(' -> ') // the separator
->showBread();
</pre>
It shows the current BreadCrumb if any.
#### Parameters:
* **$showIfEmpty** if true then it shows the breadcrumb even if it is empty (empty line)<br>
if false (default) then it doesn't show the breadcrumb if it is empty. (bool)

### Method showCheck()
It shows a label messages in a single line, example: <color>[ERROR]</color> Error message
#### Parameters:
* **$label** param string $label (string)
* **$color** =['black','green','yellow','cyan','magenta','blue'][$i] (string)
* **$content** param string $content (string)

### Method showFrame()
It shows a border frame.
#### Parameters:
* **$lines** the content. (string|string[])
* **$titles** if null then no title. (string|string[]|null)

### Method showLine()
It shows (echo) a colored line. The syntax of the color is similar to html as follows:<br>
<pre>
<red>error</red> (color red)
<yellow>warning</yellow> (color yellow)
<blue>information</blue> (blue)
<yellow>yellow</yellow> (yellow)
<green>green</green> <green>success</green> (color green)
<italic>italic</italic>
<bold>bold</bold>
<dim>dim</dim>
<underline>underline</underline>
<cyan>cyan</cyan> (color light cyan)
<magenta>magenta</magenta> (color magenta)
<col0/><col1/><col2/><col3/><col4/><col5/>  columns. col0=0 (left),col1--col5 every column of the page.
<option/> it shows all the options available (if the input has some options)
</pre>
#### Parameters:
* **$content** content to display (string)
* **$cliOneParam** param ?CliOneParam $cliOneParam (?CliOneParam)

### Method showMessageBox()

#### Parameters:
* **$lines** param string|string[] $lines (string|string[])
* **$titles** param string|string[] $titles (string|string[])

### Method alignLinesMiddle()


### Method showParamSyntax()
It shows the syntax of a parameter.
#### Parameters:
* **$key** the key to show. "*" means all keys. (string)
* **$tab** the first separation. Values are between 0 and 5. (int)
* **$tab2** the second separation. Values are between 0 and 5. (int)
* **$excludeKey** the keys to exclude. It must be an indexed array with the keys to skip. (array)

### Method showParamSyntax2()
It shows the syntax of the parameters.
#### Parameters:
* **$title** A title (optional) (?string)
* **$typeParam** =['first','last','second','flag','longflag','onlyinput','none'][$i] the type of
parameter (array)
* **$excludeKey** the keys to exclude (array)
* **$size** the minimum size of the first column (?int)

### Method wrapLine()
it wraps a line and returns one or multiples lines<br>
The lines wrapped does not open or close tags.
#### Parameters:
* **$text** param string $text (string)
* **$width** param int $width (int)

### Method showProgressBar()

#### Parameters:
* **$currentValue** the current value (numeric)
* **$max** the max value to fill the bar. (numeric)
* **$columnWidth** the size of the bar (in columns) (int)
* **$currentValueText** the current value to display at the left.<br>
if null then it will show the current value (with a space in between) (?string)

### Method showTable()
It shows an associative array.  This command is the end of a stack.
#### Parameters:
* **$assocArray** An associative array with the values to show. The key is used for the index. (array)

### Method showValuesColumn()
It shows the values as columns.
#### Parameters:
* **$values** the values to show. It could be an associative array or an indexed array. (array)
* **$type** ['multiple','multiple2','multiple3','multiple4','option','option2','option3','option4'][$i] (string)
* **$patternColumn** the pattern to be used, example: "<cyan>[{key}]</cyan> {value}" (?string)

### Method showWaitCursor()
It shows a waiting cursor.
#### Parameters:
* **$init** the first time this method is called, you must set this value as true. Then, every
update must be false. (bool)
* **$postfixValue** if you want to set a profix value such as percentage, advance, etc. (string)

### Method showparams()
It will show all the parameters by showing the key, the default value and the value<br>
It is used for debugging and testing.

### Method showParamValue()

#### Parameters:
* **$parameter** param CliOneParam $parameter (CliOneParam)

### Method strlen()
It determines the size of a string
#### Parameters:
* **$content** param $content ()
* **$visual** visual means that it considers the visual lenght, false means it considers characters. (bool)

### Method removechar()
remove visible characters at the end of the string. It ignores invisible (such as colors) characters.
#### Parameters:
* **$content** param string $content (string)
* **$numchar** param int $numchar (int)

### Method upLevel()
Up a level in the breadcrumb
#### Parameters:
* **$content** the content of the new line (string)
* **$type** the type of the content (optional) (string)

### Method colorText()
It sets the color of the cli<br>
<pre>
<red>error</red> (color red)
<yellow>warning</yellow> (color yellow)
<blue>information</blue> (blue)
<yellow>yellow</yellow> (yellow)
<green>green</green>  (color green)
<italic>italic</italic>
<bold>bold</bold>
<underline>underline</underline>
<strikethrough>strikethrough</strikethrough>
<cyan>cyan</cyan> (color light cyan)
<magenta>magenta</magenta> (color magenta)
<col0/><col1/><col2/><col3/><col4/><col5/>  columns. col0=0 (left),col1--col5 every column of the page.
<option/> it shows all the options available (if the input has some options)
</pre>
#### Parameters:
* **$content** param string $content (string)
* **$cliOneParam** param ?CliOneParam $cliOneParam (?CliOneParam)

### Method colorLess()
It removes all the escape characters of a content
#### Parameters:
* **$content** param string $content (string)

### Method colorMask()
It masks (with a char 250) all the escape characters.
#### Parameters:
* **$content** param $content ()

### Method replaceCurlyVariable()
Replaces all variables defined between {{ }} by a variable inside the dictionary of values.<br>
Example:<br>
replaceCurlyVariable('hello={{var}}',['var'=>'world']) // hello=world<br>
replaceCurlyVariable('hello={{var}}',['varx'=>'world']) // hello=<br>
replaceCurlyVariable('hello={{var}}',['varx'=>'world'],true) // hello={{var}}<br>
#### Parameters:
* **$string** The input value. It could contain variables defined as {{namevar}} (string)
* **$values** The dictionary of values. (array)
* **$notFoundThenKeep** [false] If true and the value is not found, then it keeps the value.
Otherwise, it is replaced by an empty value (bool)

## CliOneParam
CliOne - A simple creator of command line argument program.
### Field key (The)
key of the parameter. If null then the parameter is invalid.
### Field type (string=['first','last','second','flag','longflag','onlyinput','none'][$i])

### Field alias ()

### Field question ()

### Field default (mixed)

### Field currentAsDefault ()

### Field allowEmpty ()

### Field description ()

### Field required ()

### Field input ()

### Field missing (bool)
if true then the value is not entered, but it could have a value (default value)
### Field origin (string=['none','argument','input','set'][$i])
indicates the origin of the value of the argument
### Field inputType ()

### Field inputValue ()

### Field value ()

### Field valueKey ()

### Field argumentIsValueKey ()


### Method __construct()
The constructor. It is used internally
#### Parameters:
* **$parent** param CliOne $parent (CliOne)
* **$key** param ?string $key (?string)
* **$type** =['first','last','second','flag','longflag','onlyinput','none'][$i] (string)
* **$alias** param array|string $alias (array|string)
* **$value** param mixed $value (mixed)
* **$valueKey** param mixed $valueKey (mixed)
* **$argumentIsValueKey** <b>true</b> the argument is value-key<br>
<b>false</b> (default) the argument is a value (bool)

### Method add()
It adds an argument but it is not evaluated.
#### Parameters:
* **$override** if false (default) and the argument exists, then it trigger an exception.<br>
if true and the argument exists, then it is replaced. (bool)

### Method evalParam()
It creates an argument and eval the parameter.<br>
It is a macro of add() and CliOne::evalParam()
#### Parameters:
* **$forceInput** if false and the value is already digited, then it is not input anymore (bool)
* **$returnValue** If true, then it returns the value obtained.<br>
If false (default value), it returns an instance of CliOneParam. (bool)

### Method getHelpSyntax()
It returns the syntax of the help.

### Method setHelpSyntax()
It sets the syntax of help.
#### Parameters:
* **$helpSyntax** param array $helpSyntax (array)

### Method getHistory()


### Method setHistory()
It sets the whole local history. It could be used to autocomplete using the key arrows up and down.
#### Parameters:
* **$history** param array $history (array)

### Method getNameArg()


### Method getPatterColumns()
It gets the pattern, patternquestion and footer

### Method isAddHistory()
true if the evaluation of this parameter is added automatically in the global history

### Method setAddHistory()


### Method isValid()
Return the if the parameter is valid (if the key is not null).

### Method resetInput()
It resets the user input and marks the value as missing.

### Method setAllowEmpty()
It sets to allow empty values.<br>
If true, and the user inputs nothing, then the default value is never used (unless it is an option), and it
returns an empty "".<br> If false, and the user inputs nothing, then the default value is used.<br>
<b>Note</b>: If you are using an option, you are set a default value, and you enter nothing, then the default
value is still used.
#### Parameters:
* **$allowEmpty** param bool $allowEmpty (bool)

### Method setArgument()

#### Parameters:
* **$type** =['first','last','second','flag','longflag','onlyinput','none'][$i] (string)
* **$argumentIsValueKey** <b>true</b> the argument is value-key<br>
<b>false</b> (default) the argument is a value (bool)

### Method setArgumentIsValueKey()
Determine if the value via argument is a value or a value-key.
#### Parameters:
* **$argumentIsValueKey** <b>true</b> the argument is value-key<br>
<b>false</b> (default) the argument is a value (bool)

### Method setCurrentAsDefault()
if true then it set the current value as the default value but only if the value is not missing or null.<br>
if the current value is null, then it uses the regular default value assigned by setDefault()<br>
The default value is assigned every time evalParam() is called.<br>
<b>Example:</b><br>
<pre>
$this->createParam('test1')->setDefault('def')->setInput()->setCurrentAsDefault()->add();
// the if the param has a value (not null), then the default is the value
// otherwise, the default value is "def".
</pre>
#### Parameters:
* **$currentAsDefault** param bool $currentAsDefault (bool)

### Method setDefault()
It sets the default value that it is used when the user doesn't input the value<br>
Setting a default value could bypass the option isRequired()
#### Parameters:
* **$default** param mixed $default (mixed)

### Method setDescription()
It sets the description of a parameter<br>
<b>Example:</b><br>
<pre>
$this->setDescription('It shows the help','do you want help?',['usage -help'],'typehelp');
</pre>
#### Parameters:
* **$description** the initial description (used when we show the syntax) (string)
* **$question** The question, it is used in the user input. (string|null)
* **$helpSyntax** (optional) It adds one or multiple lines of help syntax. (string[])
* **$nameArg** (optional) The name of the argument (used for help). (string)

### Method setInput()
It sets the input type
<b>Example:</b><br>
<pre>
$this->createParam('selection')->setInput(true,'optionsimple',['yes','no'])->add();
$this->createParam('name')->setInput(true,'string')->add();
$this->createParam('many')->setInput(true,'multiple3',['op1','op2,'op3'])->add();
</pre>
#### Parameters:
* **$input** if true, then the value could be input via user. If false, the value could only be
entered as argument. (bool)
* **$inputType** =['number','range','string','password','multiple','multiple2','multiple3','multiple4','option','option2','option3','option4','optionshort'][$i] (string)
* **$inputValue** Depending on the $inputtype, you couls set the list of values.<br>
This value allows string, arrays and associative arrays<br>
The values indicated here are used for input and validation<br>
The library also uses this value for the auto-complete feature (tab-key). (mixed)
* **$history** you can add a custom history for this parameter (array)

### Method setPattern()
It sets the visual pattern<br>
<ul>
<li><b>{selection}</b> (for table) used by "multiple", it shows if the value is selected or not</li>
<li><b>{key}</b> (for table)it shows the current key</li>
<li><b>{value}</b> (for table)it shows the current value. If the value is an array then it is "json"</li>
<li><b>{valueinit}</b> (for table)if the value is an array then it shows the first value</li>
<li><b>{valuenext}</b> (for table)if the value is an array then it shows the next value (it could be the same,
the second or the last one)</li>
<li><b>{valueend}</b> (for table)if the value is an array then it shows the last value</li>
<li><b>{desc}</b> it shows the description</li>
<li><b>{def}</b> it shows the default value</li>
<li><b>{prefix}</b> it shows a prefix</li>
</ul>
<b>Example:</b><br>
<pre>
$this->setPattern('<cyan>[{key}]</cyan> {value}','{desc} <cyan>[{def}]</cyan> {prefix}:','it is the footer');
</pre>
#### Parameters:
* **$patterColumns** if null then it will use the default value. (?string)
* **$patterQuestion** the pattern of the question. (?string)
* **$footer** the footer line (if any) (?string)

### Method setRequired()
It marks the value as required<br>
The value could be ignored if it used together with setDefault()
#### Parameters:
* **$required** param boolean $required (boolean)

### Method setValue()
We set a new value
#### Parameters:
* **$newValue** it sets a new value (mixed)
* **$newValueKey** it sets the value-key. If null then the value is asumed using inputvalue. (mixed)
* **$missing** by default every time we set a value, we mark missing as false, however you can change
it. (bool)

