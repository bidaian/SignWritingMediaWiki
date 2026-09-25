<?php
namespace Sgnw\Core\Style;

use Sgnw\Core\Convert;
use Sgnw\Core\Regex;

/**
 * The style module contains regular expressions and functions for parsing and composing style strings.
 *
 * Style string definition:
 * https://datatracker.ietf.org/doc/id/draft-slevinski-formal-signwriting-10.html#name-styling-string
 */

// ----------------------------
// Style Parsing
// ----------------------------

function _prefix_color($color)
{
    if (!is_string($color)) {
        return $color;
    }
    if (preg_match('~^' . Regex\STYLE_PATTERN_COLORBASE . '$~', $color)) {
        $hex = strlen($color) === 3 || strlen($color) === 6;
        if ($hex && preg_match('/^[0-9a-fA-F]+$/', $color)) {
            return '#' . $color;
        }
    }
    return $color;
}

/**
 * Parse a style string to a structured dictionary.
 *
 * @param string $style_string
 * @return array
 * @example
 * \Sgnw\Core\Style\style_parse('-CP10G_blue_D_red,Cyan_');
 */
function style_parse($style_string)
{
    $pattern = '~^' . Regex\STYLE_PATTERN_FULL_GROUPS . '~';
    $m = is_string($style_string) ? preg_match($pattern, $style_string, $matches) : 0;
    if (!$m) {
        return [];
    }
    $res = [];

    $colorize = !empty($matches[1]) ? true : null;
    if ($colorize !== null) {
        $res['colorize'] = $colorize;
    }

    $padding = !empty($matches[2]) ? intval(substr($matches[2], 1)) : null;
    if ($padding !== null) {
        $res['padding'] = $padding;
    }

    $background = empty($matches[3]) ? null : _prefix_color(substr($matches[3], 2, -1));
    if ($background !== null) {
        $res['background'] = $background;
    }

    $detail = null;
    if (!empty($matches[4])) {
        $parts = explode(',', substr($matches[4], 2, -1));
        $detail = array_map(function ($p) { return _prefix_color(trim($p)); }, $parts);
    }
    if ($detail !== null) {
        $res['detail'] = $detail;
    }

    $zoom = null;
    if (!empty($matches[5])) {
        if ($matches[5] === 'Zx') {
            $zoom = 'x';
        } else {
            $num = substr($matches[5], 1);
            $zoom = (strpos($num, '.') !== false) ? floatval($num) : intval($num);
        }
    }
    if ($zoom !== null) {
        $res['zoom'] = $zoom;
    }

    $detailsym = [];
    if (!empty($matches[6])) {
        if (preg_match_all('~' . Regex\STYLE_PATTERN_DETAILSYM . '~', $matches[6], $dm)) {
            foreach ($dm[0] as $ds) {
                $index = intval(substr($ds, 1, 2));
                $inner = substr($ds, 4, -1);
                $det = [];
                foreach (explode(',', $inner) as $p) {
                    if ($p !== '') {
                        $det[] = _prefix_color(trim($p));
                    }
                }
                $detailsym[] = ['index' => $index, 'detail' => $det];
            }
        }
    }
    if ($detailsym) {
        $res['detailsym'] = $detailsym;
    }

    $classes = !empty($matches[7]) ? $matches[7] : null;
    if ($classes !== null) {
        $res['classes'] = $classes;
    }

    $id = !empty($matches[8]) ? $matches[8] : null;
    if ($id !== null) {
        $res['id'] = $id;
    }

    return $res;
}

// ----------------------------
// Style Compose
// ----------------------------

/**
 * Function to compose style string from dictionary.
 *
 * @param array $style_dict
 * @return string|null
 * @example
 * \Sgnw\Core\Style\style_compose(['colorize' => true, 'padding' => 10]);
 */
function style_compose($style_dict)
{
    if (!is_array($style_dict)) {
        return null;
    }
    $style1 = '';
    if (!empty($style_dict['colorize'])) {
        $style1 .= 'C';
    }
    if (isset($style_dict['padding'])) {
        $style1 .= 'P' . str_pad(strval(intval($style_dict['padding'])), 2, '0', STR_PAD_LEFT);
    }
    if (!empty($style_dict['background'])) {
        $style1 .= 'G_' . ltrim($style_dict['background'], '#') . '_';
    }
    if (!empty($style_dict['detail']) && is_array($style_dict['detail'])) {
        $style1 .= 'D_' . ltrim(implode(',', $style_dict['detail']), '#') . '_';
    }
    if (array_key_exists('zoom', $style_dict) && $style_dict['zoom'] !== null) {
        $style1 .= 'Z' . $style_dict['zoom'];
    }

    $style2 = '';
    if (!empty($style_dict['detailsym']) && is_array($style_dict['detailsym'])) {
        $parts = [];
        foreach ($style_dict['detailsym'] as $ds) {
            if (!is_array($ds)) {
                continue;
            }
            $index = isset($ds['index']) ? $ds['index'] : null;
            $det = isset($ds['detail']) ? $ds['detail'] : null;
            if ($index !== null && $det && is_array($det)) {
                $parts[] = 'D' . str_pad(strval(intval($index)), 2, '0', STR_PAD_LEFT) . '_' . ltrim(implode(',', $det), '#') . '_';
            }
        }
        $style2 .= implode('', $parts);
    }

    $style3 = '';
    $classes = isset($style_dict['classes']) ? $style_dict['classes'] : null;
    if (is_string($classes)) {
        $style3 .= $classes;
    }
    $id = isset($style_dict['id']) ? $style_dict['id'] : null;
    if ($classes || $id) {
        $style3 .= '!';
    }
    if (is_string($id)) {
        $style3 .= $id . '!';
    }

    $result = '-' . $style1;
    if ($style2 !== '' || $style3 !== '') {
        $result .= '-' . $style2;
    }
    if ($style3 !== '') {
        $result .= '-' . $style3;
    }

    if (!preg_match('~^(' . Regex\STYLE_PATTERN_FULL . ')$~', $result, $m)) {
        return null;
    }
    return $m[1];
}

// ----------------------------
// Style RGB
// ----------------------------

function _rgb_to_arr($rgb)
{
    if (!is_string($rgb)) {
        return [0.0, 0.0, 0.0];
    }
    if (!preg_match('/rgba?\\((.+?)\\)/i', strtolower($rgb), $m)) {
        return [0.0, 0.0, 0.0];
    }
    $values = explode(',', $m[1]);
    $out = [];
    foreach ($values as $v) {
        $out[] = floatval(trim($v));
    }
    return $out;
}

function _arr_to_hex($arr)
{
    $hex = '';
    for ($i = 0; $i < 3; $i++) {
        $hex .= str_pad(dechex(intval($arr[$i])), 2, '0', STR_PAD_LEFT);
    }
    return $hex;
}

/**
 * Function to convert rgb color to hex or "transparent" if below tolerance.
 *
 * @param string $rgb
 * @param float $tolerance
 * @return string
 * @example
 * \Sgnw\Core\Style\style_rgb_to_hex('rgb(255,255,255)');
 */
function style_rgb_to_hex($rgb, $tolerance = 0.0)
{
    $arr = _rgb_to_arr($rgb);
    if (count($arr) === 4 && $arr[3] <= $tolerance) {
        return 'transparent';
    }
    return _arr_to_hex($arr);
}

/**
 * Function to merge color with background based on alpha transparency.
 *
 * @param string $color
 * @param string $background
 * @return string
 * @example
 * \Sgnw\Core\Style\style_rgba_to_hex('rgba(255,255,255,0.5)', 'rgb(0,0,0)');
 */
function style_rgba_to_hex($color, $background)
{
    $b_arr = _rgb_to_arr($background);
    $c_arr = _rgb_to_arr($color);
    $alpha = count($c_arr) === 4 ? $c_arr[3] : 1.0;
    if ($alpha == 0) {
        return 'transparent';
    }
    $merged = [];
    for ($i = 0; $i < 3; $i++) {
        $merged[] = (1 - $alpha) * $b_arr[$i] + $alpha * $c_arr[$i];
    }
    return _arr_to_hex($merged);
}

// ----------------------------
// Style Merge
// ----------------------------

/**
 * Function to merge style dictionaries.
 *
 * @param mixed $style1
 * @param mixed $style2
 * @return array
 * @example
 * \Sgnw\Core\Style\style_merge(['colorize' => true], ['zoom' => 2]);
 */
function style_merge($style1, $style2)
{
    if (!is_array($style1)) {
        $style1 = [];
    }
    if (!is_array($style2)) {
        $style2 = [];
    }
    $zoom1 = Convert\to_zoom(isset($style1['zoom']) ? $style1['zoom'] : null);
    $zoom2 = Convert\to_zoom(isset($style2['zoom']) ? $style2['zoom'] : null);
    $merged = array_merge($style1, $style2);
    $zoomVal = $zoom1 * $zoom2;
    $merged['zoom'] = (abs($zoomVal - intval($zoomVal)) < 1e-9) ? intval($zoomVal) : $zoomVal;
    return $merged;
}
