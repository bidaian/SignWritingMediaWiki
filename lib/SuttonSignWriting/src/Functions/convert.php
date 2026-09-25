<?php
require_once __DIR__ . '/../Unicode.php';
require_once __DIR__ . '/../regex.php';
require_once __DIR__ . '/../convert.php';

function swu_to_mark($swuMark) { return \Sgnw\Core\Convert\swu_to_mark($swuMark); }
function mark_to_swu($fswMark) { return \Sgnw\Core\Convert\mark_to_swu($fswMark); }
function swu_to_num($swuNum) { return \Sgnw\Core\Convert\swu_to_num($swuNum); }
function num_to_swu($num) { return \Sgnw\Core\Convert\num_to_swu($num); }
function swu_to_coord($swuCoord) { return \Sgnw\Core\Convert\swu_to_coord($swuCoord); }
function coord_to_swu($coord) { return \Sgnw\Core\Convert\coord_to_swu($coord); }
function fsw_to_coord($fswCoord) { return \Sgnw\Core\Convert\fsw_to_coord($fswCoord); }
function coord_to_fsw($coord) { return \Sgnw\Core\Convert\coord_to_fsw($coord); }
function swu_to_code($swuSym) { return \Sgnw\Core\Convert\swu_to_code($swuSym); }
function code_to_swu($code) { return \Sgnw\Core\Convert\code_to_swu($code); }
function swu_to_id($swuSym) { return \Sgnw\Core\Convert\swu_to_id($swuSym); }
function id_to_swu($id) { return \Sgnw\Core\Convert\id_to_swu($id); }
function key_to_id($key) { return \Sgnw\Core\Convert\key_to_id($key); }
function id_to_key($id) { return \Sgnw\Core\Convert\id_to_key($id); }
function swu_to_key($swuSym) { return \Sgnw\Core\Convert\swu_to_key($swuSym); }
function key_to_swu($key) { return \Sgnw\Core\Convert\key_to_swu($key); }
function swu_to_fsw($swuText) { return \Sgnw\Core\Convert\swu_to_fsw($swuText); }
function fsw_to_swu($fswText) { return \Sgnw\Core\Convert\fsw_to_swu($fswText); }
function symid_arr() { return \Sgnw\Core\Convert\symid_arr(); }
function symid_max($symid_min) { return \Sgnw\Core\Convert\symid_max($symid_min); }
function symid_min($symid_max) { return \Sgnw\Core\Convert\symid_min($symid_max); }
function symid_to_key($symid) { return \Sgnw\Core\Convert\symid_to_key($symid); }
function key_to_symid($key) { return \Sgnw\Core\Convert\key_to_symid($key); }

