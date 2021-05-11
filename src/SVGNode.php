<?php
namespace Osyra\Tera;

class SVGNode {
    private
        $tag,
        $attributes = [],
        $nodes = [];

    public function __construct($tag) {
        $this->tag = $tag;
    }

    public function add(SVGNode $child) {
        $this->nodes[] = $child;
    }

    public function setAttributes(Array $attributes) {
        foreach($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    public function setAttribute($key, $value) {
        $this->attributes[$key] = $value;
    }

    public function transform($transformations) {
        $value = '';

        foreach($transformations as $type => $params) {
            $value .= $type.'('.implode(',', $params).') ';
        }

        $this->setAttribute('transform', $value);
    }

    public function __toString() {
        $output = '<'.$this->tag.' ';

        foreach($this->attributes as $key => $value) {
            $output .= $key.'="'.$value.'" ';
        }

        $output .= '>';

        foreach($this->nodes as $node) {
            $output .= (String) $node;
        }

        $output .= '</'.$this->tag.'>'."\n";
        return $output;
    }

    public static function rect($x, $y, $width, $height) {
        $node = new SVGNode('rect');
        $node->setAttribute('x', $x);
        $node->setAttribute('y', $y);
        $node->setAttribute('width', $width);
        $node->setAttribute('height', $height);
        return $node;
    }

    public static function circle($cx, $cy, $r) {
        $node = new SVGNode('circle');
        $node->setAttribute('cx', $cx);
        $node->setAttribute('cy', $cy);
        $node->setAttribute('r', $r);
        return $node;
    }

    public static function path($str) {
        $node = new SVGNode('path');
        $node->setAttribute('d', $str);
        return $node;
    }

    public static function polyline($str) {
        $node = new SVGNode('polyline');
        $node->setAttribute('points', $str);
        return $node;
    }

    public static function group() {
        return new SVGNode('g');
    }
}
