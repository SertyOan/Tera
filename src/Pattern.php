<?php
namespace Osyra\Tera;

class Pattern {
    const
        PATTERNS = [
            'Octogons',
            'OverlappingCircles',
            'PlusSigns',
            'Xes',
            'SineWaves',
            'Hexagons',
            'OverlappingRings',
            'Plaid',
            'Triangles',
            'Squares',
            'ConcentricCircles',
            'Diamonds',
            'Tessellation',
            'NestedSquares',
            'MosaicSquares',
            'Chevrons',
            'FishScales',
            'WaveFishScales',
            'Cubes',
            'Test'
        ],
        DEFAULT_BASE_COLOR = '#933c3c',
        FILL_COLOR_DARK = '#222',
        FILL_COLOR_LIGHT = '#ddd',
        STROKE_COLOR = '#000',
        STROKE_OPACITY = 0.02,
        OPACITY_MIN = 0.02,
        OPACITY_MAX = 0.15;

    private
        $hash,
        $options,
        $svg;

    // re-maps a number from one range to another
    private static function remap($value, $initialMin, $initialMax, $targetMin, $targetMax) {
        $value = (Float) $value;
        $initialRange = $initialMax - $initialMin;
        $targetRange = $targetMax - $targetMin;

        return ($value - $initialMin) * $targetRange / $initialRange + $targetMin;
    }

    private static function fillColor($val) {
        return ($val % 2 === 0) ? self::FILL_COLOR_LIGHT : self::FILL_COLOR_DARK;
    }

    private static function fillOpacity($val) {
        return self::remap($val, 0, 15, self::OPACITY_MIN, self::OPACITY_MAX);
    }

    public function __construct($string, Array $options = []) {
        if(!isset($options['baseColor'])) {
            $options['baseColor'] = self::DEFAULT_BASE_COLOR;
        }

        $this->hash = sha1($string);
        $this->options = $options;
        $this->svg = new SVG();

        $this->generateBackground();
        $this->generatePattern();
    }

    public function toDataURI() {
        $output = (String) $this->svg;
        $base64 = base64_encode($output);
        return 'data:image/svg+xml;base64,'.$base64;
    }

    public function toDataURL() {
        return 'url("'.$this->toDataURI().'")';
    }

    public function __toString() {
        return (String) $this->svg;
    }

    public function generateBackground() {
        if(isset($this->options['color'])) {
            $rgb = Color::hex2rgb($this->options['color']);
        }
        else {
            $value = $this->extractHashValue(14, 3);
            $hueOffset = self::remap($value, 0, 4095, 0, 359);
            $satOffset = $this->extractHashValue(17, 1);
            $baseColor = Color::rgb2hsl(Color::hex2rgb($this->options['baseColor']));

            $baseColor['h'] = (floor(($baseColor['h'] * 360 - $hueOffset) + 360) % 360) / 360;

            if($satOffset % 2 === 0) {
                $baseColor['s'] = min(1, (($baseColor['s'] * 100) + $satOffset) / 100);
            }
            else {
                $baseColor['s'] = max(0, (($baseColor['s'] * 100) - $satOffset) / 100);
            }

            $rgb = Color::hsl2rgb($baseColor);
        }

        $node = SVGNode::rect(0, 0, '100%', '100%');
        $fill = Color::rgb2rgbString($rgb);
        $node->setAttribute('fill', $fill);
        $this->svg->add($node);
    }

    public function generatePattern() {
        if(!isset($this->options['generator'])) {
            $this->options['generator'] = self::PATTERNS[$this->extractHashValue(20)];
        }

        $generator = $this->options['generator'];

        if(!in_array($generator, self::PATTERNS)) {
            throw new \Exception('Invalid generator');
        }

        $class = '\\Osyra\\Tera\\Generators\\'.$generator;
        $instance = new $class($this);
    }

    /**
     * Extract a substring from a hex string and parse it as an integer
     * @param {number} index - Start index of substring
     * @param {number} length - Length of substring. Defaults to 1.
     */
    private function extractHashValue($index, $length = 1) {
        $sub = substr($this->hash, $index, $length);
        return hexdec($sub);
    }

    private function generateOverlappingCircles() {
        $scale = $this->extractHashValue(0);
        $diameter = self::remap($scale, 0, 15, 25, 200);
        $radius = $diameter / 2;

        $this->svg->setWidth($radius * 6);
        $this->svg->setHeight($radius * 6);

        $i = 0;

        for ($y = 0; $y < 6; $y++) {
            for ($x = 0; $x < 6; $x++) {
                $val = $this->extractHashValue($i);
                $opacity = self::fillOpacity($val);
                $fill = self::fillColor($val);
                $attributes = [
                    'fill' => $fill,
                    'opacity' => $opacity
                ];

                $node = SVGNode::circle($x * $radius, $y * $radius, $radius);
                $node->setAttributes($attributes);
                $this->svg->add($node);

                // Add an extra one at top-right, for tiling.
                if ($x === 0) {
                    $node = SVGNode::circle(6 * $radius, $y * $radius, $radius);
                    $node->setAttributes($attributes);
                    $this->svg->add($node);
                }

                // // Add an extra row at the end that matches the first row, for tiling.
                if ($y === 0) {
                    $node = SVGNode::circle($x * $radius, 6 * $radius, $radius);
                    $node->setAttributes($attributes);
                    $this->svg->add($node);
                }

                // // Add an extra one at bottom-right, for tiling.
                if ($x === 0 && $y === 0) {
                    $node = SVGNode::circle(6 * $radius, 6 * $radius, $radius);
                    $node->setAttributes($attributes);
                    $this->svg->add($node);
                }

                $i++;
            }
        }
    }

    private function generateOctogons() {
        function buildShape($squareSize) {
            $s = $squareSize;
            $c = $s * 0.33;

            return implode(',', [
                $c, 0,
                $s - $c, 0,
                $s, $c,
                $s, $s - $c,
                $s - $c, $s,
                $c, $s,
                0, $s - $c,
                0, $c,
                $c, 0
            ]);
        }

        $value = $this->extractHashValue(0);
        $squareSize = self::remap($value, 0, 15, 10, 60);
        $tile = buildShape($squareSize);

        $this->svg->setWidth($squareSize * 6);
        $this->svg->setHeight($squareSize * 6);

        $i = 0;

        for ($y = 0; $y < 6; $y++) {
            for ($x = 0; $x < 6; $x++) {
                $value = $this->extractHashValue($i);
                $opacity = self::fillOpacity($value);
                $fill = self::fillColor($value);

                $attributes = [
                    'fill' => $fill,
                    'fill-opacity' => $opacity,
                    'stroke' => self::STROKE_COLOR,
                    'stroke-opacity' => self::STROKE_OPACITY
                ];

                $node = SVGNode::polyline($tile);
                $node->setAttributes($attributes);
                $node->transform([
                    'translate' => [
                        $x * $squareSize,
                        $y * $squareSize
                    ]
                ]);
                $this->svg->add($node);

                $i++;
            }
        }
    }

    private function generateSquares() {
        $value = $this->extractHashValue(0);
        $squareSize = self::remap($value, 0, 15, 10, 60);

        $this->svg->setWidth($squareSize * 6);
        $this->svg->setHeight($squareSize * 6);

        $i = 0;

        for ($y = 0; $y < 6; $y++) {
            for ($x = 0; $x < 6; $x++) {
                $value = $this->extractHashValue($i);
                $opacity = self::fillOpacity($value);
                $fill = self::fillColor($value);

                $attributes = [
                    'fill' => $fill,
                    'fill-opacity' => $opacity,
                    'stroke' => self::STROKE_COLOR,
                    'stroke-opacity' => self::STROKE_OPACITY
                ];

                $node = SVGNode::rect($x * $squareSize, $y * $squareSize, $squareSize, $squareSize);
                $node->setAttributes($attributes);
                $this->svg->add($node);

                $i++;
            }
        }
    }

    private function generateSineWaves() {
        $period = floor(self::remap($this->extractHashValue(0), 0, 15, 100, 400));
        $amplitude = floor(self::remap($this->extractHashValue(1), 0, 15, 30, 100));
        $waveWidth = floor(self::remap($this->extractHashValue(2), 0, 15, 3, 30));

        $this->svg->setWidth($period);
        $this->svg->setHeight($waveWidth * 32);

        for ($i = 0; $i < 96; $i++) {
            $value = $this->extractHashValue($i % 32);
            $opacity = self::fillOpacity($value);
            $fill = self::fillColor($value);
            $xOffset = $period / 4 * 0.7;

            $attributes = [
                'fill' => 'none',
                'stroke' => $fill,
                'opacity' => $opacity,
                'stroke-width' => $waveWidth.'px'
            ];

            $str = 'M0 '.$amplitude
                .' C '.$xOffset.' 0, '.($period / 2 - $xOffset).' 0, '.($period / 2).' '.$amplitude
                .' S '.($period - $xOffset).' '.($amplitude * 2).', '.$period.' '.$amplitude
                .' S '.($period * 1.5 - $xOffset).' 0, '.($period * 1.5).', '.$amplitude;

            $node = SVGNode::path($str);
            $node->setAttributes($attributes);
            $node->transform([
                'translate' => [
                    - $period / 4,
                    - $waveWidth * 16 + $waveWidth * $i - $amplitude * 1.5
                ]
            ]);
            $this->svg->add($node);
        }
    }

    private function generateNestedSquares() {
        $value = $this->extractHashValue(0);
        $blockSize  = self::remap($value, 0, 15, 4, 12);
        $squareSize = $blockSize * 7;

        $this->svg->setWidth(($squareSize + $blockSize) * 6 + $blockSize * 6);
        $this->svg->setHeight(($squareSize + $blockSize) * 6 + $blockSize * 6);

        $i = 0;

        for ($y = 0; $y < 6; $y++) {
            for ($x = 0; $x < 6; $x++) {
                // big square
                $value = $this->extractHashValue($i);
                $opacity = self::fillOpacity($value);
                $fill = self::fillColor($value);

                $attributes = [
                    'fill' => 'none',
                    'stroke' => $fill,
                    'opacity' => $opacity,
                    'stroke-width' => $blockSize.'px'
                ];

                $rectX = $x * $squareSize + $x * $blockSize * 2 + $blockSize / 2;
                $rectY = $y * $squareSize + $y * $blockSize * 2 + $blockSize / 2;
                $node = SVGNode::rect($rectX, $rectY, $squareSize, $squareSize);
                $node->setAttributes($attributes);
                $this->svg->add($node);

                // little square
                $value = $this->extractHashValue(39 - $i);
                $opacity = self::fillOpacity($value);
                $fill = self::fillColor($value);

                $attributes = [
                    'fill' => 'none',
                    'stroke' => $fill,
                    'opacity' => $opacity,
                    'stroke-width' => $blockSize.'px'
                ];

                $rectX = $x * $squareSize + $x * $blockSize * 2 + $blockSize / 2 + $blockSize * 2;
                $rectY = $y * $squareSize + $y * $blockSize * 2 + $blockSize / 2 + $blockSize * 2;
                $node = SVGNode::rect($rectX, $rectY, $blockSize * 3, $blockSize * 3);
                $node->setAttributes($attributes);
                $this->svg->add($node);

                $i++;
            }
        }
    }

    private function generateConcentricCircles() {
        $scale = $this->extractHashValue(0);
        $ringSize = self::remap($scale, 0, 15, 10, 60);
        $strokeWidth = $ringSize / 5;

        $this->svg->setWidth(($ringSize + $strokeWidth) * 6);
        $this->svg->setHeight(($ringSize + $strokeWidth) * 6);

        $i = 0;

        for ($y = 0; $y < 6; $y++) {
            for ($x = 0; $x < 6; $x++) {
                $value = $this->extractHashValue($i);
                $opacity = self::fillOpacity($value);
                $fill = self::fillColor($value);

                $attributes = [
                    'fill' => 'none',
                    'stroke' => $fill,
                    'opacity' => $opacity,
                    'stroke-width' => $strokeWidth.'px'
                ];

                $centerX = $x * $ringSize + $x * $strokeWidth + ($ringSize + $strokeWidth) / 2;
                $centerY = $y * $ringSize + $y * $strokeWidth + ($ringSize + $strokeWidth) / 2;

                $node = SVGNode::circle($centerX, $centerY, $ringSize / 2);
                $node->setAttributes($attributes);
                $this->svg->add($node);

                $value = $this->extractHashValue(39 - $i);
                $opacity = self::fillOpacity($value);
                $fill = self::fillColor($value);

                $attributes = [
                    'fill' => $fill,
                    'fill-opacity' => $opacity
                ];

                $node = SVGNode::circle($centerX, $centerY, $ringSize / 4);
                $node->setAttributes($attributes);
                $this->svg->add($node);

                $i++;
            }
        }
    }

    private function generateDiamonds() {
        function buildShape($width, $height) {
            return implode(',', [
                $width / 2, 0,
                $width, $height / 2,
                $width / 2, $height,
                0, $height / 2
            ]);
        }

        $value0 = $this->extractHashValue(0);
        $diamondWidth = self::remap($value0, 0, 15, 10, 50);
        $value1 = $this->extractHashValue(1);
        $diamondHeight = self::remap($value1, 0, 15, 10, 50);

        $shape = buildShape($diamondWidth, $diamondHeight);

        $this->svg->setWidth($diamondWidth * 6);
        $this->svg->setHeight($diamondHeight * 3);

        $i = 0;

        for ($y = 0; $y < 6; $y++) {
            for ($x = 0; $x < 6; $x++) {
                $value = $this->extractHashValue($i);
                $opacity = self::fillOpacity($value);
                $fill = self::fillColor($value);

                $attributes = [
                    'fill' => $fill,
                    'fill-opacity' => $opacity,
                    'stroke' => self::STROKE_COLOR,
                    'stroke-opacity' => self::STROKE_OPACITY
                ];

                $dx = ($y % 2 === 0) ? 0 : $diamondWidth / 2;

                $node = SVGNode::polyline($shape);
                $node->setAttributes($attributes);
                $node->transform([
                    'translate' => [
                        $x * $diamondWidth - $diamondWidth / 2 + $dx,
                        $diamondHeight / 2 * $y - $diamondHeight / 2
                    ]
                ]);
                $this->svg->add($node);

                // Add an extra one at top-right, for tiling.
                if ($x === 0) {
                    $node = SVGNode::polyline($shape);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [
                            6 * $diamondWidth - $diamondWidth / 2 + $dx,
                            $diamondHeight / 2 * $y - $diamondHeight / 2
                        ]
                    ]);
                    $this->svg->add($node);
                }

                // Add an extra row at the end that matches the first row, for tiling.
                if ($y === 0) {
                    $node = SVGNode::polyline($shape);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [
                            $x * $diamondWidth - $diamondWidth / 2 + $dx,
                            $diamondHeight / 2 * 6 - $diamondHeight / 2
                        ]
                    ]);
                    $this->svg->add($node);
                }

                // Add an extra one at bottom-right, for tiling.
                if ($x === 0 && $y === 0) {
                    $node = SVGNode::polyline($shape);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [
                            6 * $diamondWidth - $diamondWidth / 2 + $dx,
                            $diamondHeight / 2 * 6 - $diamondHeight / 2
                        ]
                    ]);
                    $this->svg->add($node);
                }

                $i++;
            }
        }
    }

    private function generateOverlappingRings() {
        $scale = $this->extractHashValue(0);
        $ringSize = self::remap($scale, 0, 15, 10, 60);
        $strokeWidth = $ringSize / 4;

        $this->svg->setWidth($ringSize * 6);
        $this->svg->setHeight($ringSize * 6);

        $i = 0;

        for ($y = 0; $y < 6; $y++) {
            for ($x = 0; $x < 6; $x++) {
                $value = $this->extractHashValue($i);
                $opacity = self::fillOpacity($value);
                $fill = self::fillColor($value);

                $attributes = [
                    'fill' => 'none',
                    'stroke' => $fill,
                    'opacity' => $opacity,
                    'stroke-width' => $strokeWidth.'px'
                ];

                $node = SVGNode::circle($x * $ringSize, $y * $ringSize, $ringSize - $strokeWidth / 2);
                $node->setAttributes($attributes);
                $this->svg->add($node);

                // Add an extra one at top-right, for tiling.
                if ($x === 0) {
                    $node = SVGNode::circle(6 * $ringSize, $y * $ringSize, $ringSize - $strokeWidth / 2);
                    $node->setAttributes($attributes);
                    $this->svg->add($node);
                }

                if ($y === 0) {
                    $node = SVGNode::circle($x * $ringSize, 6 * $ringSize, $ringSize - $strokeWidth / 2);
                    $node->setAttributes($attributes);
                    $this->svg->add($node);
                }

                if ($x === 0 && $y === 0) {
                    $node = SVGNode::circle(6 * $ringSize, 6 * $ringSize, $ringSize - $strokeWidth / 2);
                    $node->setAttributes($attributes);
                    $this->svg->add($node);
                }

                $i++;
            }
        }
    }

    private function generateChevrons() {
        function buildShape($width, $height) {
            $e = $height * 0.66;
            return [
                implode(',', [
                    0, 0,
                    $width / 2, $height - $e,
                    $width / 2, $height,
                    0, $e,
                    0, 0
                ]),
                implode(',', [
                    $width / 2, $height - $e,
                    $width, 0,
                    $width, $e,
                    $width / 2, $height,
                    $width / 2, $height - $e
                ])
            ];
        }

        $value = $this->extractHashValue(0);
        $chevronWidth = self::remap($value, 0, 15, 30, 80);
        $chevronHeight = self::remap($value, 0, 15, 30, 80);
        $chevron = buildShape($chevronWidth, $chevronHeight);

        $this->svg->setWidth($chevronWidth * 6);
        $this->svg->setHeight($chevronHeight * 6 * 0.66);

        $i = 0;

        for ($y = 0; $y < 6; $y++) {
            for ($x = 0; $x < 6; $x++) {
                $value = $this->extractHashValue($i);
                $opacity = self::fillOpacity($value);
                $fill = self::fillColor($value);

                $attributes = [
                    'stroke' => self::STROKE_COLOR,
                    'stroke-opacity' => self::STROKE_OPACITY,
                    'fill' => $fill,
                    'fill-opacity' => $opacity,
                    'stroke-width' => 1
                ];

                $group = SVGNode::group();
                $group->setAttributes($attributes);
                $group->transform([
                    'translate' => [
                        $x * $chevronWidth,
                        $y * $chevronHeight * 0.66 - $chevronHeight / 2
                    ]
                ]);
                $this->svg->add($group);
                
                $node = SVGNode::polyline($chevron[0]);
                $group->add($node);

                $node = SVGNode::polyline($chevron[1]);
                $group->add($node);

                // Add an extra row at the end that matches the first row, for tiling.
                if ($y === 0) {
                    $group = SVGNode::group();
                    $group->setAttributes($attributes);
                    $group->transform([
                        'translate' => [
                            $x * $chevronWidth,
                            6 * $chevronHeight * 0.66 - $chevronHeight / 2
                        ]
                    ]);
                    $this->svg->add($group);
                    
                    $node = SVGNode::polyline($chevron[0]);
                    $group->add($node);

                    $node = SVGNode::polyline($chevron[1]);
                    $group->add($node);
                }

                $i++;
            }
        }
    }

    private function generateTriangles() {
        function buildShape($sideLength, $height) {
            $halfWidth = $sideLength / 2;
            return implode(',', [
                $halfWidth, 0,
                $sideLength, $height,
                0, $height,
                $halfWidth, 0
            ]);
        }

        $scale = $this->extractHashValue(0);
        $sideLength = self::remap($scale, 0, 15, 15, 80);
        $triangleHeight = $sideLength / 2 * sqrt(3);
        $triangle = buildShape($sideLength, $triangleHeight);

        $this->svg->setWidth($sideLength * 3);
        $this->svg->setHeight($triangleHeight * 6);

        $i = 0;

        for ($y = 0; $y < 6; $y++) {
            for ($x = 0; $x < 6; $x++) {
                $value = $this->extractHashValue($i);
                $opacity = self::fillOpacity($value);
                $fill = self::fillColor($value);

                $attributes = [
                    'fill' => $fill,
                    'fill-opacity' => $opacity,
                    'stroke' => self::STROKE_COLOR,
                    'stroke-opacity' => self::STROKE_OPACITY
                ];

                if ($y % 2 === 0) {
                    $rotation = $x % 2 === 0 ? 180 : 0;
                } else {
                    $rotation = $x % 2 !== 0 ? 180 : 0;
                }

                $node = SVGNode::polyline($triangle);
                $node->setAttributes($attributes);
                $node->transform([
                    'translate' => [
                        $x * $sideLength * 0.5 - $sideLength / 2,
                        $triangleHeight * $y
                    ],
                    'rotate' => [
                        $rotation,
                        $sideLength / 2,
                        $triangleHeight / 2
                    ]
                ]);
                $this->svg->add($node);

                // add an extra one at top-right, for tiling
                if ($x === 0) {
                    $node = SVGNode::polyline($triangle);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [
                            6 * $sideLength * 0.5 - $sideLength / 2,
                            $triangleHeight * $y
                        ],
                        'rotate' => [
                            $rotation,
                            $sideLength / 2,
                            $triangleHeight / 2
                        ]
                    ]);
                    $this->svg->add($node);
                }

                $i++;
            }
        }
    }

    private function generatePlusSigns() {
        $value = $this->extractHashValue(0);
        $squareSize = self::remap($value, 0, 15, 10, 25);
        $plusSize = $squareSize * 3;

        $this->svg->setWidth($squareSize * 12);
        $this->svg->setHeight($squareSize * 12);

        $i = 0;

        for ($y = 0; $y < 6; $y++) {
            for ($x = 0; $x < 6; $x++) {
                $value = $this->extractHashValue($i);
                $opacity = self::fillOpacity($value);
                $fill = self::fillColor($value);
                $dx = ($y % 2 === 0) ? 0 : 1;

                $attributes = [
                    'fill' => $fill,
                    'stroke' => self::STROKE_COLOR,
                    'stroke-opacity' => self::STROKE_OPACITY,
                    'fill-opacity' => $opacity
                ];

                $group = SVGNode::group();
                
                $node = SVGNode::rect($squareSize, 0, $squareSize, $squareSize * 3);
                $group->add($node);

                $node = SVGNode::rect(0, $squareSize, $squareSize * 3, $squareSize);
                $group->add($node);

                $group->setAttributes($attributes);
                $group->transform([
                    'translate' => [
                        $x * $plusSize - $x * $squareSize + $dx * $squareSize - $squareSize,
                        $y * $plusSize - $y * $squareSize - $plusSize / 2
                    ]
                ]);
                $this->svg->add($group);

                if ($x === 0) { // add an extra column on the right for tiling
                    $group = SVGNode::group();
                
                    $node = SVGNode::rect($squareSize, 0, $squareSize, $squareSize * 3);
                    $group->add($node);

                    $node = SVGNode::rect(0, $squareSize, $squareSize * 3, $squareSize);
                    $group->add($node);

                    $group->setAttributes($attributes);
                    $group->transform([
                        'translate' => [
                            4 * $plusSize - $x * $squareSize + $dx * $squareSize - $squareSize,
                            $y * $plusSize - $y * $squareSize - $plusSize / 2
                        ]
                    ]);
                    $this->svg->add($group);
                }

                if ($y === 0) { // add an extra row on the bottom that matches the first row for tiling
                    $group = SVGNode::group();

                    $node = SVGNode::rect($squareSize, 0, $squareSize, $squareSize * 3);
                    $group->add($node);

                    $node = SVGNode::rect(0, $squareSize, $squareSize * 3, $squareSize);
                    $group->add($node);

                    $group->setAttributes($attributes);
                    $group->transform([
                        'translate' => [
                            $x * $plusSize - $x * $squareSize + $dx * $squareSize - $squareSize,
                            4 * $plusSize - $y * $squareSize - $plusSize / 2
                        ]
                    ]);
                    $this->svg->add($group);
                }

                if ($x === 0 && $y === 0) { // add an extra one at top-right and bottom-right for tiling
                    $group = SVGNode::group();

                    $node = SVGNode::rect($squareSize, 0, $squareSize, $squareSize * 3);
                    $group->add($node);

                    $node = SVGNode::rect(0, $squareSize, $squareSize * 3, $squareSize);
                    $group->add($node);

                    $group->setAttributes($attributes);
                    $group->transform([
                        'translate' => [
                            4 * $plusSize - $x * $squareSize + $dx * $squareSize - $squareSize,
                            4 * $plusSize - $y * $squareSize - $plusSize / 2
                        ]
                    ]);
                    $this->svg->add($group);
                }

                $i++;
            }
        }
    }

    private function generateXes() {
        function buildGroup($squareSize) {
            $group = SVGNode::group();

            $node = SVGNode::rect($squareSize, 0, $squareSize, $squareSize * 3);
            $group->add($node);

            $node = SVGNode::rect(0, $squareSize, $squareSize * 3, $squareSize);
            $group->add($node);
            return $group;
        }

        $value = $this->extractHashValue(0);
        $squareSize = self::remap($value, 0, 15, 10, 25);
        $xSize = $squareSize * 3 * 0.943;
        $rotation = [45, $xSize / 2, $xSize / 2];
        $this->svg->setWidth($xSize * 3);
        $this->svg->setHeight($xSize * 3);

        $i = 0;

        for ($y = 0; $y < 6; $y++) {
            for ($x = 0; $x < 6; $x++) {
                $value = $this->extractHashValue($i);
                $opacity = self::fillOpacity($value);
                $fill = self::fillColor($value);
                $dy = $x % 2 === 0 ? $y * $xSize - $xSize / 2 : $y * $xSize - $xSize / 2 + $xSize / 4;

                $attributes = [
                    'fill' => $fill,
                    'opacity' => $opacity
                ];

                $group = buildGroup($squareSize);
                $group->setAttributes($attributes);
                $group->transform([
                    'translate' => [$x * $xSize / 2 - $xSize / 2, $dy - $y * $xSize / 2],
                    'rotate' => $rotation
                ]);
                $this->svg->add($group);

                if ($x === 0) { // add an extra column on the right for tiling
                    $group = buildGroup($squareSize);
                    $group->setAttributes($attributes);
                    $group->transform([
                        'translate' => [6 * $xSize / 2 - $xSize / 2, $dy - $y * $xSize / 2],
                        'rotate' => $rotation
                    ]);
                    $this->svg->add($group);
                }

                if ($y === 0) { // add an extra row on the bottom that matches the first row for tiling
                    $dy = $x % 2 === 0 ? 6 * $xSize - $xSize / 2 : 6 * $xSize - $xSize / 2 + $xSize / 4;

                    $group = buildGroup($squareSize);
                    $group->setAttributes($attributes);
                    $group->transform([
                        'translate' => [$x * $xSize / 2 - $xSize / 2, $dy - 6 * $xSize / 2],
                        'rotate' => $rotation
                    ]);
                    $this->svg->add($group);
                }

                if ($y === 5) { // put a row at the top for tiling
                    $group = buildGroup($squareSize);
                    $group->setAttributes($attributes);
                    $group->transform([
                        'translate' => [$x * $xSize / 2 - $xSize / 2, $dy - 11 * $xSize / 2],
                        'rotate' => $rotation
                    ]);
                    $this->svg->add($group);
                }

                if ($x === 0 && $y === 0) { // add an extra one at top-right and bottom-right for tiling
                    $group = buildGroup($squareSize);
                    $group->setAttributes($attributes);
                    $group->transform([
                        'translate' => [6 * $xSize / 2 - $xSize / 2, $dy - 6 * $xSize / 2],
                        'rotate' => $rotation
                    ]);
                    $this->svg->add($group);
                }

                $i++;
            }
        }
    }

    private function generatePlaid() {
        $height = 0;
        $width = 0;

        // horizontal stripes
        $i = 0;

        while ($i < 36) {
            $space = $this->extractHashValue($i);
            $height += $space + 5;

            $value = $this->extractHashValue($i + 1);
            $opacity = self::fillOpacity($value);
            $fill = self::fillColor($value);
            $stripeHeight = $value + 5;

            $node = SVGNode::rect(0, $height, '100%', $stripeHeight);
            $node->setAttributes([
                'opacity' => $opacity,
                'fill' => $fill
            ]);
            $this->svg->add($node);

            $height += $stripeHeight;
            $i += 2;
        }

        // vertical stripes
        $i = 0;

        while ($i < 36) {
            $space = $this->extractHashValue($i);
            $width += $space + 5;

            $value = $this->extractHashValue($i + 1);
            $opacity = self::fillOpacity($value);
            $fill = self::fillColor($value);
            $stripeWidth = $value + 5;

            $node = SVGNode::rect($width, 0, $stripeWidth, '100%');
            $node->setAttributes([
                'opacity' => $opacity,
                'fill' => $fill
            ]);
            $this->svg->add($node);

            $width += $stripeWidth;
            $i += 2;
        }

        $this->svg->setWidth($width);
        $this->svg->setHeight($height);
    }

    private function generateTessellation() {
        function buildShape($sideLength, $triangleWidth) {
            $halfHeight = $sideLength / 2;
            return implode(',', [
                0, 0,
                $triangleWidth, $halfHeight,
                0, $sideLength,
                0, 0
            ]);
        }

        $value = $this->extractHashValue(0);
        $sideLength = self::remap($value, 0, 15, 5, 40);
        $hexHeight = $sideLength * sqrt(3);
        $hexWidth = $sideLength * 2;
        $triangleHeight = $sideLength / 2 * sqrt(3);
        $shape = buildShape($sideLength, $triangleHeight);
        $width = $sideLength * 3 + $triangleHeight * 2;
        $height = ($hexHeight * 2) + ($sideLength * 2);

        $this->svg->setWidth($width);
        $this->svg->setHeight($height);

        for ($i = 0; $i < 20; $i++) {
            $value = $this->extractHashValue($i);
            $opacity = self::fillOpacity($value);
            $fill = self::fillColor($value);

            $attributes = [
                'stroke' => self::STROKE_COLOR,
                'stroke-opacity' => self::STROKE_OPACITY,
                'fill' => $fill,
                'fill-opacity' => $opacity,
                'stroke-width' => 1
            ];

            switch ($i) {
                case 0: // all 4 corners
                    $node = SVGNode::rect(- $sideLength / 2, - $sideLength / 2, $sideLength, $sideLength);
                    $node->setAttributes($attributes);
                    $this->svg->add($node);

                    $node = SVGNode::rect($width - $sideLength / 2, - $sideLength / 2, $sideLength, $sideLength);
                    $node->setAttributes($attributes);
                    $this->svg->add($node);

                    $node = SVGNode::rect(- $sideLength / 2, $height - $sideLength / 2, $sideLength, $sideLength);
                    $node->setAttributes($attributes);
                    $this->svg->add($node);

                    $node = SVGNode::rect($width - $sideLength / 2, $height - $sideLength / 2, $sideLength, $sideLength);
                    $node->setAttributes($attributes);
                    $this->svg->add($node);
                    break;
                case 1: // center / top square
                    $node = SVGNode::rect($hexWidth / 2 + $triangleHeight, $hexHeight / 2, $sideLength, $sideLength);
                    $node->setAttributes($attributes);
                    $this->svg->add($node);
                    break;
                case 2: // side squares
                    $node = SVGNode::rect(- $sideLength / 2, $height / 2 - $sideLength / 2, $sideLength, $sideLength);
                    $node->setAttributes($attributes);
                    $this->svg->add($node);

                    $node = SVGNode::rect($width - $sideLength / 2, $height / 2 - $sideLength / 2, $sideLength, $sideLength);
                    $node->setAttributes($attributes);
                    $this->svg->add($node);
                    break;
                case 3: // center / bottom square
                    $node = SVGNode::rect($hexWidth / 2 + $triangleHeight, $hexHeight * 1.5 + $sideLength, $sideLength, $sideLength);
                    $node->setAttributes($attributes);
                    $this->svg->add($node);
                    break;
                case 4: // left top / bottom triangle
                    $node = SVGNode::polyline($shape);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [$sideLength / 2, - $sideLength / 2],
                        'rotate' => [0, $sideLength / 2, $triangleHeight / 2]
                    ]);
                    $this->svg->add($node);

                    $node = SVGNode::polyline($shape);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [$sideLength / 2, $height + $sideLength / 2],
                        'rotate' => [0, $sideLength / 2, $triangleHeight / 2],
                        'scale' => [1, -1]
                    ]);
                    $this->svg->add($node);
                    break;
                case 5: // right top / bottom triangle
                    $node = SVGNode::polyline($shape);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [$width - $sideLength / 2, - $sideLength / 2],
                        'rotate' => [0, $sideLength / 2, $triangleHeight / 2],
                        'scale' => [-1, 1]
                    ]);
                    $this->svg->add($node);

                    $node = SVGNode::polyline($shape);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [$width - $sideLength / 2, $height + $sideLength / 2],
                        'rotate' => [0, $sideLength / 2, $triangleHeight / 2],
                        'scale' => [-1, -1]
                    ]);
                    $this->svg->add($node);
                    break;
                case 6: // center / top / right triangle
                    $node = SVGNode::polyline($shape);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [$width / 2 + $sideLength / 2, $hexHeight / 2]
                    ]);
                    $this->svg->add($node);
                    break;
                case 7: // center / top / left triangle
                    $node = SVGNode::polyline($shape);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [$width / 2 - $width / 2 - $sideLength / 2, $hexHeight / 2],
                        'scale' => [-1, 1]
                    ]);
                    $this->svg->add($node);
                    break;
                case 8: // center / bottom / right triangle
                    $node = SVGNode::polyline($shape);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [$width / 2 + $sideLength / 2, $height - $hexHeight / 2],
                        'scale' => [1, -1]
                    ]);
                    $this->svg->add($node);
                    break;
                case 9: // center / bottom / left triangle
                    $node = SVGNode::polyline($shape);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [$width - $width / 2 - $sideLength / 2, $height - $hexHeight / 2],
                        'scale' => [-1, -1]
                    ]);
                    $this->svg->add($node);
                    break;
                case 10: // left / middle triangle
                    $node = SVGNode::polyline($shape);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [$sideLength / 2, $height / 2 - $sideLength / 2]
                    ]);
                    $this->svg->add($node);
                    break;
                case 11: // right / middle triangle
                    $node = SVGNode::polyline($shape);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [$width - $sideLength / 2, $height / 2 - $sideLength / 2],
                        'scale' => [-1, 1]
                    ]);
                    $this->svg->add($node);
                    break;
                case 12: // left / top square
                    $node = SVGNode::rect(0, 0, $sideLength, $sideLength);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [$sideLength / 2, $sideLength / 2],
                        'rotate' => [-30, 0, 0]
                    ]);
                    $this->svg->add($node);
                    break;
                case 13: // right / top square
                    $node = SVGNode::rect(0, 0, $sideLength, $sideLength);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'scale' => [-1, 1],
                        'translate' => [- $width + $sideLength / 2, $sideLength / 2],
                        'rotate' => [-30, 0, 0]
                    ]);
                    $this->svg->add($node);
                    break;
                case 14: // left / center-top square
                    $node = SVGNode::rect(0, 0, $sideLength, $sideLength);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [$sideLength / 2, $height / 2 - $sideLength / 2 - $sideLength],
                        'rotate' => [30, 0, $sideLength]
                    ]);
                    $this->svg->add($node);
                    break;
                case 15: // right / center-top square
                    $node = SVGNode::rect(0, 0, $sideLength, $sideLength);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'scale' => [-1, 1],
                        'translate' => [- $width + $sideLength / 2, $height / 2 - $sideLength / 2 - $sideLength],
                        'rotate' => [30, 0, $sideLength]
                    ]);
                    $this->svg->add($node);
                    break;
                case 16: // left / center-top square
                    $node = SVGNode::rect(0, 0, $sideLength, $sideLength);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'scale' => [1, -1],
                        'translate' => [$sideLength / 2, - $height + $height / 2 - $sideLength / 2 - $sideLength],
                        'rotate' => [30, 0, $sideLength]
                    ]);
                    $this->svg->add($node);
                    break;
                case 17: // right / center-bottom square
                    $node = SVGNode::rect(0, 0, $sideLength, $sideLength);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'scale' => [-1, -1],
                        'translate' => [- $width + $sideLength / 2, - $height + $height / 2 - $sideLength / 2 - $sideLength],
                        'rotate' => [30, 0, $sideLength]
                    ]);
                    $this->svg->add($node);
                    break;
                case 18: // left / bottom square
                    $node = SVGNode::rect(0, 0, $sideLength, $sideLength);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'scale' => [1, -1],
                        'translate' => [$sideLength / 2, - $height + $sideLength / 2],
                        'rotate' => [-30, 0, 0]
                    ]);
                    $this->svg->add($node);
                    break;
                case 19: // right / bottom square
                    $node = SVGNode::rect(0, 0, $sideLength, $sideLength);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'scale' => [-1, -1],
                        'translate' => [- $width + $sideLength / 2, - $height + $sideLength / 2],
                        'rotate' => [-30, 0, 0]
                    ]);
                    $this->svg->add($node);
                    break;
            }
        }
    }

    private function generateMosaicSquares() {
        function buildShape($sideLength) { // right triangle
            return implode(',', [
                0, 0,
                $sideLength, $sideLength,
                0, $sideLength,
                0, 0
            ]);
        }

        $value = $this->extractHashValue(0);
        $triangleSize = self::remap($value, 0, 15, 15, 50);
        $shape = buildShape($triangleSize);

        $this->svg->setWidth($triangleSize * 8);
        $this->svg->setHeight($triangleSize * 8);

        $i = 0;

        for ($y = 0; $y < 4; $y++) {
            for ($x = 0; $x < 4; $x++) {
                $valueA = $this->extractHashValue($i);
                $valueB = $this->extractHashValue($i + 1);

                if ($x % 2 === 0) {
                    if ($y % 2 === 0) {
                        $this->drawOuterMosaicTile($x * $triangleSize * 2, $y * $triangleSize * 2, $triangleSize, $valueA, $shape);
                    }
                    else {
                        $this->drawInnerMosaicTile($x * $triangleSize * 2, $y * $triangleSize * 2, $triangleSize, $valueA, $valueB, $shape);
                    }
                }
                else {
                    if ($y % 2 === 0) {
                        $this->drawInnerMosaicTile($x * $triangleSize * 2, $y * $triangleSize * 2, $triangleSize, $valueA, $valueB, $shape);
                    }
                    else {
                        $this->drawOuterMosaicTile($x * $triangleSize * 2, $y * $triangleSize * 2, $triangleSize, $valueA, $shape);
                    }
                }

                $i++;
            }
        }
    }

    private function drawInnerMosaicTile($x, $y, $triangleSize, $valueA, $valueB, $shape) {
        $opacity = self::fillOpacity($valueA);
        $fill = self::fillColor($valueA);
        $attributes = [
            'stroke' => self::STROKE_COLOR,
            'stroke-opacity' => self::STROKE_OPACITY,
            'fill-opacity' => $opacity,
            'fill' => $fill
        ];

        $node = SVGNode::polyline($shape);
        $node->setAttributes($attributes);
        $node->transform([
            'translate' => [$x + $triangleSize, $y],
            'scale' => [-1, 1]
        ]);
        $this->svg->add($node);

        $node = SVGNode::polyline($shape);
        $node->setAttributes($attributes);
        $node->transform([
            'translate' => [$x + $triangleSize, $y + $triangleSize * 2],
            'scale' => [1, -1]
        ]);
        $this->svg->add($node);

        $opacity = self::fillOpacity($valueB);
        $fill = self::fillColor($valueB);
        $attributes = [
            'stroke' => self::STROKE_COLOR,
            'stroke-opacity' => self::STROKE_OPACITY,
            'fill-opacity' => $opacity,
            'fill' => $fill
        ];

        $node = SVGNode::polyline($shape);
        $node->setAttributes($attributes);
        $node->transform([
            'translate' => [$x + $triangleSize, $y + $triangleSize * 2],
            'scale' => [-1, -1]
        ]);
        $this->svg->add($node);

        $node = SVGNode::polyline($shape);
        $node->setAttributes($attributes);
        $node->transform([
            'translate' => [$x + $triangleSize, $y],
            'scale' => [1, 1]
        ]);
        $this->svg->add($node);
    }

    private function drawOuterMosaicTile($x, $y, $triangleSize, $value, $shape) {
        $opacity = self::fillOpacity($value);
        $fill = self::fillColor($value);
        $attributes = [
            'stroke' => self::STROKE_COLOR,
            'stroke-opacity' => self::STROKE_OPACITY,
            'fill-opacity' => $opacity,
            'fill' => $fill
        ];

        $node = SVGNode::polyline($shape);
        $node->setAttributes($attributes);
        $node->transform([
            'translate' => [$x, $y + $triangleSize],
            'scale' => [1, -1]
        ]);
        $this->svg->add($node);

        $node = SVGNode::polyline($shape);
        $node->setAttributes($attributes);
        $node->transform([
            'translate' => [$x + $triangleSize * 2, $y + $triangleSize],
            'scale' => [-1, -1]
        ]);
        $this->svg->add($node);

        $node = SVGNode::polyline($shape);
        $node->setAttributes($attributes);
        $node->transform([
            'translate' => [$x, $y + $triangleSize],
            'scale' => [1, 1]
        ]);
        $this->svg->add($node);

        $node = SVGNode::polyline($shape);
        $node->setAttributes($attributes);
        $node->transform([
            'translate' => [$x + $triangleSize * 2, $y + $triangleSize],
            'scale' => [-1, 1]
        ]);
        $this->svg->add($node);
    }

    private function generateFishScales() {
        $scale = $this->extractHashValue(0);
        $diameter = self::remap($scale, 0, 15, 25, 200);
        $radius = $diameter / 2;

        $path = 'M0 '.$radius
            .' A '.$radius.' '.$radius.' 0 1 0 '.$diameter.' '.$radius
            .' A '.$radius.' '.$radius.' 0 0 1 '.$radius.' 0'
            .' A '.$radius.' '.$radius.' 0 0 1 0 '.$radius
            .' Z';

        $this->svg->setWidth($diameter * 6);
        $this->svg->setHeight($radius * 6);

        $i = 0;

        for ($y = 0; $y < 6; $y++) {
            for ($x = 0; $x < 6; $x++) {
                $value = $this->extractHashValue($i);
                $opacity = self::fillOpacity($value);
                $fill = self::fillColor($value);
                $dx = $y % 2 ? 0 : - $radius;
                $attributes = [
                    'stroke' => self::STROKE_COLOR,
                    'stroke-opacity' => self::STROKE_OPACITY,
                    'fill' => $fill,
                    'fill-opacity' => $opacity
                ];

                $node = SVGNode::path($path);
                $node->setAttributes($attributes);
                $node->transform([
                    'translate' => [$x * $diameter + $dx, $y * $radius - $radius]
                ]);
                $this->svg->add($node);

                if ($x === 0) { // add an extra column on the right for tiling
                    $node = SVGNode::path($path);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [6 * $diameter + $dx, $y * $radius - $radius]
                    ]);
                    $this->svg->add($node);
                }

                if ($y === 0) { // add an extra row at the end that matches the first row, for tiling
                    $node = SVGNode::path($path);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [$x * $diameter + $dx, 6 * $radius - $radius]
                    ]);
                    $this->svg->add($node);
                }

                if ($x === 0 && $y === 0) { // add an extra one at bottom-right, for tiling
                    $node = SVGNode::path($path);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [6 * $diameter + $dx, 6 * $radius - $radius]
                    ]);
                    $this->svg->add($node);
                }

                $i++;
            }
        }
    }

    private function generateWaveFishScales() {
        $scale = $this->extractHashValue(0);
        $diameter = self::remap($scale, 0, 15, 25, 200);
        $radius = $diameter / 2;

        $this->svg->setWidth($diameter * 6);
        $this->svg->setHeight($diameter * 6);

        $i = 0;

        for ($y = 0; $y < 6; $y++) {
            for ($x = 0; $x < 3; $x++) {
                $dx = $y % 2 ? 0 : - $diameter;

                $group = $this->buildWaveFishScaleGroup($i, $diameter);
                $group->transform([
                    'translate' => [$x * $diameter * 2 + $dx - $radius, $y * $diameter - $diameter]
                ]);
                $this->svg->add($group);

                if ($x === 0) { // add an extra column on the right for tiling
                    $group = $this->buildWaveFishScaleGroup($i, $diameter);
                    $group->transform([
                        'translate' => [3 * $diameter * 2 + $dx - $radius, $y * $diameter - $diameter]
                    ]);
                    $this->svg->add($group);
                }

                if ($y === 0) { // add an extra row at the end that matches the first row, for tiling
                    $group = $this->buildWaveFishScaleGroup($i, $diameter);
                    $group->transform([
                        'translate' => [$x * $diameter * 2 + $dx - $radius, 6 * $diameter - $diameter]
                    ]);
                    $this->svg->add($group);
                }

                if ($x === 0 && $y === 0) { // add an extra one at bottom-right, for tiling
                    $group = $this->buildWaveFishScaleGroup($i, $diameter);
                    $group->transform([
                        'translate' => [3 * $diameter * 2 + $dx - $radius, 6 * $diameter - $diameter]
                    ]);
                    $this->svg->add($group);
                }

                $i++;
            }
        }
    }

    private function buildWaveFishScaleGroup($i, $diameter) {
        $radius = $diameter / 2;

        $path = 'M0 '.$radius
            .' A '.$radius.' '.$radius.' 0 1 0 '.$diameter.' '.$radius
            .' A '.$radius.' '.$radius.' 0 0 1 '.$radius.' 0'
            .' A '.$radius.' '.$radius.' 0 0 1 0 '.$radius
            .' Z';

        $group = SVGNode::group();

        // top scale
        $value = $this->extractHashValue($i);
        $opacity = self::fillOpacity($value);
        $fill = self::fillColor($value);

        $attributes = [
            'stroke' => self::STROKE_COLOR,
            'stroke-opacity' => self::STROKE_OPACITY,
            'fill' => $fill,
            'fill-opacity' => $opacity
        ];

        $node = SVGNode::path($path);
        $node->setAttributes($attributes);
        $group->add($node);

        // right scale
        $value = $this->extractHashValue($i + 1);
        $opacity = self::fillOpacity($value);
        $fill = self::fillColor($value);

        $attributes = [
            'stroke' => self::STROKE_COLOR,
            'stroke-opacity' => self::STROKE_OPACITY,
            'fill' => $fill,
            'fill-opacity' => $opacity
        ];

        $node = SVGNode::path($path);
        $node->setAttributes($attributes);
        $node->transform([
            'rotate' => [-90, $radius, $radius],
            'translate' => [- $radius, $radius]
        ]);
        $group->add($node);

        // left scale
        $value = $this->extractHashValue($i + 2);
        $opacity = self::fillOpacity($value);
        $fill = self::fillColor($value);

        $attributes = [
            'stroke' => self::STROKE_COLOR,
            'stroke-opacity' => self::STROKE_OPACITY,
            'fill' => $fill,
            'fill-opacity' => $opacity
        ];

        $node = SVGNode::path($path);
        $node->setAttributes($attributes);
        $node->transform([
            'rotate' => [90, $radius, $radius],
            'translate' => [$radius, $radius]
        ]);
        $group->add($node);

        // bottom scale
        $value = $this->extractHashValue($i + 3);
        $opacity = self::fillOpacity($value);
        $fill = self::fillColor($value);

        $attributes = [
            'stroke' => self::STROKE_COLOR,
            'stroke-opacity' => self::STROKE_OPACITY,
            'fill' => $fill,
            'fill-opacity' => $opacity
        ];

        $node = SVGNode::path($path);
        $node->setAttributes($attributes);
        $node->transform([
            'rotate' => [180, $radius, $radius],
            'translate' => [0, - $diameter]
        ]);
        $group->add($node);
        return $group;
    }

    private function generateCubes() {
        $scale = $this->extractHashValue(0);
        $size = self::remap($scale, 0, 15, 25, 200);

        $this->svg->setWidth($size * 6);
        $this->svg->setHeight($size * 6 * 0.75);

        $i = 0;

        for ($y = 0; $y < 6; $y++) {
            for ($x = 0; $x < 6; $x++) {
                $dx = $y % 2 ? 0 : $size / 2;

                $group = $this->buildCube($i, $size);
                $group->transform([
                    'translate' => [$x * $size - $size / 2 + $dx, $y * 0.75 * $size - $size / 2]
                ]);
                $this->svg->add($group);

                if ($x === 0) { // add an extra column on the right for tiling
                    $group = $this->buildCube($i, $size);
                    $group->transform([
                        'translate' => [6 * $size - $size / 2 + $dx, $y * 0.75 * $size - $size / 2]
                    ]);
                    $this->svg->add($group);
                }

                if ($y === 0) { // add an extra row at the end that matches the first row, for tiling
                    $group = $this->buildCube($i, $size);
                    $group->transform([
                        'translate' => [$x * $size - $size / 2 + $dx, 6 * 0.75 * $size - $size / 2]
                    ]);
                    $this->svg->add($group);
                }

                if ($x === 0 && $y === 0) { // add an extra one at bottom-right, for tiling
                    $group = $this->buildCube($i, $size);
                    $group->transform([
                        'translate' => [6 * $size - $size / 2 + $dx, 6 * 0.75 * $size - $size / 2]
                    ]);
                    $this->svg->add($group);
                }

                $i++;
            }
        }
    }

    private function buildCube($i, $size) {
        $group = SVGNode::group();

        // left face
        $shape = implode(',', [
            0, 0.25 * $size,
            0, 0.75 * $size,
            0.5 * $size, $size, 
            0.5 * $size, 0.5 * $size,
            0, 0.25 * $size
        ]);

        $value = $this->extractHashValue($i);
        $opacity = self::fillOpacity($value);
        $fill = self::fillColor($value);

        $attributes = [
            'stroke' => self::STROKE_COLOR,
            'stroke-opacity' => self::STROKE_OPACITY,
            'fill' => $fill,
            'fill-opacity' => $opacity
        ];

        $node = SVGNode::polyline($shape);
        $node->setAttributes($attributes);
        $group->add($node);

        // right face
        $shape = implode(',', [
            0.5 * $size, 0.5 * $size,
            0.5 * $size, $size,
            $size, 0.75 * $size, 
            $size, 0.25 * $size,
            0.5 * $size, 0.5 * $size
        ]);

        $value = $this->extractHashValue($i + 1);
        $opacity = self::fillOpacity($value);
        $fill = self::fillColor($value);

        $attributes = [
            'stroke' => self::STROKE_COLOR,
            'stroke-opacity' => self::STROKE_OPACITY,
            'fill' => $fill,
            'fill-opacity' => $opacity
        ];

        $node = SVGNode::polyline($shape);
        $node->setAttributes($attributes);
        $group->add($node);

        // top face
        $shape = implode(',', [
            0.5 * $size, 0,
            0, 0.25 * $size,
            0.5 * $size, 0.5 * $size,
            $size, 0.25 * $size, 
            0.5 * $size, 0
        ]);

        $value = $this->extractHashValue($i + 2);
        $opacity = self::fillOpacity($value);
        $fill = self::fillColor($value);

        $attributes = [
            'stroke' => self::STROKE_COLOR,
            'stroke-opacity' => self::STROKE_OPACITY,
            'fill' => $fill,
            'fill-opacity' => $opacity
        ];

        $node = SVGNode::polyline($shape);
        $node->setAttributes($attributes);
        $group->add($node);

        return $group;
    }

    private function generateTest() {
        $scale = $this->extractHashValue(0);
        $size = self::remap($scale, 0, 15, 25, 200);

        $this->svg->setWidth($size);
        $this->svg->setHeight($size);

        $line = '0,0 '.$size.','.$size;

        $value = $this->extractHashValue(8);
        $opacity = self::fillOpacity($value);
        $fill = self::fillColor($value);

        $attributes = [
            'stroke' => self::STROKE_COLOR,
            'stroke-opacity' => self::STROKE_OPACITY,
            'stroke-width' => 40,
            'fill' => $fill,
            'fill-opacity' => $opacity
        ];

        $node = SVGNode::polyline($line);
        $node->setAttributes($attributes);
        /*
        $node->transform([
            'translate' => [
                - $period / 4,
                    - $waveWidth * 16 + $waveWidth * $i - $amplitude * 1.5
                ]
        ]);
         */
        $this->svg->add($node);
    }
}
