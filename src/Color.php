<?php
namespace Osyra\Tera;

class Color {
    public static function hex2rgb($hex) {
        // expand shorthand form (e.g. "03F") to full form (e.g. "0033FF")
        $regex = '/^#?([a-f\d])([a-f\d])([a-f\d])$/i';
        $hex = preg_replace_callback($regex, function($matches) {
            $r = $matches[1];
            $g = $matches[2];
            $b = $matches[3];
            return $r.$r.$g.$g.$b.$b;
        }, $hex);

        $regex = '/^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i';

        if(preg_match($regex, $hex, $matches) === false) {
            throw new \Exception('Invalid hex value');
        }

        return ['r' => hexdec($matches[1]), 'g' => hexdec($matches[2]), 'b' => hexdec($matches[3])];
    }

    /**
     * Converts an RGB color value to a hex string.
     * @param  Object rgb RGB as r, g, and b keys
     * @return String     Hex color string
     */
    public static function rgb2hex($rgb) {
        $hex = '#';

        foreach(['r', 'g', 'b'] as $key) {
            $hex .= str_pad(dechex($rgb[$key]), 2, '0');
        }

        return $hex;
    }

    /**
     * Converts an RGB color value to HSL. Conversion formula adapted from
     * http://en.wikipedia.org/wiki/HSL_color_space. This function adapted
     * from http://stackoverflow.com/a/9493060.
     * Assumes r, g, and b are contained in the set [0, 255] and
     * returns h, s, and l in the set [0, 1].
     *
     * @param   Object  rgb     RGB as r, g, and b keys
     * @return  Object          HSL as h, s, and l keys
     */
    public static function rgb2hsl($rgb) {
        $r = $rgb['r'];
        $g = $rgb['g'];
        $b = $rgb['b'];

        $r /= 255;
        $g /= 255;
        $b /= 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);

        $h = $s = $l = ($max + $min) / 2;

        if($max === $min) {
            $h = $s = 0; // achromatic
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

            switch ($max) {
                case $r: $h = ($g - $b) / $d + ($g < $b ? 6 : 0); break;
                case $g: $h = ($b - $r) / $d + 2; break;
                case $b: $h = ($r - $g) / $d + 4; break;
            }

            $h /= 6;
        }

        return [
            'h' => $h,
            's' => $s,
            'l' => $l
        ];
    }

    /**
     * Converts an HSL color value to RGB. Conversion formula adapted from
     * http://en.wikipedia.org/wiki/HSL_color_space. This function adapted
     * from http://stackoverflow.com/a/9493060.
     * Assumes h, s, and l are contained in the set [0, 1] and
     * returns r, g, and b in the set [0, 255].
     *
     * @param   Object  hsl     HSL as h, s, and l keys
     * @return  Object          RGB as r, g, and b values
     */
    public static function hsl2rgb($hsl) {
        function hue2rgb($p, $q, $t) {
            if ($t < 0) $t += 1;
            if ($t > 1) $t -= 1;
            if ($t < 1 / 6) return $p + ($q - $p) * 6 * $t;
            if ($t < 1 / 2) return $q;
            if ($t < 2 / 3) return $p + ($q - $p) * (2 / 3 - $t) * 6;
            return $p;
        }

        $h = $hsl['h'];
        $s = $hsl['s'];
        $l = $hsl['l'];

        if($s === 0) {
            $r = $g = $b = $l; // achromatic
        }
        else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = hue2rgb($p, $q, $h + 1 / 3);
            $g = hue2rgb($p, $q, $h);
            $b = hue2rgb($p, $q, $h - 1 / 3);
        }

        return [
            'r' => round($r * 255),
            'g' => round($g * 255),
            'b' => round($b * 255)
        ];
    }

    public static function rgb2rgbString($rgb) {
        $r = $rgb['r'];
        $g = $rgb['g'];
        $b = $rgb['b'];

		return 'rgb('.implode(',', [$r, $g, $b]).')';
	}
}
