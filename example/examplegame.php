<?php /** @noinspection TypeUnsafeComparisonInspection */

use eftec\CliOne\CliOne;

include __DIR__.'/../src/CliOne.php';
include __DIR__.'/../src/CliOneParam.php';

$cli=new CliOne(); // we create an instance

$numbertarget=0;
$chance=0;
$chanceLimit=10;
$bet=null;
$money=100;

$cli->createParam('bet')
    ->setDescription('','What is your bet?')
    ->setPattern(null,'{desc} <cyan>${prefix}</cyan> :')
    ->setInput(true,'range',[0,100])
    ->add();
$cli->createParam('number')
    ->setDescription('','Pick a number between 0 and 100')
    ->setPattern(null,'{desc}:')
    ->setInput(true,'range',[0,100])
    ->add();
$cli->createParam('question')
    ->setDescription('','Do you want to play it again?')
    ->setDefault('yes')
    ->setInput(true,'optionshort',['yes','no'])
    ->add();
$title="
   _____                     
  / ____|                    
 | |  __ _   _  ___  ___ ___ 
 | | |_ | | | |/ _ \/ __/ __|
 | |__| | |_| |  __/\__ \__ \
  \_____|\__,_|\___||___/___/                                                          
";
$cli->showLine($title);
$cli->showLine('<yellow>Rules</yellow>');
$cli->showLine('It is a small game where you must guess a number between 0 and 100.');
$cli->showLine("Initially you have $chanceLimit chances to guess the number. However with every game, your chances are reduced by one.");
$cli->showLine("You start betting money and if you find the number then you double your bet");
$cli->showLine("However, if you use all your chances then you loss your bet");
$cli->showLine("There is not limit in your bet");
$cli->showLine('The goal is to collect as much money as possible');
$cli->showLine('Initially the game is easy and you usually bet all your money in the first runs, however you must know when to quit');

$cli->showLine();
$cli->showLine("You start with <bold>\$$money</bold> bucks.");
$cli->showLine();
cleargame();
$cli->getParameter('bet')->setInput(true,'range',[0,$money]);
$cli->showLine("Your current fund is :<bold>\$$money</bold>");
while(true) {
    $bet = $cli->evalParam('bet', true);
    if ($bet->value == 0) {
        $cli->showLine('<yellow>Chicken!</yellow>');
    } else {
        break;
    }
}
$again=false;
while(true) {
    $number=$cli->evalParam('number',true);
    if($number->value<$numbertarget) {
        $chance++;
        $cli->showLine('<yellow>Your number is too small</yellow>');
        $distance=abs($number->value-$numbertarget);
        if($distance<5) {
            $cli->showLine('<green>You are too close</green>');
        }

        $cli->showLine('You have <bold>'.($chanceLimit-$chance).'</bold> chances');
    }
    if($number->value>$numbertarget) {
        $chance++;
        $cli->showLine('<yellow>Your number is too big</yellow>');
        $distance=abs($number->value-$numbertarget);
        if($distance<5) {
            $cli->showLine('<green>You are too close</green>');
        }
        $cli->showLine('You have <bold>'.($chanceLimit-$chance).'</bold> chances');
    }
    if($chance===$chanceLimit) {
        $money-=$bet->value;
        $cli->showLine("<red>You loss \$$bet->value</red> the number was <bold>$numbertarget</bold>");
        $again=true;
    }
    if($number->value==$numbertarget) {
        $money+=$bet->value;
        $cli->showLine("<green>Jackpot! You win \$$bet->value</green>");
        $again=true;
    }
    if($again) {
        $again=false;
        if($money<=0) {
            $cli->showLine('<red>Game over</red> You loss all your money');
            die(1);
        }
        if($chanceLimit<2) {
            break;
        }
        $q = $cli->evalParam('question', true);
        if ($q->value === 'yes') {
            $chanceLimit--;
            $cli->getParameter('bet')->setInput(true, 'range', [0, $money]);
            while(true) {
                $cli->showLine("Your current fund is :<bold>\$$money</bold>");
                $bet = $cli->evalParam('bet', true);
                if ($bet->value == 0) {
                    $cli->showLine('<yellow>Chicken!</yellow>');
                } else {
                    break;
                }
            }
            cleargame();
        } else {
            break;
        }
    }
}
$cli->showLine("Money collected :<bold>\$$money</bold>");
$cli->showLine('<green>Bye</green>');


function cleargame() {
    global $numbertarget;
    global $chance;
    global $cli;
    global $money;



    /** @noinspection PhpUnhandledExceptionInspection */
    $numbertarget=random_int(0,100);
    $chance=0;

}


