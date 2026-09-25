<?php
namespace Sgnw\Core\FswQuery;

use Sgnw\Core\Convert;
use Sgnw\Core\Regex;
use Sgnw\Core\Fsw;

/**
 * The fswquery module contains functions for handling the FSW query language.
 *
 * Query Language definition:
 * https://datatracker.ietf.org/doc/id/draft-slevinski-formal-signwriting-10.html#name-query-language
 */

// ----------------------------
// FSW Query Parsing
// ----------------------------

function _fswquery_parse_prefix($text)
{
    if ($text === 'T') {
        return ['required' => true];
    }
    $processed_parts = [];
    if (preg_match_all('/' . Regex\FSWQUERY_PATTERN_LIST . '/', $text, $matches)) {
        foreach ($matches[0] as $part) {
            if (strpos($part, 'o') !== false) {
                $processed = ['or_list'];
                if (preg_match_all('/' . Regex\FSWQUERY_PATTERN_ITEM . '/', $part, $m2)) {
                    foreach ($m2[0] as $or_part) {
                        if (strlen($or_part) > 0 && $or_part[0] === 'S') {
                            $processed[] = $or_part;
                        } else {
                            $processed[] = explode('t', substr($or_part, 1));
                        }
                    }
                }
                $processed_parts[] = $processed;
            } else {
                if (strlen($part) > 0 && $part[0] === 'S') {
                    $processed_parts[] = $part;
                } else {
                    $processed_parts[] = explode('t', substr($part, 1));
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
 * \Sgnw\Core\FswQuery\_fswquery_parse_signbox('S100uu500x500');
 * // [['symbol' => 'S100uu', 'coord' => [500, 500]]]
 */
function _fswquery_parse_signbox($text)
{
    $processed = [];
    if (!preg_match_all('/' . Regex\FSWQUERY_PATTERN_LIST . Regex\FSWQUERY_PATTERN_COORD . '/', $text, $matches)) {
        return $processed;
    }
    foreach ($matches[0] as $item) {
        $coord_str = (strpos(substr($item, -7), 'x') !== false) ? substr($item, -7) : '';
        $front = $coord_str ? substr($item, 0, -7) : $item;
        $coord = $coord_str ? Convert\fsw_to_coord($coord_str) : null;

        if (strpos($front, 'o') !== false) {
            $or_parts = explode('o', $front);
            $or_list = [];
            foreach ($or_parts as $or_part) {
                if (strlen($or_part) > 0 && $or_part[0] === 'S') {
                    $or_list[] = $or_part;
                } else {
                    $or_list[] = explode('t', substr($or_part, 1));
                }
            }
            $query_or = ['or_list' => $or_list];
            if ($coord !== null) {
                $query_or['coord'] = $coord;
            }
            $processed[] = $query_or;
        } elseif (strpos($front, 'S') !== false) {
            $query_symbol = ['symbol' => $front];
            if ($coord !== null) {
                $query_symbol['coord'] = $coord;
            }
            $processed[] = $query_symbol;
        } else {
            $query_range = ['range' => explode('t', substr($front, 1))];
            if ($coord !== null) {
                $query_range['coord'] = $coord;
            }
            $processed[] = $query_range;
        }
    }
    return $processed;
}

/**
 * Parse an FSW query string to a structured dictionary.
 *
 * @param string $fsw_query_string an FSW query string
 * @return array Dictionary representing the query structure
 * @example
 * \Sgnw\Core\FswQuery\fswquery_parse('QAS10000S10500oS20500oR2fft304TS100uuR205t206oS207uu510x510V5-');
 * // ['query' => true, 'prefix' => ['required' => true, 'parts' => ['S10000', ['or_list', 'S10500', 'S20500', ['2ff', '304']]]], 'signbox' => [['symbol' => 'S100uu'], ['or_list' => [['205', '206'], 'S207uu'], 'coord' => [510, 510]]], 'variance' => 5, 'style' => true]
 */
function fswquery_parse($fsw_query_string)
{
    $pattern = '/^' . Regex\FSWQUERY_PATTERN_FULL . '$/';
    if (!preg_match($pattern, $fsw_query_string, $m)) {
        return ['query' => false];
    }

    $result = ['query' => true];
    $prefix = isset($m[1]) && $m[1] !== '' ? _fswquery_parse_prefix($m[1]) : null;
    if ($prefix !== null) {
        $result['prefix'] = $prefix;
    }
    $signbox = isset($m[2]) && $m[2] !== '' ? _fswquery_parse_signbox($m[2]) : null;
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
// FSW Query Compose
// ----------------------------

/**
 * Function to compose FSW query string from object.
 *
 * @param array $fsw_query_object Dictionary of query elements
 * @return string|null FSW query string
 * @example
 * \Sgnw\Core\FswQuery\fswquery_compose([
 *     'query' => true,
 *     'prefix' => ['required' => true, 'parts' => ['S10000', ['100', '204'], 'S20500']],
 *     'signbox' => [
 *         ['symbol' => 'S20000'],
 *         ['range' => ['100', '105'], 'coord' => [500, 500]]
 *     ],
 *     'variance' => 5,
 *     'style' => true
 * ]);
 * // 'QAS10000R100t204S20500TS20000R100t105500x500V5-'
 */
function fswquery_compose($fsw_query_object)
{
    if (!is_array($fsw_query_object) || empty($fsw_query_object['query'])) {
        return null;
    }

    $query = 'Q';

    $prefix_obj = isset($fsw_query_object['prefix']) ? $fsw_query_object['prefix'] : null;
    if (is_array($prefix_obj) && !empty($prefix_obj['required'])) {
        $parts = isset($prefix_obj['parts']) ? $prefix_obj['parts'] : null;
        if (is_array($parts)) {
            $query .= 'A';
            foreach ($parts as $part) {
                if (is_string($part)) {
                    $query .= $part;
                } elseif (is_array($part) && count($part) === 2) {
                    $query .= 'R' . $part[0] . 't' . $part[1];
                } elseif (is_array($part) && count($part) > 2 && $part[0] === 'or_list') {
                    $or_parts = array_slice($part, 1);
                    $or_strs = [];
                    foreach ($or_parts as $or_part) {
                        if (is_string($or_part)) {
                            $or_strs[] = $or_part;
                        } elseif (is_array($or_part) && count($or_part) === 2) {
                            $or_strs[] = 'R' . $or_part[0] . 't' . $or_part[1];
                        }
                    }
                    $query .= implode('o', $or_strs);
                }
            }
        }
        $query .= 'T';
    }

    $signbox = isset($fsw_query_object['signbox']) ? $fsw_query_object['signbox'] : null;
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
                                $or_strs[] = 'R' . $item[0] . 't' . $item[1];
                            }
                        }
                    }
                    $out = implode('o', $or_strs);
                } elseif (isset($part_sb['symbol'])) {
                    $out = $part_sb['symbol'];
                } elseif (isset($part_sb['range']) && is_array($part_sb['range']) && count($part_sb['range']) === 2) {
                    $out = 'R' . $part_sb['range'][0] . 't' . $part_sb['range'][1];
                }

                $coord = isset($part_sb['coord']) ? $part_sb['coord'] : null;
                if (is_array($coord) && count($coord) === 2) {
                    $out .= Convert\coord_to_fsw($coord);
                }
            }

            $query .= $out;
        }
    }

    $variance = isset($fsw_query_object['variance']) ? $fsw_query_object['variance'] : null;
    if (is_int($variance)) {
        $query .= 'V' . $variance;
    }

    if (!empty($fsw_query_object['style'])) {
        $query .= '-';
    }

    if (preg_match('/^(' . Regex\FSWQUERY_PATTERN_FULL . ')$/', $query, $m)) {
        return $m[1];
    }
    return null;
}

// ----------------------------
// FSW to Query
// ----------------------------

/**
 * Function to convert an FSW sign to a query string.
 *
 * For the flags parameter, use one or more of the following:
 * - A: exact symbol in temporal prefix
 * - a: general symbol in temporal prefix
 * - S: exact symbol in spatial signbox
 * - s: general symbol in spatial signbox
 * - L: spatial signbox symbol at location
 *
 * @param string $fsw_sign FSW sign
 * @param string $flags flags for query string creation
 * @return string|null FSW query string
 * @example
 * \Sgnw\Core\FswQuery\fsw_to_query('AS10011S10019S2e704S2e748M525x535S2e748483x510S10011501x466S2e704510x500S10019476x475', 'ASL');
 * // 'QAS10011S10019S2e704S2e748TS2e748483x510S10011501x466S2e704510x500S10019476x475'
 */
function fsw_to_query($fsw_sign, $flags)
{
    $query = '';
    $parsed = Fsw\fsw_parse_sign($fsw_sign);

    if (!is_array($parsed) || empty($parsed['box'])) {
        return null;
    }

    $A_flag = strpos($flags, 'A') !== false;
    $a_flag = strpos($flags, 'a') !== false;
    $S_flag = strpos($flags, 'S') !== false;
    $s_flag = strpos($flags, 's') !== false;
    $L_flag = strpos($flags, 'L') !== false;

    if ($A_flag || $a_flag || $S_flag || $s_flag) {
        if (($A_flag || $a_flag) && !empty($parsed['sequence'])) {
            $query .= 'A';
            foreach ($parsed['sequence'] as $sym) {
                $query .= substr($sym, 0, 4) . ($a_flag ? 'uu' : substr($sym, 4, 2));
            }
            $query .= 'T';
        }

        if (($S_flag || $s_flag) && !empty($parsed['spatials'])) {
            foreach ($parsed['spatials'] as $spatial) {
                $sym = $spatial['symbol'];
                $query .= substr($sym, 0, 4) . ($s_flag ? 'uu' : substr($sym, 4, 2));
                if ($L_flag) {
                    $query .= implode('x', $spatial['coord']);
                }
            }
        }
    }

    return $query !== '' ? 'Q' . $query : null;
}

// ----------------------------
// FSW Query Range
// ----------------------------

function _range_pattern($low, $high, $is_hex)
{
    if ($low === $high) {
        return $low;
    }
    if (!$is_hex) {
        return '[' . $low . '-' . $high . ']';
    }
    $hex_digits = '0123456789abcdef';
    $low_index = strpos($hex_digits, $low);
    $high_index = strpos($hex_digits, $high);
    $range_str = substr($hex_digits, $low_index, $high_index - $low_index + 1);
    preg_match('/[0-9]+/', $range_str, $numeric_match);
    preg_match('/[a-f]+/', $range_str, $alpha_match);
    $numeric = isset($numeric_match[0]) ? $numeric_match[0] : '';
    $alpha = isset($alpha_match[0]) ? $alpha_match[0] : '';
    if ($numeric !== '' && $alpha !== '') {
        return '[' . $numeric[0] . '-' . $numeric[strlen($numeric) - 1] . $alpha[0] . '-' . $alpha[strlen($alpha) - 1] . ']';
    }
    if ($numeric !== '') {
        return '[' . $numeric[0] . '-' . $numeric[strlen($numeric) - 1] . ']';
    }
    if ($alpha !== '') {
        return '[' . $alpha[0] . '-' . $alpha[strlen($alpha) - 1] . ']';
    }
    return '';
}

function _regex_geq($p, $q, $is_hex)
{
    $digit_seq = $is_hex ? '0123456789abcdef' : '0123456789';
    $last_digit = $digit_seq[strlen($digit_seq) - 1];
    $p_index = strpos($digit_seq, $p);
    if ($q === $digit_seq[0]) {
        return _range_pattern($p, $last_digit, $is_hex) . ($is_hex ? '[0-9a-f]' : '[0-9]');
    }
    $q_range = _range_pattern($q, $last_digit, $is_hex);
    $next_p = ($p_index + 1 < strlen($digit_seq)) ? $digit_seq[$p_index + 1] : null;
    if ($next_p !== null) {
        $next_range = _range_pattern($next_p, $last_digit, $is_hex);
        return $p . $q_range . '|' . $next_range . ($is_hex ? '[0-9a-f]' : '[0-9]');
    }
    return $p . $q_range;
}

function _regex_leq($r, $s, $is_hex)
{
    $digit_seq = $is_hex ? '0123456789abcdef' : '0123456789';
    $first_digit = $digit_seq[0];
    $r_index = strpos($digit_seq, $r);
    if ($r_index > 0) {
        $prev_r = $digit_seq[$r_index - 1];
        $prev_part = _range_pattern($first_digit, $prev_r, $is_hex)
            . ($is_hex ? '[0-9a-f]' : '[0-9]')
            . '|';
        $s_range = _range_pattern($first_digit, $s, $is_hex);
        return $prev_part . $r . $s_range;
    }
    $s_range = _range_pattern($first_digit, $s, $is_hex);
    return $r . $s_range;
}

function _regex_between_two_digits($s, $t, $is_hex)
{
    $s1 = $s[0];
    $s2 = $s[1];
    $t1 = $t[0];
    $t2 = $t[1];
    $digit_seq = $is_hex ? '0123456789abcdef' : '0123456789';
    $s1_index = strpos($digit_seq, $s1);
    $t1_index = strpos($digit_seq, $t1);
    if ($s1_index < $t1_index) {
        $parts = [$s1 . _range_pattern($s2, $digit_seq[strlen($digit_seq) - 1], $is_hex)];
        $middle_digits = substr($digit_seq, $s1_index + 1, $t1_index - $s1_index - 1);
        if ($middle_digits !== '') {
            $parts[] = _range_pattern($middle_digits[0], $middle_digits[strlen($middle_digits) - 1], $is_hex)
                . ($is_hex ? '[0-9a-f]' : '[0-9]');
        }
        $parts[] = $t1 . _range_pattern($digit_seq[0], $t2, $is_hex);
        return implode('|', $parts);
    }
    return $s1 . _range_pattern($s2, $t2, $is_hex);
}

/**
 * Function to transform a range to a regular expression.
 *
 * @param int|string $min_ either a decimal number or hexidecimal string
 * @param int|string $max_ either a decimal number or hexidecimal string
 * @param bool $hex_ if True, the regular expression will match a hexidecimal range
 * @return string a regular expression that matches a range
 * @example
 * \Sgnw\Core\FswQuery\fswquery_range(500, 750);
 * // '(([56][0-9][0-9])|(7[0-4][0-9])|(750))'
 * @example
 * \Sgnw\Core\FswQuery\fswquery_range('100', '10e', true);
 * // '10[0-9a-e]'
 */
function fswquery_range($min_, $max_, $hex_ = false)
{
    $min_str = is_int($min_) ? sprintf('%03d', $min_) : str_pad((string)$min_, 3, '0', STR_PAD_LEFT);
    $max_str = is_int($max_) ? sprintf('%03d', $max_) : (string)$max_;

    $a = $min_str[0];
    $b = $min_str[1];
    $c = $min_str[2];
    $d = $max_str[0];
    $e = $max_str[1];
    $f = $max_str[2];

    $digit_seq = $hex_ ? '0123456789abcdef' : '0123456789';
    $a_index = strpos($digit_seq, $a);
    $d_index = strpos($digit_seq, $d);
    if ($a_index > $d_index) {
        throw new \InvalidArgumentException('Start is greater than end');
    }

    if ($a === $d) {
        $between_pattern = _regex_between_two_digits($b . $c, $e . $f, $hex_);
        return (strpos($between_pattern, '|') !== false) ? $a . '(?:' . $between_pattern . ')' : $a . $between_pattern;
    }

    $parts = [];
    $geq_pattern = _regex_geq($b, $c, $hex_);
    $parts[] = (strpos($geq_pattern, '|') !== false) ? $a . '(?:' . $geq_pattern . ')' : $a . $geq_pattern;

    if ($a_index + 1 < $d_index) {
        $middle_digits = substr($digit_seq, $a_index + 1, $d_index - $a_index - 1);
        $middle_pattern = _range_pattern($middle_digits[0], $middle_digits[strlen($middle_digits) - 1], $hex_);
        $digit_class = $hex_ ? '[0-9a-f]' : '[0-9]';
        $parts[] = $middle_pattern . $digit_class . $digit_class;
    }

    $leq_pattern = _regex_leq($e, $f, $hex_);
    $parts[] = (strpos($leq_pattern, '|') !== false) ? $d . '(?:' . $leq_pattern . ')' : $d . $leq_pattern;

    return '(?:' . implode('|', $parts) . ')';
}

// ----------------------------
// FSW Query Regex
// ----------------------------

function _regex_symbol($sym)
{
    $segment = substr($sym, 0, 4);
    $fill = substr($sym, 4, 1);
    if ($fill === 'u') {
        $segment .= '[0-5]';
    } else {
        $segment .= $fill;
    }
    $rot = substr($sym, 5, 1);
    if ($rot === 'u') {
        $segment .= '[0-9a-f]';
    } else {
        $segment .= $rot;
    }
    return $segment;
}

function _regex_range($sym_range)
{
    $from = substr($sym_range, 1, 3);
    $to = substr($sym_range, 5, 3);
    return 'S' . fswquery_range($from, $to, true) . '[0-5][0-9a-f]';
}

/**
 * Function to transform an FSW query string to one or more regular expressions.
 *
 * @param string $query an FSW query string
 * @return array a list of one or more regular expression strings
 * @example
 * \Sgnw\Core\FswQuery\fswquery_regex('QS100uuS20500480x520');
 * // ['(?:A(?:S[123][0-9a-f]{2}[0-5][0-9a-f])+)?[BLMR][0-9]{3}x[0-9]{3}(?:S[123][0-9a-f]{2}[0-5][0-9a-f][0-9]{3}x[0-9]{3})*S100[0-5][0-9a-f][0-9]{3}x[0-9]{3}(?:S[123][0-9a-f]{2}[0-5][0-9a-f][0-9]{3}x[0-9]{3})*', '(?:A(?:S[123][0-9a-f]{2}[0-5][0-9a-f])+)?[BLMR][0-9]{3}x[0-9]{3}(?:S[123][0-9a-f]{2}[0-5][0-9a-f][0-9]{3}x[0-9]{3})*S20500(?:4[6-9][0-9]|500)x(?:5[0-3][0-9]|540)(?:S[123][0-9a-f]{2}[0-5][0-9a-f][0-9]{3}x[0-9]{3})*']
 */
function fswquery_regex($query)
{
    $pattern = '/^' . Regex\FSWQUERY_PATTERN_FULL . '$/';
    if (!preg_match($pattern, $query, $m)) {
        return [];
    }
    $query = $m[0];

    $q_style = '(?:' . Regex\STYLE_PATTERN_FULL . ')?';

    if ($query === 'Q') {
        return [Regex\FSW_PATTERN_PREFIX . '?' . Regex\FSW_PATTERN_SIGNBOX];
    }
    if ($query === 'Q-') {
        return [Regex\FSW_PATTERN_PREFIX . '?' . Regex\FSW_PATTERN_SIGNBOX . $q_style];
    }
    if ($query === 'QT') {
        return [Regex\FSW_PATTERN_PREFIX . Regex\FSW_PATTERN_SIGNBOX];
    }
    if ($query === 'QT-') {
        return [Regex\FSW_PATTERN_PREFIX . Regex\FSW_PATTERN_SIGNBOX . $q_style];
    }

    $segments = [];
    $sortable = 0;
    $pos = strpos($query, 'T');
    if ($pos !== false) {
        $sortable = $pos + 1;
    }
    $q_sortable = '';
    if ($sortable) {
        $q_sortable = '(?:A';
        $qat = substr($query, 0, $sortable);
        $query = substr($query, $sortable);
        if ($qat === 'QT') {
            $q_sortable .= Regex\FSW_PATTERN_NULL_OR_SYMBOL . '+)';
        } else {
            if (preg_match_all('/' . Regex\FSWQUERY_PATTERN_LIST . '/', $qat, $matches)) {
                foreach ($matches[0] as $part) {
                    $or_list = [];
                    if (preg_match_all('/' . Regex\FSWQUERY_PATTERN_ITEM . '/', $part, $m2)) {
                        foreach ($m2[0] as $mor) {
                            if (preg_match('/^' . Regex\FSWQUERY_PATTERN_NULL_OR_SYMBOL . '$/', $mor)) {
	                                $or_list[] = _regex_symbol($mor);
                            } else {
	                                $or_list[] = _regex_range($mor);
                            }
                        }
                    }
                    if (count($or_list) === 1) {
                        $q_sortable .= $or_list[0];
                    } else {
                        $q_sortable .= '(?:' . implode('|', $or_list) . ')';
                    }
                }
                $q_sortable .= Regex\FSW_PATTERN_NULL_OR_SYMBOL . '*)';
            }
        }
    }

    $fuzz = 20;
    if (preg_match('/' . Regex\FSWQUERY_PATTERN_VAR . '/', $query, $mvar)) {
        $fuzz = intval(substr($mvar[0], 1));
    }

    $item_pattern = '/(?:' . Regex\FSWQUERY_PATTERN_SYMBOL . '|' . Regex\FSWQUERY_PATTERN_RANGE . ')/';
    if (preg_match_all('/' . Regex\FSWQUERY_PATTERN_LIST . Regex\FSWQUERY_PATTERN_COORD . '/', $query, $matches)) {
        foreach ($matches[0] as $item) {
            $or_list = [];
            if (preg_match_all($item_pattern, $item, $m2)) {
                foreach ($m2[0] as $mor) {
                    if (preg_match('/^' . Regex\FSWQUERY_PATTERN_SYMBOL . '$/', $mor)) {
                        $or_list[] = _regex_symbol($mor);
                    } else {
                        $or_list[] = _regex_range($mor);
                    }
                }
            }
            if (count($or_list) === 1) {
                $segment = $or_list[0];
            } else {
                $segment = '(?:' . implode('|', $or_list) . ')';
            }

            if (strpos(substr($item, -7), 'x') !== false) {
                $coord_str = substr($item, -7);
                $coord = Convert\fsw_to_coord($coord_str);
                $x = $coord[0];
                $y = $coord[1];
                $segment .= fswquery_range($x - $fuzz, $x + $fuzz);
                $segment .= 'x';
                $segment .= fswquery_range($y - $fuzz, $y + $fuzz);
            } else {
                $segment .= Regex\FSW_PATTERN_COORD;
            }

            $segment = Regex\FSW_PATTERN_SIGNBOX . $segment . '(?:' . Regex\FSW_PATTERN_SYMBOL . Regex\FSW_PATTERN_COORD . ')*';
            if ($sortable) {
                $segment = $q_sortable . $segment;
            } else {
                $segment = Regex\FSW_PATTERN_PREFIX . '?' . $segment;
            }
            if (strpos($query, '-') !== false) {
                $segment .= $q_style;
            }
            $segments[] = $segment;
        }
    }

    if (empty($segments)) {
        $segment = $q_sortable . Regex\FSW_PATTERN_SIGNBOX;
        if (strpos($query, '-') !== false) {
            $segment .= $q_style;
        }
        $segments[] = $segment;
    }

    return $segments;
}

// ----------------------------
// FSW Query Results
// ----------------------------

/**
 * Function that uses a query string to match signs from a string of text.
 *
 * @param string $query an FSW query string
 * @param string $text a string of text containing multiple signs
 * @return array an array of FSW signs
 * @example
 * \Sgnw\Core\FswQuery\fswquery_results('QAS10011T','AS10011S10019S2e704S2e748M525x535S2e748483x510S10011501x466S2e704510x500S10019476x475 AS15a21S15a07S21100S2df04S2df14M521x538S15a07494x488S15a21498x489S2df04498x517S2df14497x461S21100479x486 AS1f010S10018S20600M519x524S10018485x494S1f010490x494S20600481x476');
 * // ['AS10011S10019S2e704S2e748M525x535S2e748483x510S10011501x466S2e704510x500S10019476x475']
 */
function fswquery_results($query, $text)
{
    if ($text === null || $text === '') {
        return [];
    }

    $re_patterns = fswquery_regex($query);
    if (empty($re_patterns)) {
        return [];
    }

    foreach ($re_patterns as $pattern) {
        if (preg_match_all('~' . $pattern . '~', $text, $matches)) {
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
 * Function that uses an FSW query string to match signs from multiple lines of text.
 *
 * @param string $query an FSW query string
 * @param string $text multiple lines of text, each starting with an FSW sign
 * @return array an array of lines of text, each starting with an FSW sign
 * @example
 * \Sgnw\Core\FswQuery\fswquery_lines('QAS10011T',"AS10011S10019S2e704S2e748M525x535S2e748483x510S10011501x466S2e704510x500S10019476x475 line one\nAS15a21S15a07S21100S2df04S2df14M521x538S15a07494x488S15a21498x489S2df04498x517S2df14497x461S21100479x486 line two\nAS1f010S10018S20600M519x524S10018485x494S1f010490x494S20600481x476 line three");
 * // ['AS10011S10019S2e704S2e748M525x535S2e748483x510S10011501x466S2e704510x500S10019476x475 line one']
 */
function fswquery_lines($query, $text)
{
    if ($text === null || $text === '') {
        return [];
    }

    $re_patterns = fswquery_regex($query);
    if (empty($re_patterns)) {
        return [];
    }

    foreach ($re_patterns as $pattern) {
        $pattern_with_anchor = '^' . $pattern . '.*';
        if (preg_match_all('~' . $pattern_with_anchor . '~m', $text, $matches)) {
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
