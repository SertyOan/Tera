<?php
namespace Osyra\Tera;

class SVG {
    private
        $height = 100,
        $width = 100,
        $nodes = [];

    public function __toString() {
        $output = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$this->width.'" height="'.$this->height.'">';

        foreach($this->nodes as $node) {
            $output .= (String) $node;
        }

        $output .= '</svg>';
        return $output;
    }

    public function setWidth($width) {
        $this->width = floor($width);
    }

    public function setHeight($height) {
        $this->height = floor($height);
    }

    public function add(SVGNode $child) {
        $this->nodes[] = $child;
    }
}
