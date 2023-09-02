<?php

namespace eftec\CliOne;
class CliOneContainer {
    public $parent;
    public $content=[];
    public $type='container';
    public $width;
    public $height;


    public function __construct($content,$width=null,$height=null,$type='container',$parent=null) {
        $this->content=$content;
        $this->width=$width;
        $this->height=$height;
        $this->type=$type;
        $this->parent=$parent;
    }
    public function addRow($content,$width=null,$height=null): CliOneContainer
    {
        $row=new CliOneContainer($content,$width,$height,'row',$this);
        $this->content[]=$row;
        return $row;
    }
    public function addCol($content,$width=null,$height=null): CliOneContainer
    {
        $col=new CliOneContainer($content,$width,$height,'col',$this);
        $this->content[]=$col;
        return $col;
    }
    public function close() {
        return $this->parent;
    }
    protected function percentage($fullParent,$width) {
        if(is_numeric($width)) {
            return $width;
        }
        if(is_string($width) && $width[-1]==='%') {
            $percentage=substr($width,0,-1);
            return round($fullParent*($percentage/100));
        }
        return 0;
    }
    public function draw() {
        $result='';
        $cell='';
        foreach($this->content as $content) {
            if($content instanceof self) {
                $cell.=$content->draw();
            } else {
                $cell.=$content;
            }
        }
        switch ($this->type) {
            case 'container':
                $result.=$cell;
                break;
            case 'row':
                for($i=0;$i<$this->width;$i++) {
                    $result.='-';
                }
                $result.="\n";
                $result.=$cell;
                $result.="\n";
                for($i=0;$i<$this->width;$i++) {
                    $result.='-';
                }
                $result.="\n";
                break;
            case 'col':

                $lines=explode("\n",$cell);
                foreach($lines as $line) {
                    $result.="|$line|";
                }

        }
        return $result;
    }

}
