<?php
require_once __DIR__ . '/../Unicode.php';
require_once __DIR__ . '/../regex.php';
require_once __DIR__ . '/../convert.php';
require_once __DIR__ . '/../swu.php';

function swu_structure_kind() { return \Sgnw\Core\Swu\swu_structure_kind(); }
function swu_structure_category() { return \Sgnw\Core\Swu\swu_structure_category(); }
function swu_structure_group() { return \Sgnw\Core\Swu\swu_structure_group(); }
function swu_structure_ranges() { return \Sgnw\Core\Swu\swu_structure_ranges(); }
function swu_is_type($swu_char, $type_name) { return \Sgnw\Core\Swu\swu_is_type($swu_char, $type_name); }
function swu_colors() { return \Sgnw\Core\Swu\swu_colors(); }
function swu_colorize($sym) { return \Sgnw\Core\Swu\swu_colorize($sym); }
function swu_parse_symbol($swu_sym) { return \Sgnw\Core\Swu\swu_parse_symbol($swu_sym); }
function swu_parse_sign($swu_sign) { return \Sgnw\Core\Swu\swu_parse_sign($swu_sign); }
function swu_parse_text($swu_text) { return \Sgnw\Core\Swu\swu_parse_text($swu_text); }
function swu_compose_symbol($swu_sym_object) { return \Sgnw\Core\Swu\swu_compose_symbol($swu_sym_object); }
function swu_compose_sign($swu_sign_object) { return \Sgnw\Core\Swu\swu_compose_sign($swu_sign_object); }
function swu_info($swu) { return \Sgnw\Core\Swu\swu_info($swu); }
function swu_column_defaults() { return \Sgnw\Core\Swu\swu_column_defaults(); }
function swu_column_defaults_merge($options = null) { return \Sgnw\Core\Swu\swu_column_defaults_merge($options); }
function swu_columns($swu_text, $options = null) { return \Sgnw\Core\Swu\swu_columns($swu_text, $options); }

