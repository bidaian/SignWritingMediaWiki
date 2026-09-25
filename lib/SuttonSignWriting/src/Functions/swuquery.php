<?php
require_once __DIR__ . '/../Unicode.php';
require_once __DIR__ . '/../regex.php';
require_once __DIR__ . '/../convert.php';
require_once __DIR__ . '/../swu.php';
require_once __DIR__ . '/../swuquery.php';

function swuquery_parse($swu_query_string) { return \Sgnw\Core\SwuQuery\swuquery_parse($swu_query_string); }
function swuquery_compose($swu_query_object) { return \Sgnw\Core\SwuQuery\swuquery_compose($swu_query_object); }
function swu_to_query($swu_sign, $flags) { return \Sgnw\Core\SwuQuery\swu_to_query($swu_sign, $flags); }
function swuquery_range($min_char, $max_char) { return \Sgnw\Core\SwuQuery\swuquery_range($min_char, $max_char); }
function swuquery_regex($query) { return \Sgnw\Core\SwuQuery\swuquery_regex($query); }
function swuquery_results($query, $text) { return \Sgnw\Core\SwuQuery\swuquery_results($query, $text); }
function swuquery_lines($query, $text) { return \Sgnw\Core\SwuQuery\swuquery_lines($query, $text); }

