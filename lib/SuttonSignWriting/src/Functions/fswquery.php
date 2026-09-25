<?php
require_once __DIR__ . '/../Unicode.php';
require_once __DIR__ . '/../regex.php';
require_once __DIR__ . '/../convert.php';
require_once __DIR__ . '/../fsw.php';
require_once __DIR__ . '/../fswquery.php';

function fswquery_parse($fsw_query_string) { return \Sgnw\Core\FswQuery\fswquery_parse($fsw_query_string); }
function fswquery_compose($fsw_query_object) { return \Sgnw\Core\FswQuery\fswquery_compose($fsw_query_object); }
function fsw_to_query($fsw_sign, $flags) { return \Sgnw\Core\FswQuery\fsw_to_query($fsw_sign, $flags); }
function fswquery_range($min_, $max_, $hex_ = false) { return \Sgnw\Core\FswQuery\fswquery_range($min_, $max_, $hex_); }
function fswquery_regex($query) { return \Sgnw\Core\FswQuery\fswquery_regex($query); }
function fswquery_results($query, $text) { return \Sgnw\Core\FswQuery\fswquery_results($query, $text); }
function fswquery_lines($query, $text) { return \Sgnw\Core\FswQuery\fswquery_lines($query, $text); }

