<?php

namespace eftec\CliOne;
class CliOneContainer
{
    public ?object $parent = null;
    public array $content = [];
    public string $type = 'container';
    public ?int $width = null;
    public ?int $height = null;

    public function __construct($content,?int $width = null,?int $height = null,string $type = 'container', $parent = null)
    {
        $this->content = $content;
        $this->width = $width;
        $this->height = $height;
        $this->type = $type;
        $this->parent = $parent;
    }

    public function addRow($content, $width = null, $height = null): CliOneContainer
    {
        $row = new CliOneContainer($content, $width, $height, 'row', $this);
        $this->content[] = $row;
        return $row;
    }

    public function addCol($content, $width = null, $height = null): CliOneContainer
    {
        $col = new CliOneContainer($content, $width, $height, 'col', $this);
        $this->content[] = $col;
        return $col;
    }

    public function close()
    {
        return $this->parent;
    }

    protected function percentage($fullParent, $width)
    {
        if (is_numeric($width)) {
            return $width;
        }
        if (is_string($width) && $width[-1] === '%') {
            $percentage = substr($width, 0, -1);
            return round($fullParent * ($percentage / 100));
        }
        return 0;
    }

    public function draw(): string
    {
        $cell = '';
        $result = '';
        foreach ($this->content as $content) {
            if ($content instanceof self) {
                $cell .= $content->draw();
            } else {
                $cell .= $content;
            }
        }
        switch ($this->type) {
            case 'container':
                $result .= $cell;
                break;
            case 'row':
                $result = str_repeat('-', $this->width);
                $result .= "\n";
                $result .= $cell;
                $result .= "\n";
                $result .= str_repeat('-', $this->width);
                $result .= "\n";
                break;
            case 'col':
                $lines = explode("\n", $cell);
                foreach ($lines as $line) {
                    $result .= "|$line|";
                }
        }
        return $result;
    }
}
