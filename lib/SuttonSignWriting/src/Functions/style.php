<?php
require_once __DIR__ . '/../Unicode.php';
require_once __DIR__ . '/../regex.php';
require_once __DIR__ . '/../convert.php';
require_once __DIR__ . '/../style.php';

function style_parse($style_string) { return \Sgnw\Core\Style\style_parse($style_string); }
function style_compose($style_dict) { return \Sgnw\Core\Style\style_compose($style_dict); }
function style_rgb_to_hex($rgb, $tolerance = 0.0) { return \Sgnw\Core\Style\style_rgb_to_hex($rgb, $tolerance); }
function style_rgba_to_hex($color, $background) { return \Sgnw\Core\Style\style_rgba_to_hex($color, $background); }
function style_merge($style1, $style2) { return \Sgnw\Core\Style\style_merge($style1, $style2); }

