<?php
chdir(dirname(__FILE__));
require_once('src/SVG.php');
require_once('src/SVGNode.php');
require_once('src/Color.php');
require_once('src/Pattern.php');

$pattern = new \Osyra\Tera\Pattern($argv[1], ['generator' => 'Test']);
#$pattern = new \Osyra\Tera\Pattern(uniqid());

$html = '<html>';
$html .= '<head>';
$html .= '<script>window.setTimeout(function() { window.location.reload() }, 1000);</script>';
$html .= '<style type="text/css">';
$html .= 'BODY { margin: 0; padding: 0; height: 100%; width: 100%; background-image: '.$pattern->toDataURL().'; background-repeat: repeat }';
$html .= '</style>';
$html .= '</head>';
$html .= '</html>';
file_put_contents('/mnt/e/tmp/test.html', $html);
file_put_contents('/mnt/e/tmp/test.svg', $pattern);
