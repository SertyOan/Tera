<?php
namespace Osyra\Tera\Generators;

class Hexagons extends AbstractGenerator {
    public function generate() {
        function buildShape($sideLength) {
            $c = $sideLength;
            $a = $c / 2;
            $b = sin(60 * pi() / 180) * $c;
            return implode(',', [
                0, $b,
                $a, 0,
                $a + $c, 0,
                2 * $c, $b,
                $a + $c, 2 * $b,
                $a, 2 * $b,
                0, $b
            ]);
        }

        $scale = $this->pattern->extractHashValue(0);
        $sideLength = self::remap($scale, 0, 15, 8, 60);
        $hexHeight = $sideLength * sqrt(3);
        $hexWidth = $sideLength * 2;
        $hex = buildShape($sideLength);

        $this->svg->setWidth($hexWidth * 3 + $sideLength * 3);
        $this->svg->setHeight($hexHeight * 6);

        $i = 0;

        for ($y = 0; $y < 6; $y++) {
            for ($x = 0; $x < 6; $x++) {
                $val = $this->extractHashValue($i);
                $dy = $x % 2 === 0 ? $y * $hexHeight : $y * $hexHeight + $hexHeight / 2;
                $opacity = $this->fillOpacity($val);
                $fill = $this->fillColor($val);

                $attributes = [
                    'fill' => $fill,
                    'fill-opacity' => $opacity,
                    'stroke' => self::STROKE_COLOR,
                    'stroke-opacity' => self::STROKE_OPACITY
                ];

                $node = SVGNode::polyline($hex);
                $node->setAttributes($attributes);
                $node->transform([
                    'translate' => [
                        $x * $sideLength * 1.5 - $hexWidth / 2,
                        $dy - $hexHeight / 2
                    ]
                ]);
                $this->svg->add($node);

                // Add an extra one at top-right, for tiling.
                if ($x === 0) {
                    $node = SVGNode::polyline($hex);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [
                            6 * $sideLength * 1.5 - $hexWidth / 2,
                            $dy - $hexHeight / 2
                        ]
                    ]);
                    $this->svg->add($node);
                }

                // Add an extra row at the end that matches the first row, for tiling.
                if ($y === 0) {
                    $dy = $x % 2 === 0 ? 6 * $hexHeight : 6 * $hexHeight + $hexHeight / 2;

                    $node = SVGNode::polyline($hex);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [
                            $x * $sideLength * 1.5 - $hexWidth / 2,
                            $dy - $hexHeight / 2
                        ]
                    ]);
                    $this->svg->add($node);
                }

                // Add an extra one at bottom-right, for tiling.
                if ($x === 0 && $y === 0) {
                    $node = SVGNode::polyline($hex);
                    $node->setAttributes($attributes);
                    $node->transform([
                        'translate' => [
                            6 * $sideLength * 1.5 - $hexWidth / 2,
                            5 * $hexHeight + $hexHeight / 2
                        ]
                    ]);
                    $this->svg->add($node);
                }

                $i++;
            }
        }
    }
}
