<?php
namespace Osyra\Tera;

abstract class AbstractGenerator {
    protected $pattern;

    public function __construct($pattern) {
        $this->pattern = $pattern;
    }

    abstract protected function generate();
}
