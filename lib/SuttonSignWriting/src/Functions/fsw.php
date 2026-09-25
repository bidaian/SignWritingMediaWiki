<?php
require_once __DIR__ . '/../Unicode.php';
require_once __DIR__ . '/../regex.php';
require_once __DIR__ . '/../convert.php';
require_once __DIR__ . '/../fsw.php';

function fsw_structure_kind() { return \Sgnw\Core\Fsw\fsw_structure_kind(); }
function fsw_structure_category() { return \Sgnw\Core\Fsw\fsw_structure_category(); }
function fsw_structure_group() { return \Sgnw\Core\Fsw\fsw_structure_group(); }
function fsw_structure_ranges() { return \Sgnw\Core\Fsw\fsw_structure_ranges(); }
function fsw_is_type($sym_key, $type_name) { return \Sgnw\Core\Fsw\fsw_is_type($sym_key, $type_name); }
function fsw_colors() { return \Sgnw\Core\Fsw\fsw_colors(); }
function fsw_colorize($key) { return \Sgnw\Core\Fsw\fsw_colorize($key); }
function fsw_parse_symbol($fsw_sym) { return \Sgnw\Core\Fsw\fsw_parse_symbol($fsw_sym); }
function fsw_parse_sign($fsw_sign) { return \Sgnw\Core\Fsw\fsw_parse_sign($fsw_sign); }
function fsw_parse_text($fsw_text) { return \Sgnw\Core\Fsw\fsw_parse_text($fsw_text); }
function fsw_compose_symbol($fsw_sym_object) { return \Sgnw\Core\Fsw\fsw_compose_symbol($fsw_sym_object); }
function fsw_compose_sign($fsw_sign_object) { return \Sgnw\Core\Fsw\fsw_compose_sign($fsw_sign_object); }
function fsw_info($fsw) { return \Sgnw\Core\Fsw\fsw_info($fsw); }
function fsw_column_defaults() { return \Sgnw\Core\Fsw\fsw_column_defaults(); }
function fsw_column_defaults_merge($options = null) { return \Sgnw\Core\Fsw\fsw_column_defaults_merge($options); }
function fsw_columns($fsw_text, $options = null) { return \Sgnw\Core\Fsw\fsw_columns($fsw_text, $options); }
function fsw_tokenize($fsw, $sequence = true, $signbox = true, $sep = '[SEP]') { return \Sgnw\Core\Fsw\fsw_tokenize($fsw, $sequence, $signbox, $sep); }
function fsw_detokenize($tokens, $special_tokens = null) { return \Sgnw\Core\Fsw\fsw_detokenize($tokens, $special_tokens); }
function fsw_chunk_tokens($tokens, $chunk_size, $cls = '[CLS]', $sep = '[SEP]', $pad = '[PAD]') { return \Sgnw\Core\Fsw\fsw_chunk_tokens($tokens, $chunk_size, $cls, $sep, $pad); }
function fsw_tokenizer($special_tokens = null, $starting_index = null) { return new \Sgnw\Core\Fsw\FSWTokenizer($special_tokens, $starting_index); }

