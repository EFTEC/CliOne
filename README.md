# CliOne

This library helps to create command line (CLI) operator for PHP in Windows, Mac and Linux

[![Packagist](https://img.shields.io/packagist/v/eftec/CliOne.svg)](https://packagist.org/packages/eftec/CliOne)
[![Total Downloads](https://poser.pugx.org/eftec/CliOne/downloads)](https://packagist.org/packages/eftec/CliOne)
[![Maintenance](https://img.shields.io/maintenance/yes/2022.svg)]()
[![composer](https://img.shields.io/badge/composer-%3E1.6-blue.svg)]()
[![php](https://img.shields.io/badge/php-7.1-green.svg)]()
[![php](https://img.shields.io/badge/php-8.x-green.svg)]()
[![CocoaPods](https://img.shields.io/badge/docs-70%25-yellow.svg)]()

## Features

✅ Windows, Linux and Mac Compatible.

✅ This library is simple, it only consists of 2 classes and nothing more and no external dependency.

✅ Arguments & user input

✅ Colors available

✅ The design is mostly fluent (it adjust to the width of the screen)

✅ Validation of values



![](docs/examplecomplete.jpg)



# Table of contents

- [CliOne](#clione)
  - [Features](#features)
  - [Getting started](#getting-started)
  - [Example using arguments](#example-using-arguments)
  - [Example using user input](#example-using-user-input)
  - [Showing colors](#showing-colors)
  - [Reading arguments](#reading-arguments)
  - [Types of user input](#types-of-user-input)
  - [Types of colors](#types-of-colors)
  - [Methods CliOne](#methods-clione)
    - [Method __construct()](#method-__construct)
    - [Method findVendorPath()](#method-findvendorpath)
    - [Method getColSize()](#method-getcolsize)
    - [Method evalParam()](#method-evalparam)
    - [Method readParameterCli()](#method-readparametercli)
    - [Method show()](#method-show)
    - [Method showLine()](#method-showline)
    - [Method showParamSyntax()](#method-showparamsyntax)
    - [Method getParameter()](#method-getparameter)
    - [Method setParam()](#method-setparam)
    - [Method showCheck()](#method-showcheck)
    - [Method getValue()](#method-getvalue)
    - [Method getValueKey()](#method-getvaluekey)
    - [Method showparams()](#method-showparams)
    - [Method isCli()](#method-iscli)
    - [Method saveData()](#method-savedata)
    - [Method getArrayParams()](#method-getarrayparams)
    - [Method setArrayParam()](#method-setarrayparam)
    - [Method readData()](#method-readdata)
    - [Method createParam()](#method-createparam)
  - [Methods CliOneParam](#methods-clioneparam)
    - [Method getHelpSyntax()](#method-gethelpsyntax)
    - [Method setHelpSyntax()](#method-sethelpsyntax)
    - [Method setPattern()](#method-setpattern)
    - [Method getPatterColumns()](#method-getpattercolumns)
    - [Method resetInput()](#method-resetinput)
    - [Method __construct()](#method-__construct)
    - [Method setDefault()](#method-setdefault)
    - [Method setCurrentAsDefault()](#method-setcurrentasdefault)
    - [Method setAllowEmpty()](#method-setallowempty)
    - [Method setDescription()](#method-setdescription)
    - [Method setRequired()](#method-setrequired)
    - [Method setInput()](#method-setinput)
    - [Method evalParam()](#method-evalparam)
    - [Method add()](#method-add)
  - [Changelog](#changelog)





## Getting started

Add the library using composer:

> composer require eftec/clione

And create a new instance of the library

```php
$cli=new CliOne(); // instance of the library
```

## Types of arguments

```shell
php mycli.php subcommandfirst subcommandsecond -flag valueflag --longflag valueflag2 subcommandlatest
```

The system allows to read multiple types of arguments

* **first**: this argument does not have value and it is position (in the very first position), it could be not be prefixed with a "-"
* **second**: this argument is also positional (second position) and does not have any value
* **last**: this argument is also positional and it is always at the latest argument
* **flag**: the argument is prefixed with a single "-". This argument not need to be a single character.
* **longflag**: the argument is prefixed with a double "--"
* **onlyinput/none**: the system never read the argument so it could be user-input.

The argument could be created as:

```php
// program.php -f hello
// or
// program.php -f=hello
// or
// program.php -f "hello world"
$cli->createParam('f','flag')->add();  // add() is important otherwise the parameter will not be create.
```

And it could be read as:

```php
$result=$cli->evalParam('name'); // $result->value will return "hello"
```

Now, what if you want to create multiples alias for the same parameter.

```php
// program.php -h 
// or
// program.php --help 
$cli->createParam('h','flag',['help'])->add();  // it adds an alias longflag called "help"
```

## Examples

### Example using arguments

[example/example1.php](example/example1.php)

And create the next code

```php
// example1.php
// don't forget to add autoloader, namespace, etc.
$cli=new CliOne(); // instance of the library
if($cli->isCli()) { // we validate if we are running a CLI or not.
  $cli->createParam('param1') // the name of the parameter
        ->setDescription('Some description','question?') // description and question
        ->setRequired(true) // if the field is required
        ->setDefault('param1') // the default value If the value is not found
        ->add(); // it adds a parameter to the cli
  $param1=$cli->evalParam('param1'); // then we evaluate the parameter.
  var_dump($param1->value);  
}
```
So you can run as:

![](docs/example1.jpg)





### Example using user input

You can ask for user input of the user.

[example/example2.php](example/example2.php)

```php
$cli=new CliOne();
if($cli->isCli()) {
    $cli->createParam('param1')
        ->setDescription('This field is called param1 and it is required')
        ->setInput(true,'string')
        ->setRequired(true)
        ->setDefault('param1')
        ->add();
    $param1 = $cli->evalParam('param1');
    var_dump($param1->value);
}
```

It will show the next result

![](docs/example2.jpg)



### Example with a game

[example/examplegame.php](example/examplegame.php)

![docs/guess.jpg](docs/guess.jpg)
Image (c) George Beker

![docs/examplegame.jpg](docs/examplegame.jpg) 



### Example colors

You can see the tags available in [Types of colors](#types-of-colors)

[example/examplecolor.php](example/examplecolor.php)




```php
$cli->showLine("<bold>bold</bold>");
$cli->showLine("<dim>dim</dim>");
$cli->showLine("<bred>background red</bred>");
$cli->showLine("<bblue>background red</bblue>");
$cli->showLine("<bwhite><black>background white</black> </bwhite>");
$cli->showLine("<byellow><blue>background yellow</blue></byellow>");
$cli->showLine("<red>error</red> (color red)");
$cli->showLine("<yellow>warning</yellow> (color yellow)");
$cli->showLine("<blue>information</blue> (blue)");
$cli->showLine("<yellow>yellow</yellow> (yellow)");
$cli->showLine("<green>green</green> (color green)");
$cli->showLine("<italic>italic</italic>");
$cli->showLine("<bold>bold</bold>");
$cli->showLine("<bold><yellow>bold yellow</yellow></bold>");
$cli->showLine("<strikethrough>stike</strikethrough>");
$cli->showLine("<underline>underline</underline>");
$cli->showLine("<cyan>cyan</cyan> (color cyan)");
$cli->showLine("<magenta>magenta</magenta> (color magenta)");
$cli->showLine("<bold><cyan>bold cyan</cyan></bold> (color cyan)");
$cli->showLine("<bold><magenta>bold magenta</magenta></bold> (color magenta)");
$cli->showLine("<bblue><col0/> col0</bblue>");
$cli->showLine("<bblue><col1/> col1</bblue>");
$cli->showLine("<bblue><col2/> col2</bblue>");
$cli->showLine("<bblue><col3/> col3</bblue>");
$cli->showLine("<bblue><col4/> col4</bblue>");
$cli->showLine("<bblue><col1/> col1 <col3/> col3 <col5/> col5</bblue>");
$cli->showLine("The parameters of option are: <option/>",$cli->getParameter('test'));
```

![](docs/examplecolor.jpg)

## 

### Example tables

[example/exampletables.php](example/exampletables.php)

![docs/exampletable.jpg](docs/exampletable.jpg)







## Types of user input

| userinput    | description                                                  |
| ------------ | ------------------------------------------------------------ |
| number       | the value must be a number                                   |
| range        | the value must be a number between a range of values         |
| string       | the value must be a string (by default, nulls are not string) |
| password     | the value must be a string (and if it is displayed, then it shows ****) |
| multiple     | It allows to select one or many values displated in 1 column |
| multiple2    | The same than multiple but displayed in 2 columns            |
| multiple3    | The same than multiple but displayed in 3 columns            |
| multiple4    | The same than multiple but displayed in 4 columns            |
| option       | It allows to select one from many values displayed in a column |
| option2      | The same than option but displayed in 2 columns              |
| option3      | The same than option but displayed in 3 columns              |
| option4      | The same than option but displayed in 4 columns              |
| optionsimple | It allows to select one from many values. It doesn't use columns |

## Types of colors

| tag                                        | description                   |
| ------------------------------------------ | ----------------------------- |
| <red>error</red>                           | color red                     |
| <yellow>warning</yellow>                   | color yellow                  |
| <blue>information</blue>                   | blue                          |
| <black>black</black>                       | black                         |
| <white>white</white>                       | white                         |
| <green>success</green>                     | color green                   |
| <italic>italic</italic>                    | italic                        |
| <bold>bold</body>                          | bold                          |
| <underline>underline</underline>           | underline                     |
| <cyan>cyan</cyan>                          | color light cyan              |
| <magenta>magenta</magenta>                 | color magenta                 |
| <col0/><col1/><col2/><col3/><col4/><col5/> | col0 leftmost column          |
| <option/>                                  | if the input has some options |
| <bred>red</bred>                           | background color red          |
| <byellow><bblue>..                         | background color              |



## Methods CliOne

* Method __construct()

  The constructor
  #### Parameters:
  * **$origin** you can specify the origin file. If you specify the origin file, then isCli will only
  return true if the file is called directly. (?string)

  ### Method findVendorPath()
  It finds the vendor path starting from a route. The route must be inside the application path.
  #### Parameters:
  * **$initPath** the initial path, example __DIR__, getcwd(), 'folder1/folder2'. If null, then __DIR__ (string)

  ### Method createParam()
  It creates a new parameter to be read from the command line and/or to be input manually by the user<br>
  <b>Example:</b><br>
  <pre>
  $this->createParam('k1','first'); // php program.php thissubcommand
  $this->createParam('k1','flag',['flag2','flag3']); // php program.php -k1 <val> or --flag2 <val> or --flag3 <val>
  </pre>
  #### Parameters:
  * **$key** The key or the parameter. It must be unique. (string)
  * **$type** =['first','last','second','flag','longflag','onlyinput','none'][$i]<br>
  <b>flag</b>: (default) it reads a flag "php program.php -thisflag value"<br>
  <b>first</b>: it reads the first argument "php program.php thisarg" (without value)<br>
  <b>second</b>: it reads the second argument "php program.php sc1 thisarg" (without value)<br>
  <b>last</b>: it reads the second argument "php program.php ... thisarg" (without value)<br>
  <b>longflag</b>: it reads a longflag "php program --thislongflag value<br>
  <b>last</b>: it reads the second argument "php program.php ... thisvalue" (without value)<br>
  <b>onlyinput</b>: the value means to be user-input, and it is stored<br>
  <b>none</b>: the value it is not captured via argument, so it could be user-input, but it is
  not stored<br>
  none parameters could always be overridden, and they are used to "temporary" input such as
  validations (y/n). (string)
  * **$alias** A simple array with the name of the arguments to read (without - or --)<br>
  if the type is a flag, then the alias is a double flag "--".<br>
  if the type is a double flag, then the alias is a flag. (array)

  ### Method downLevel()
  Down a level in the breadcrub.

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

  ### Method getArrayParams()
  It returns an associative array with all the parameters of the form [key=>value]
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

  ### Method readParameterArgFlag()

  #### Parameters:
  * **$parameter** param CliOneParam $parameter (CliOneParam)

  ### Method saveData()
  It saves the information into a file. The content will be serialized.
  #### Parameters:
  * **$filename** the filename (without extension) to where the value will be saved. (string)
  * **$content** The content to save. It will be serialized. (mixed)

  ### Method setAlign()

  #### Parameters:
  * **$title** =['left','right','middle'][$i] (string)
  * **$content** =['left','right','middle'][$i] (string)
  * **$contentNumeric** =['left','right','middle'][$i] (string)

  ### Method setArrayParam()
  It sets the parameters using an array of the form [key=>value]<br>
  It also marks the parameters as missing=false
  #### Parameters:
  * **$array** the associative array to use to set the parameters. (array)
  * **$excludeKeys** you can add a key that you want to exclude. (array)

  ### Method setColor()


  ### Method setParam()
  It sets the value of a parameter manually.<br>
  Once the value is set, then the system skips to read the values from the command line or ask for input.
  #### Parameters:
  * **$key** the key of the parameter (string)
  * **$value** the value to assign. (mixed)

  ### Method setPatternTitle()
  {value} {type}
  #### Parameters:
  * **** string $pattern1Stack if null then it will use the default value. (string|null)

  ### Method setPatternCurrent()
  <bold>{value}{type}</bold>
  #### Parameters:
  * **$pattern2Stack** if null then it will use the default value. (string|null)

  ### Method setPatternSeparator()
  ">"
  #### Parameters:
  * **$pattern3Stack** if null then it will use the default value. (string|null)

  ### Method setPatternContent()
  Not used yet.
  #### Parameters:
  * **$pattern4Stack** if null then it will use the default value. (null|string)

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
  <bold>bold</body>
  <dim>dim</dim>
  <underline>underline</underline>
  <cyan>cyan</cyan> (color light cyan)
  <magenta>magenta</magenta> (color magenta)
  <col0/><col1/><col2/><col3/><col4/><col5/>  columns. col0=0 (left),col1--col5 every column of the page.
  <option/> it shows all the options available (if the input has some options)
  </pre>
  #### Parameters:
  * **$content** content to display (string)
  * **$cliOneParam** param null $cliOneParam (null)

  ### Method showMessageBox()

  #### Parameters:
  * **$lines** param string|string[] $lines (string|string[])
  * **$titles** param string|string[] $titles (string|string[])

  ### Method showParamSyntax()
  It shows the syntax of a parameter.
  #### Parameters:
  * **$key** the key to show. "*" means all keys. (string)
  * **$tab** the first separation. Values are between 0 and 5. (int)
  * **$tab2** the second separation. Values are between 0 and 5. (int)
  * **$excludeKey** the keys to exclude. It must be an indexed array with the keys to skip. (array)

  ### Method showProgressBar()

  #### Parameters:
  * **$currentValue** the current value (numeric)
  * **$max** the max value to fill the bar. (numeric)
  * **$columnWidth** the size of the bar (in columns) (int)
  * **$currentValueText** the current value to display at the left.<br>
  if null then it will show the current value (with a space in between) (string|null)

  ### Method showTable()

  #### Parameters:
  * **$assocArray** An associative array with the values to show. The key is used for the index. (array)

  ### Method showValuesColumn()
  It shows the values as columns.
  #### Parameters:
  * **$values** the values to show. It could be an associative array or an indexed array. (array)
  * **$type** ['multiple','multiple2','multiple3','multiple4','option','option2','option3','option4'][$i] (string)
  * **$patternColumn** the pattern to be used, example: "<cyan>[{key}]</cyan> {value}" (null|string)

  ### Method showWaitCursor()


  ### Method showparams()
  It will show all the parameters by showing the key, the default value and the value<br>
  It is used for debugging and testing.

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

  ### Method substr()


  ### Method upLevel()
  Up a level in the breadcrumb
  #### Parameters:
  * **$content** the content of the new line (string)
  * **$type** the type of the content (optional) (string)

  ### Method replaceColor()
  It sets the color of the cli<br>
  <pre>
  <red>error</red> (color red)
  <yellow>warning</yellow> (color yellow)
  <blue>information</blue> (blue)
  <yellow>yellow</yellow> (yellow)
  <green>green</green>  (color green)
  <italic>italic</italic>
  <bold>bold</body>
  <underline>underline</underline>
  <strikethrough>strikethrough</strikethrough>
  <cyan>cyan</cyan> (color light cyan)
  <magenta>magenta</magenta> (color magenta)
  <col0/><col1/><col2/><col3/><col4/><col5/>  columns. col0=0 (left),col1--col5 every column of the page.
  <option/> it shows all the options available (if the input has some options)
  </pre>
  #### Parameters:
  * **$content** param $content ()
  * **$cliOneParam** param CliOneParam|null $cliOneParam (CliOneParam|null)

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

## Methods CliOneParam

### Method getHelpSyntax()

It returns the syntax of the help.

### Method setHelpSyntax()

It sets the syntax of help.

#### Parameters:

* **$helpSyntax** param array $helpSyntax (array)

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
$this->setPattern('<c>[{key}]</c> {value}','{desc} <c>[{def}]</c> {prefix}:','it is the footer');
</pre>

#### Parameters:

* **$patterColumns** if null then it will use the default value. (?string)
* **$patterQuestion** the pattern of the question. (?string)
* **$footer** the footer line (if any) (?string)

### Method getPatterColumns()

It gets the pattern, patternquestion and footer

### Method resetInput()

It resets the user input and marks the value as missing.

### Method __construct()

The constructor. It is used internally

#### Parameters:

* **$parent** param CliOne $parent (CliOne)
* **$key** param ?string $key (?string)
* **$isOperator** param bool $isOperator (bool)

### Method setDefault()

It sets the default value that it is used when the user doesn't input the value<br>
Setting a default value could bypass the option isRequired()

#### Parameters:

* **$default** param mixed $default (mixed)

### Method setCurrentAsDefault()

if true then it set the current value as the default value but only if the value is not missing.<br>
The default value is assigned every time evalParam() is called.

#### Parameters:

* **$currentAsDefault** param bool $currentAsDefault (bool)

### Method setAllowEmpty()

It sets to allow empty values.<br>
If true, and the user inputs nothing, then the default value is never used (unless it is an option), and it
returns an empty "".<br> If false, and the user inputs nothing, then the default value is used.<br>
<b>Note</b>: If you are using an option, you are set a default value, and you enter nothing, then the default
value is still used.

#### Parameters:

* **$allowEmpty** param bool $allowEmpty (bool)

### Method setDescription()

It sets the description

#### Parameters:

* **$description** the initial description (used when we show the syntax) (string)
* **$question** The question, it is used in the user input. (null|string)
* **$helpSyntax** It adds one or multiple lines of help syntax. (string[])

### Method setRequired()

It marks the value as required<br>
The value could be ignored if it used together with setDefault()

#### Parameters:

* **$required** param boolean $required (boolean)

### Method setInput()

It sets the input type

#### Parameters:

* **$input** if true, then the value could be input via user. If false, the value could only be
  entered as argument. (bool)
* **$inputType** =['number','range','string','password','multiple','multiple2','multiple3','multiple4','option','option2','option3','option4','optionsimple'][$i] (string)
* **$inputValue** param mixed $inputValue (mixed)

### Method evalParam()

It creates an argument and eval the parameter.<br>
It is a macro of add() and CliOne::evalParam()

#### Parameters:

* **$forceInput** if false and the value is already digited, then it is not input anymore (bool)

### Method add()

It adds an argument but it is not evaluated.

#### Parameters:

* **$override** if false (default) and the argument exists, then it trigger an exception.<br>
  if true and the argument exists, then it is replaced. (bool)



## Changelog
* 1.5.1 (2022-02-18)
  * **[fix]** fixed a problem when the type is "none", it returned null instead of false.
* 1.5 (2022-02-17)
  * **[fix]** corrected the display and trim of some text when the text uses a color.
  * **[new]** it allows multiples types of arguments (not only flags), including positional, flags, long-flags are none.
  * **[new]** added stack. Some visual elements allows to stack values.
* 1.4.1 (2022-02-15)
  * **[fix]** some fix for characters thare unicode. The system will use automatically MB_STRING if the library is available.
  Otherwise, it will use the default library 
  * **[new]** new styles and patterns for components.
* 1.4 (2022-02-15)
  * **[replaced]** now all colors are express not abbreviated "<e>" => "<red>", etc.
  * **[new]** added all basic colors, background and solved a problem with the underline.
  * **[new]** added showFrame(),showTable(),showWaitCursor(),showProgressBar()
* 1.3 (2022-02-14)
  * **[new]** added autocomplete (tab key)
  * **[new]** added breadcrub
  * **[new]** added showValuesColumn()
  * **[replaced]** now keys (showing in options) are aligned at the center.
* 1.2.2 (2022-02-14)
  * **[fixed]** fixed a problem where the question is truncated with an ellipsis (...)
* 1.2.1 (2022-02-13)
  * **[fixed]** fixed some bugs
  * **[new]** keys are padded, example [ 1] [ 2] [ 3] ... [99],  [value 1] [value 2] [value  ]
* 1.2 (2022-02-13)
  * **[replaced]** "options" renamed as "multiple". Added "multiple2","multiple3","multiple4"
  * **[new]** associative arrays are allowed.
  * **[new]** added templates.
  * **[new]** added valuekey.
* 1.1 (2022-02-12)
  * **[new]** new methods savedata(),getArrayParams(),setArrayParam(),readData()
  * **[replaced]** a new argument for the method replaceColor()
  * **[new]** a new type called password
* 1.0.1 (2022-02-11)
  * **[Fixed]** namespace.
  * **[Fixed]** replaceColor() fixed a color
  * **[Added]** CliOne::indVendorPath()
* 1.0 (2022-02-11)
  * End of beta, first release. 
* 0.7 (2022-02-11)
  * Added option4.
  * optionshorts allows to specify the first letter, only if the first letter is unique, examples: yes/no allows to use y/n
* 0.6 (2022-02-11)
  * Added option2 and option3 
  * allowNulls() now doesn't work with default values.
* 0.5 (2022-02-03) 
  * [unittest] Better unit test.
  * [CliOneParam] You can set if you allow empty values or not.
  * [CliOne] You can set if you allow empty values or not.
* 0.4 (2022-01-27) Now test coverage is over the 75%
* 0.3 (2022-01-26) new corrections to the reading of values
* 0.2 some updates
* 0.1 first version
