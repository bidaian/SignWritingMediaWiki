<?php
namespace Sgnw\Core\SwuQuery;

use Sgnw\Core\Convert;
use Sgnw\Core\Regex;
use Sgnw\Core\Swu;
use Sgnw\Core\Unicode;

/**
 * The swuquery module contains functions for handling the SWU query language.
 *
 * Query Language definition:
 * https://datatracker.ietf.org/doc/id/draft-slevinski-formal-signwriting-10.html#name-query-language
 */

// ----------------------------
// SWU Query Parsing
// ----------------------------

function _swuquery_parse_prefix($text)
{
    if ($text === 'T') {
        return ['required' => true];
    }
    $processed_parts = [];
    if (preg_match_all('/' . Regex\SWUQUERY_PATTERN_LIST . '/u', $text, $matches)) {
        foreach ($matches[0] as $part) {
            if (strpos($part, 'o') !== false) {
                $processed = ['or_list'];
                if (preg_match_all('/' . Regex\SWUQUERY_PATTERN_ITEM . '/u', $part, $m2)) {
                    foreach ($m2[0] as $or_part) {
                        $first = Unicode::substrCp($or_part, 0, 1);
                        if ($first !== 'R') {
                            $processed[] = $or_part;
                        } else {
                            $processed[] = [
                                Unicode::substrCp($or_part, 1, 1),
                                Unicode::substrCp($or_part, 2, 1),
                            ];
                        }
                    }
                }
                $processed_parts[] = $processed;
            } else {
                $first = Unicode::substrCp($part, 0, 1);
                if ($first !== 'R') {
                    $processed_parts[] = $part;
                } else {
                    $processed_parts[] = [
                        Unicode::substrCp($part, 1, 1),
                        Unicode::substrCp($part, 2, 1),
                    ];
                }
            }
        }
    }
    $result = ['required' => true];
    if (!empty($processed_parts)) {
        $result['parts'] = $processed_parts;
    }
    return $result;
}

/**
 * Parse a signbox query string into a list of query elements.
 *
 * @param string $text A string representing the signbox query.
 * @return array A list of symbols, ranges, or OR groups.
 * @example
 * \Sgnw\Core\SwuQuery\_swuquery_parse_signbox('񀀁𝤆𝤆');
 * // [['symbol' => '񀀁', 'coord' => [500, 500]]]
 */
function _swuquery_parse_signbox($text)
{
    $processed = [];
    if (!preg_match_all('/' . Regex\SWUQUERY_PATTERN_LIST . Regex\SWUQUERY_PATTERN_COORD . '/u', $text, $matches)) {
        return $processed;
    }
    foreach ($matches[0] as $item) {
        $coord_str = '';
        if (preg_match('/' . Regex\SWUQUERY_PATTERN_COORD . '$/u', $item, $cm)) {
            $coord_str = $cm[0];
        }
        if ($coord_str !== '') {
            $front = mb_substr($item, 0, mb_strlen($item, 'UTF-8') - mb_strlen($coord_str, 'UTF-8'), 'UTF-8');
            $coord = Convert\swu_to_coord($coord_str);
        } else {
            $front = $item;
            $coord = null;
        }

        if (strpos($front, 'o') !== false) {
            $or_parts = explode('o', $front);
            $or_list = [];
            foreach ($or_parts as $or_part) {
                $first = Unicode::substrCp($or_part, 0, 1);
                if ($first !== 'R') {
                    $or_list[] = $or_part;
                } else {
                    $or_list[] = [
                        Unicode::substrCp($or_part, 1, 1),
                        Unicode::substrCp($or_part, 2, 1),
                    ];
                }
            }
            $query_or = ['or_list' => $or_list];
            if ($coord !== null) {
                $query_or['coord'] = $coord;
            }
            $processed[] = $query_or;
        } elseif (Unicode::substrCp($front, 0, 1) !== 'R') {
            $query_symbol = ['symbol' => $front];
            if ($coord !== null) {
                $query_symbol['coord'] = $coord;
            }
            $processed[] = $query_symbol;
        } else {
            $query_range = ['range' => [Unicode::substrCp($front, 1, 1), Unicode::substrCp($front, 2, 1)]];
            if ($coord !== null) {
                $query_range['coord'] = $coord;
            }
            $processed[] = $query_range;
        }
    }
    return $processed;
}

/**
 * Parse an SWU query string to a structured dictionary.
 *
 * @param string $swu_query_string an SWU query string
 * @return array Dictionary representing the query structure
 * @example
 * \Sgnw\Core\SwuQuery\swuquery_parse('QA񀀁R񀀁񆆑񆇡T񆀁R񀀁񀇱𝤆𝤆V5-');
 * // ['query' => true, 'prefix' => ['required' => true, 'parts' => ['񀀁', ['񀀁', '񆆑'], '񆇡']], 'signbox' => [['symbol' => '񆀁'], ['range' => ['񀀁', '񀇱'], 'coord' => [500, 500]]], 'variance' => 5, 'style' => true]
 */
function swuquery_parse($swu_query_string)
{
    $pattern = '/^' . Regex\SWUQUERY_PATTERN_FULL . '$/u';
    if (!preg_match($pattern, $swu_query_string, $m)) {
        return ['query' => false];
    }

    $result = ['query' => true];
    $prefix = isset($m[1]) && $m[1] !== '' ? _swuquery_parse_prefix($m[1]) : null;
    if ($prefix !== null) {
        $result['prefix'] = $prefix;
    }
    $signbox = isset($m[2]) && $m[2] !== '' ? _swuquery_parse_signbox($m[2]) : null;
    if ($signbox !== null) {
        $result['signbox'] = $signbox;
    }
    $variance = isset($m[3]) && $m[3] !== '' ? intval(substr($m[3], 1)) : null;
    if ($variance !== null) {
        $result['variance'] = $variance;
    }
    $style = isset($m[4]) && $m[4] !== '' ? true : null;
    if ($style !== null) {
        $result['style'] = $style;
    }

    return $result;
}

// ----------------------------
// SWU Query Compose
// ----------------------------

/**
 * Function to compose SWU query string from object.
 *
 * @param array $swu_query_object Dictionary of query elements
 * @return string|null SWU query string
 * @example
 * \Sgnw\Core\SwuQuery\swuquery_compose([
 *     'query' => true,
 *     'prefix' => ['required' => true, 'parts' => ['񀀁', ['񀀁', '񆆑'], '񆇡']],
 *     'signbox' => [
 *         ['symbol' => '񆀁'],
 *         ['range' => ['񀀁', '񀇱'], 'coord' => [500, 500]]
 *     ],
 *     'variance' => 5,
 *     'style' => true
 * ]);
 * // 'QA񀀁R񀀁񆆑񆇡T񆀁R񀀁񀇱𝤆𝤆V5-'
 */
function swuquery_compose($swu_query_object)
{
    if (!is_array($swu_query_object) || empty($swu_query_object['query'])) {
        return null;
    }

    $query = 'Q';

    $prefix_obj = isset($swu_query_object['prefix']) ? $swu_query_object['prefix'] : null;
    if (is_array($prefix_obj) && !empty($prefix_obj['required'])) {
        $parts = isset($prefix_obj['parts']) ? $prefix_obj['parts'] : null;
        if (is_array($parts)) {
            $query .= 'A';
            foreach ($parts as $part) {
                if (is_string($part)) {
                    $query .= $part;
                } elseif (is_array($part) && count($part) === 2) {
                    $query .= 'R' . $part[0] . $part[1];
                } elseif (is_array($part) && count($part) > 2 && $part[0] === 'or_list') {
                    $or_parts = array_slice($part, 1);
                    $or_strs = [];
                    foreach ($or_parts as $or_part) {
                        if (is_string($or_part)) {
                            $or_strs[] = $or_part;
                        } elseif (is_array($or_part) && count($or_part) === 2) {
                            $or_strs[] = 'R' . $or_part[0] . $or_part[1];
                        }
                    }
                    $query .= implode('o', $or_strs);
                }
            }
        }
        $query .= 'T';
    }

    $signbox = isset($swu_query_object['signbox']) ? $swu_query_object['signbox'] : null;
    if (is_array($signbox)) {
        foreach ($signbox as $part_sb) {
            $out = '';
            if (is_array($part_sb)) {
                if (isset($part_sb['or_list'])) {
                    $or_list = $part_sb['or_list'];
                    $or_strs = [];
                    if (is_array($or_list)) {
                        foreach ($or_list as $item) {
                            if (is_string($item)) {
                                $or_strs[] = $item;
                            } elseif (is_array($item) && count($item) === 2) {
                                $or_strs[] = 'R' . $item[0] . $item[1];
                            }
                        }
                    }
                    $out = implode('o', $or_strs);
                } elseif (isset($part_sb['symbol'])) {
                    $out = $part_sb['symbol'];
                } elseif (isset($part_sb['range']) && is_array($part_sb['range']) && count($part_sb['range']) === 2) {
                    $out = 'R' . $part_sb['range'][0] . $part_sb['range'][1];
                }

                $coord = isset($part_sb['coord']) ? $part_sb['coord'] : null;
                if (is_array($coord) && count($coord) === 2) {
                    $out .= Convert\coord_to_swu($coord);
                }
            }

            $query .= $out;
        }
    }

    $variance = isset($swu_query_object['variance']) ? $swu_query_object['variance'] : null;
    if (is_int($variance)) {
        $query .= 'V' . $variance;
    }

    if (!empty($swu_query_object['style'])) {
        $query .= '-';
    }

    if (preg_match('/^(' . Regex\SWUQUERY_PATTERN_FULL . ')$/u', $query, $m)) {
        return $m[1];
    }
    return null;
}

// ----------------------------
// SWU to Query
// ----------------------------

/**
 * Function to convert an SWU sign to a query string.
 *
 * For the flags parameter, use one or more of the following:
 * - A: exact symbol in temporal prefix
 * - a: general symbol in temporal prefix
 * - S: exact symbol in spatial signbox
 * - s: general symbol in spatial signbox
 * - L: spatial signbox symbol at location
 *
 * @param string $swu_sign SWU sign
 * @param string $flags flags for query string creation
 * @return string|null SWU query string
 * @example
 * \Sgnw\Core\SwuQuery\swu_to_query('𝠀񀀒񀀚񋚥񋛩𝠃𝤟𝤩񋛩𝣵𝤐񀀒𝤇𝣤񋚥𝤐𝤆񀀚𝣮𝣭', 'ASL');
 * // 'QA񀀒񀀚񋚥񋛩T񋛩𝣵𝤐񀀒𝤇𝣤񋚥𝤐𝤆񀀚𝣮𝣭'
 */
function swu_to_query($swu_sign, $flags)
{
    $parsed = Swu\swu_parse_sign($swu_sign);
    if (!is_array($parsed) || empty($parsed['box'])) {
        return null;
    }

    $A_flag = strpos($flags, 'A') !== false;
    $a_flag = strpos($flags, 'a') !== false;
    $S_flag = strpos($flags, 'S') !== false;
    $s_flag = strpos($flags, 's') !== false;
    $L_flag = strpos($flags, 'L') !== false;

    $query = '';
    if ($A_flag || $a_flag || $S_flag || $s_flag) {
        if (($A_flag || $a_flag) && !empty($parsed['sequence'])) {
            $query .= 'A';
            foreach ($parsed['sequence'] as $sym) {
                $query .= $sym . ($a_flag ? 'fr' : '');
            }
            $query .= 'T';
        }

        if (($S_flag || $s_flag) && !empty($parsed['spatials'])) {
            foreach ($parsed['spatials'] as $spatial) {
                $sym = $spatial['symbol'];
                $query .= $sym . ($s_flag ? 'fr' : '');
                if ($L_flag) {
                    $query .= Convert\coord_to_swu($spatial['coord']);
                }
            }
        }
    }

    return $query !== '' ? 'Q' . $query : null;
}

// ------------------------------------------------------------------ #
// SWU Query Range
// ------------------------------------------------------------------ #

/**
 * Function to transform a range of SWU characters to a regular expression.
 *
 * @param string $min_char an SWU character
 * @param string $max_char an SWU character
 * @return string a regular expression that matches a range of SWU characters
 * @example
 * \Sgnw\Core\SwuQuery\swuquery_range('񀀁', '񀇡');
 * // '[\\x{40001}-\\x{401E1}]'
 */
function swuquery_range($min_char, $max_char)
{
    if (!is_string($min_char) || !is_string($max_char)) {
        return '';
    }
    if (mb_strlen($min_char, 'UTF-8') !== 1 || mb_strlen($max_char, 'UTF-8') !== 1) {
        return '';
    }
    $min_code = Unicode::codePointAt($min_char, 0);
    $max_code = Unicode::codePointAt($max_char, 0);
    if ($min_code === null || $max_code === null) {
        return '';
    }
    if ($min_code > $max_code) {
        return '';
    }
    $min_hex = strtoupper(sprintf('%05X', $min_code));
    $max_hex = strtoupper(sprintf('%05X', $max_code));
    return '[\\x{' . $min_hex . '}-\\x{' . $max_hex . '}]';
}

// ------------------------------------------------------------------ #
// SWU Query Regex
// ------------------------------------------------------------------ #

function _regex_range($sym_range)
{
    $from_char = Unicode::substrCp($sym_range, 1, 1);
    $to_char = Unicode::substrCp($sym_range, 2, 1);
    $from_key = Convert\swu_to_key($from_char);
    $to_key = Convert\swu_to_key($to_char);
    $from_char = Convert\key_to_swu(substr($from_key, 0, 4) . '00');
    $to_char = Convert\key_to_swu(substr($to_key, 0, 4) . '5f');
    return swuquery_range($from_char, $to_char);
}

function _regex_symbol($sym)
{
    $base_char = Unicode::substrCp($sym, 0, 1);
    $suffix = mb_substr($sym, 1, null, 'UTF-8');
    $base_code = Unicode::codePointAt($base_char, 0);
    if ($base_code === null) {
        return '';
    }

    $base_block = intdiv($base_code - 0x40001, 0x60);
    $base_start = 0x40001 + ($base_block * 0x60);
    $offset = $base_code - $base_start;
    $fill_index = intdiv($offset, 0x10);
    $rot_index = $offset % 0x10;

    $rotation_block = 0x10;
    $total_block = 0x60;

    if ($suffix === '') {
        return '\\x{' . strtoupper(sprintf('%05X', $base_code)) . '}';
    }
    if ($suffix === 'r') {
        $start = $base_start + $fill_index * $rotation_block;
        $end = $start + 0x0F;
        return '[\\x{' . strtoupper(sprintf('%05X', $start)) . '}-\\x{' . strtoupper(sprintf('%05X', $end)) . '}]';
    }
    if ($suffix === 'f') {
        $parts = [];
        for ($i = 0; $i < 6; $i++) {
            $parts[] = '\\x{' . strtoupper(sprintf('%05X', $base_start + $rot_index + $i * $rotation_block)) . '}';
        }
        return '(?:' . implode('|', $parts) . ')';
    }
    if ($suffix === 'fr' || $suffix === 'rf') {
        $start = $base_start;
        $end = $base_start + $total_block - 1;
        return '[\\x{' . strtoupper(sprintf('%05X', $start)) . '}-\\x{' . strtoupper(sprintf('%05X', $end)) . '}]';
    }

    throw new \InvalidArgumentException('Invalid suffix for SWU symbol: ' . $sym);
}

/**
 * Function to transform an SWU query string to one or more regular expressions.
 *
 * @param string $query an SWU query string
 * @return array a list of one or more regular expression strings
 * @example
 * \Sgnw\Core\SwuQuery\swuquery_regex('QA񀀒T');
 */
function swuquery_regex($query)
{
    $pattern = '/^' . Regex\SWUQUERY_PATTERN_FULL . '$/u';
    if (!preg_match($pattern, $query, $m)) {
        return [];
    }
    $query = $m[0];

    $q_style = '(?:' . Regex\STYLE_PATTERN_FULL . ')?';

    if ($query === 'Q') {
        return [Regex\SWU_PATTERN_PREFIX . '?' . Regex\SWU_PATTERN_BOX . Regex\SWU_PATTERN_COORD . '(?:' . Regex\SWU_PATTERN_SYMBOL . Regex\SWU_PATTERN_COORD . ')*'];
    }
    if ($query === 'Q-') {
        return [Regex\SWU_PATTERN_PREFIX . '?' . Regex\SWU_PATTERN_BOX . Regex\SWU_PATTERN_COORD . '(?:' . Regex\SWU_PATTERN_SYMBOL . Regex\SWU_PATTERN_COORD . ')*' . $q_style];
    }
    if ($query === 'QT') {
        return [Regex\SWU_PATTERN_PREFIX . Regex\SWU_PATTERN_BOX . Regex\SWU_PATTERN_COORD . '(?:' . Regex\SWU_PATTERN_SYMBOL . Regex\SWU_PATTERN_COORD . ')*'];
    }
    if ($query === 'QT-') {
        return [Regex\SWU_PATTERN_PREFIX . Regex\SWU_PATTERN_BOX . Regex\SWU_PATTERN_COORD . '(?:' . Regex\SWU_PATTERN_SYMBOL . Regex\SWU_PATTERN_COORD . ')*' . $q_style];
    }

    $segments = [];
    $sortable = 0;
    $pos = strpos($query, 'T');
    if ($pos !== false) {
        $sortable = $pos + 1;
    }
    $q_sortable = '';
    if ($sortable) {
        $q_sortable = '(?:' . Regex\SWU_PATTERN_SORT;
        $qat = substr($query, 0, $sortable);
        $query = substr($query, $sortable);
        if ($qat === 'QT') {
            $q_sortable .= Regex\SWU_PATTERN_NULL_OR_SYMBOL . '+)';
        } else {
            if (preg_match_all('/' . Regex\SWUQUERY_PATTERN_LIST . '/u', $qat, $matches)) {
                foreach ($matches[0] as $part) {
                    $or_list = [];
                    if (preg_match_all('/' . Regex\SWUQUERY_PATTERN_ITEM . '/u', $part, $m2)) {
                        foreach ($m2[0] as $mor) {
                            if (preg_match('/^' . Regex\SWUQUERY_PATTERN_RANGE . '$/u', $mor)) {
                                $or_list[] = _regex_range($mor);
                            } else {
                                $or_list[] = _regex_symbol($mor);
                            }
                        }
                    }
                    if (count($or_list) === 1) {
                        $q_sortable .= $or_list[0];
                    } else {
                        $q_sortable .= '(?:' . implode('|', $or_list) . ')';
                    }
                }
                $q_sortable .= Regex\SWU_PATTERN_NULL_OR_SYMBOL . '*)';
            }
        }
    }

    $fuzz = 20;
    if (preg_match('/' . Regex\SWUQUERY_PATTERN_VAR . '/u', $query, $mvar)) {
        $fuzz = intval(substr($mvar[0], 1));
    }

    $item_pattern = '/(?:' . Regex\SWUQUERY_PATTERN_SYMBOL . '|' . Regex\SWUQUERY_PATTERN_RANGE . ')/u';
    if (preg_match_all('/' . Regex\SWUQUERY_PATTERN_LIST . Regex\SWUQUERY_PATTERN_COORD . '/u', $query, $matches)) {
        foreach ($matches[0] as $item) {
            $or_list = [];
            if (preg_match_all($item_pattern, $item, $m2)) {
                foreach ($m2[0] as $mor) {
                    if (preg_match('/^' . Regex\SWUQUERY_PATTERN_RANGE . '$/u', $mor)) {
                        $or_list[] = _regex_range($mor);
                    } else {
                        $or_list[] = _regex_symbol($mor);
                    }
                }
            }
            if (count($or_list) === 1) {
                $segment = $or_list[0];
            } else {
                $segment = '(?:' . implode('|', $or_list) . ')';
            }

            if (preg_match('/' . Regex\SWUQUERY_PATTERN_COORD . '$/u', $item, $cm) && $cm[0] !== '') {
                $coord = Convert\swu_to_coord($cm[0]);
                $x = $coord[0];
                $y = $coord[1];
                $segment .= swuquery_range(Convert\num_to_swu($x - $fuzz), Convert\num_to_swu($x + $fuzz));
                $segment .= swuquery_range(Convert\num_to_swu($y - $fuzz), Convert\num_to_swu($y + $fuzz));
            } else {
                $segment .= Regex\SWU_PATTERN_COORD;
            }

            $segment = Regex\SWU_PATTERN_SIGNBOX . $segment . '(?:' . Regex\SWU_PATTERN_SYMBOL . Regex\SWU_PATTERN_COORD . ')*';
            if ($sortable) {
                $segment = $q_sortable . $segment;
            } else {
                $segment = Regex\SWU_PATTERN_PREFIX . '?' . $segment;
            }
            if (strpos($query, '-') !== false) {
                $segment .= $q_style;
            }
            $segments[] = $segment;
        }
    }

    if (empty($segments)) {
        $segment = $q_sortable . Regex\SWU_PATTERN_SIGNBOX;
        if (strpos($query, '-') !== false) {
            $segment .= $q_style;
        }
        $segments[] = $segment;
    }

    return $segments;
}

// ----------------------------
// SWU Query Results
// ----------------------------

/**
 * Function that uses a query string to match signs from a string of text.
 *
 * @param string $query an SWU query string
 * @param string $text a string of text containing multiple signs
 * @return array an array of SWU signs
 * @example
 * \Sgnw\Core\SwuQuery\swuquery_results('QA񀀒T','𝠀񀀒񀀚񋚥񋛩𝠃𝤟𝤩񋛩𝣵𝤐񀀒𝤇𝣤񋚥𝤐𝤆񀀚𝣮𝣭 𝠀񂇢񂇈񆙡񋎥񋎵𝠃𝤛𝤬񂇈𝤀𝣺񂇢𝤄𝣻񋎥𝤄𝤗񋎵𝤃𝣟񆙡𝣱𝣸 𝠀񅨑񀀙񆉁𝠃𝤙𝤞񀀙𝣷𝤀񅨑𝣼𝤀񆉁𝣳𝣮');
 * // ['𝠀񀀒񀀚񋚥񋛩𝠃𝤟𝤩񋛩𝣵𝤐񀀒𝤇𝣤񋚥𝤐𝤆񀀚𝣮𝣭']
 */
function swuquery_results($query, $text)
{
    if ($text === null || $text === '') {
        return [];
    }

    $re_patterns = swuquery_regex($query);
    if (empty($re_patterns)) {
        return [];
    }

    foreach ($re_patterns as $pattern) {
        if (preg_match_all('~' . $pattern . '~u', $text, $matches)) {
            if (!empty($matches[0])) {
                $text = implode(' ', $matches[0]);
            } else {
                $text = '';
                break;
            }
        } else {
            $text = '';
            break;
        }
    }

    if ($text !== '') {
        $words = explode(' ', $text);
        $unique = [];
        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }
            if (!isset($unique[$word])) {
                $unique[$word] = true;
            }
        }
        return array_keys($unique);
    }
    return [];
}

/**
 * Function that uses an SWU query string to match signs from multiple lines of text.
 *
 * @param string $query an SWU query string
 * @param string $text multiple lines of text, each starting with an SWU sign
 * @return array an array of lines of text, each starting with an SWU sign
 * @example
 * \Sgnw\Core\SwuQuery\swuquery_lines('QA񀀒T',"𝠀񀀒񀀚񋚥񋛩𝠃𝤟𝤩񋛩𝣵𝤐񀀒𝤇𝣤񋚥𝤐𝤆񀀚𝣮𝣭 line one\n𝠀񂇢񂇈񆙡񋎥񋎵𝠃𝤛𝤬񂇈𝤀𝣺񂇢𝤄𝣻񋎥𝤄𝤗񋎵𝤃𝣟񆙡𝣱𝣸 line two\n𝠀񅨑񀀙񆉁𝠃𝤙𝤞񀀙𝣷𝤀񅨑𝣼𝤀񆉁𝣳𝣮 line three");
 * // ['𝠀񀀒񀀚񋚥񋛩𝠃𝤟𝤩񋛩𝣵𝤐񀀒𝤇𝣤񋚥𝤐𝤆񀀚𝣮𝣭 line one']
 */
function swuquery_lines($query, $text)
{
    if ($text === null || $text === '') {
        return [];
    }

    $re_patterns = swuquery_regex($query);
    if (empty($re_patterns)) {
        return [];
    }

    foreach ($re_patterns as $pattern) {
        $pattern_with_anchor = '^' . $pattern . '.*';
        if (preg_match_all('~' . $pattern_with_anchor . '~um', $text, $matches)) {
            if (!empty($matches[0])) {
                $text = implode("\n", $matches[0]);
            } else {
                $text = '';
                break;
            }
        } else {
            $text = '';
            break;
        }
    }

    if ($text !== '') {
        $lines = explode("\n", $text);
        $unique = [];
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            if (!isset($unique[$line])) {
                $unique[$line] = true;
            }
        }
        return array_keys($unique);
    }
    return [];
}
