<?php
namespace Sgnw\Core\Swu;

use Sgnw\Core\Convert;
use Sgnw\Core\Regex;
use Sgnw\Core\Unicode;

/**
 * The swu module contains functions for handling SignWriting in Unicode (SWU) characters.
 *
 * SWU characters definition:
 * https://datatracker.ietf.org/doc/id/draft-slevinski-formal-signwriting-10.html#name-signwriting-in-unicode-swu
 */

// ----------------------------
// SWU structure constants (plane 4 code points)
// ----------------------------

function swu_structure_kind()
{
    return [0x40001, 0x4EFA1, 0x4F2A1];
}

function swu_structure_category()
{
    return [0x40001, 0x461E1, 0x4BCA1, 0x4BFA1, 0x4E8E1, 0x4EFA1, 0x4F2A1];
}

function swu_structure_group()
{
    return [
        0x40001,
        0x40541,
        0x40B41,
        0x41981,
        0x41C81,
        0x43241,
        0x43D81,
        0x445C1,
        0x44CE1,
        0x45BE1,
        0x461E1,
        0x46841,
        0x46FC1,
        0x47FE1,
        0x485E1,
        0x49301,
        0x49E41,
        0x4A4A1,
        0x4AFE1,
        0x4B521,
        0x4BCA1,
        0x4BFA1,
        0x4C3C1,
        0x4CFC1,
        0x4D621,
        0x4E161,
        0x4E8E1,
        0x4EC41,
        0x4EFA1,
        0x4F2A1,
    ];
}

function swu_structure_ranges()
{
    return [
        'all' => [0x40001, 0x4F480],
        'writing' => [0x40001, 0x4EFA0],
        'hand' => [0x40001, 0x461E0],
        'movement' => [0x461E1, 0x4BCA0],
        'dynamic' => [0x4BCA1, 0x4BFA0],
        'head' => [0x4BFA1, 0x4E8E0],
        'hcenter' => [0x4BFA1, 0x4E8E0],
        'vcenter' => [0x4BFA1, 0x4EC40],
        'trunk' => [0x4E8E1, 0x4EC40],
        'limb' => [0x4EC41, 0x4EFA0],
        'location' => [0x4EFA1, 0x4F2A0],
        'punctuation' => [0x4F2A1, 0x4F480],
    ];
}

/**
 * Test whether an SWU character is of the given type/range.
 *
 * @param string $swu_char
 * @param string $type_name
 * @return bool
 * @example
 * \Sgnw\Core\Swu\swu_is_type('񀀁', 'hand'); // true
 */
function swu_is_type($swu_char, $type_name)
{
    if (!is_string($swu_char) || $swu_char === '') {
        return false;
    }
    $code = Unicode::codePointAt($swu_char, 0);
    if ($code === null) {
        return false;
    }
    $ranges = swu_structure_ranges();
    if (!isset($ranges[$type_name])) {
        return false;
    }
    $rng = $ranges[$type_name];
    return $rng[0] <= $code && $code <= $rng[1];
}

// ----------------------------
// SWU Colorize
// ----------------------------

function swu_colors()
{
    return [
        '#0000CC',
        '#CC0000',
        '#FF0099',
        '#006600',
        '#000000',
        '#884411',
        '#FF9900',
    ];
}

/**
 * Function that returns the standardized color for a symbol.
 *
 * @param string $sym
 * @return string
 * @example
 * \Sgnw\Core\Swu\swu_colorize('񀀁'); // '#0000CC'
 */
function swu_colorize($sym)
{
    if (!is_string($sym)) {
        return '#000000';
    }
    $parsed = swu_parse_symbol($sym);
    $color = '#000000';
    if (isset($parsed['symbol'])) {
        $code = Convert\swu_to_code($parsed['symbol']);
        $category = swu_structure_category();
        $index = -1;
        foreach ($category as $i => $val) {
            if ($val > $code) {
                $index = $i;
                break;
            }
        }
        $colors = swu_colors();
        $color = $colors[$index < 0 ? 6 : $index - 1];
    }
    return $color;
}

// ----------------------------
// SWU Parsing
// ----------------------------

/**
 * Parse an SWU symbol with optional coordinate and style string.
 *
 * @param string $swu_sym
 * @return array
 * @example
 * \Sgnw\Core\Swu\swu_parse_symbol('񀀁𝤆𝤆-C');
 * // ['symbol' => '񀀁', 'coord' => [500, 500], 'style' => '-C']
 */
function swu_parse_symbol($swu_sym)
{
    $pattern = '~^(' . Regex\SWU_PATTERN_SYMBOL . ')(' . Regex\SWU_PATTERN_COORD . ')?(' . Regex\STYLE_PATTERN_FULL . ')?~u';
    if (!preg_match($pattern, $swu_sym, $m)) {
        return [];
    }
    return Convert\drop_none([
        'symbol' => $m[1],
        'coord' => isset($m[2]) && $m[2] !== '' ? Convert\swu_to_coord($m[2]) : null,
        'style' => isset($m[3]) ? $m[3] : null,
    ]);
}

/**
 * Parse an SWU sign with optional style string.
 *
 * @param string $swu_sign
 * @return array
 * @example
 * \Sgnw\Core\Swu\swu_parse_sign('𝠀񀀒񀀚񋚥񋛩𝠃𝤟𝤩񋛩𝣵𝤐񀀒𝤇𝣤񋚥𝤐𝤆񀀚𝣮𝣭-C');
 */
function swu_parse_sign($swu_sign)
{
    $pattern = '~^(' . Regex\SWU_PATTERN_PREFIX . ')?(' . Regex\SWU_PATTERN_SIGNBOX . ')(' . Regex\STYLE_PATTERN_FULL . ')?~u';
    if (!preg_match($pattern, $swu_sign, $m)) {
        return [];
    }
    $prefix = isset($m[1]) ? $m[1] : null;
    $signbox = $m[2];
    $sequence = null;
    if ($prefix) {
        $seq_str = Unicode::substrCp($prefix, 1);
        $sequence = $seq_str !== '' ? preg_split('//u', $seq_str, -1, PREG_SPLIT_NO_EMPTY) : null;
    }
    $box = Unicode::substrCp($signbox, 0, 1);
    $max_coord = Convert\swu_to_coord(Unicode::substrCp($signbox, 1, 2));
    $spatials_str = mb_strlen($signbox, 'UTF-8') > 3 ? Unicode::substrCp($signbox, 3) : '';
    $spatials = null;
    if ($spatials_str !== '') {
        if (preg_match_all('/(' . Regex\SWU_PATTERN_SYMBOL . ')(' . Regex\SWU_PATTERN_COORD . ')/u', $spatials_str, $sm)) {
            $spatials = [];
            foreach ($sm[1] as $i => $sym) {
                $coord_str = $sm[2][$i];
                $spatials[] = [
                    'symbol' => $sym,
                    'coord' => Convert\swu_to_coord($coord_str),
                ];
            }
        }
    }
    return Convert\drop_none([
        'sequence' => $sequence,
        'box' => $box,
        'max' => $max_coord,
        'spatials' => $spatials,
        'style' => isset($m[3]) ? $m[3] : null,
    ]);
}

/**
 * Parse an SWU text into signs and punctuations.
 *
 * @param string $swu_text
 * @return array
 * @example
 * \Sgnw\Core\Swu\swu_parse_text('𝠀񁲡񈩧𝠃𝤘𝤣񁲡𝣳𝣩񈩧𝤉𝣻 𝠀񃊢񃊫񋛕񆇡𝠃𝤘𝤧񃊫𝣻𝤕񃊢𝣴𝣼񆇡𝤎𝤂񋛕𝤆𝣦 񏌁𝣢𝤂');
 */
function swu_parse_text($swu_text)
{
    if (!is_string($swu_text)) {
        return [];
    }
    $pattern = '~(?:' . Regex\SWU_PATTERN_PREFIX . ')?(?:' . Regex\SWU_PATTERN_SIGNBOX . ')(?:' . Regex\STYLE_PATTERN_FULL . ')?|' . Regex\SWU_PATTERN_SPATIAL . '(?:' . Regex\STYLE_PATTERN_FULL . ')?~u';
    if (!preg_match_all($pattern, $swu_text, $m)) {
        return [];
    }
    return $m[0];
}

// ----------------------------
// SWU Compose
// ----------------------------

/**
 * Function to compose an swu symbol with optional coordinate and style string.
 *
 * @param array $swu_sym_object
 * @return string|null
 * @example
 * \Sgnw\Core\Swu\swu_compose_symbol(['symbol' => '񀀁', 'coord' => [500, 500], 'style' => '-C']);
 */
function swu_compose_symbol($swu_sym_object)
{
    if (!is_array($swu_sym_object)) {
        return null;
    }
    $symbol = isset($swu_sym_object['symbol']) ? $swu_sym_object['symbol'] : null;
    if (!is_string($symbol)) {
        return null;
    }
    if (!preg_match('~^(' . Regex\SWU_PATTERN_SYMBOL . ')$~u', $symbol, $m)) {
        return null;
    }
    $symbol = $m[1];

    $coord = isset($swu_sym_object['coord']) ? $swu_sym_object['coord'] : null;
    $coord_str = '';
    if (is_array($coord) && count($coord) === 2) {
        $coord_str = Convert\coord_to_swu($coord);
    }

    $style_candidate = '';
    if (isset($swu_sym_object['style']) && is_string($swu_sym_object['style'])) {
        if (preg_match('~^(' . Regex\STYLE_PATTERN_FULL . ')~', $swu_sym_object['style'], $sm)) {
            $style_candidate = $sm[1];
        }
    }

    return $symbol . $coord_str . $style_candidate;
}

/**
 * Function to compose an swu sign with style string.
 *
 * @param array $swu_sign_object
 * @return string|null
 * @example
 * \Sgnw\Core\Swu\swu_compose_sign([
 *   'sequence' => ['񀀒','񀀚','񋚥','񋛩'],
 *   'box' => '𝠃',
 *   'max' => [525, 535],
 *   'spatials' => [
 *     ['symbol' => '񋛩', 'coord' => [483, 510]],
 *     ['symbol' => '񀀒', 'coord' => [501, 466]],
 *     ['symbol' => '񋚥', 'coord' => [510, 500]],
 *     ['symbol' => '񀀚', 'coord' => [476, 475]]
 *   ],
 *   'style' => '-C'
 * ]);
 */
function swu_compose_sign($swu_sign_object)
{
    if (!is_array($swu_sign_object)) {
        return null;
    }
    $box = isset($swu_sign_object['box']) ? $swu_sign_object['box'] : '';
    if (!preg_match('~^(' . Regex\SWU_PATTERN_BOX . ')$~u', $box, $m)) {
        return null;
    }
    $box = $m[1];

    $max_coord = isset($swu_sign_object['max']) ? $swu_sign_object['max'] : null;
    $max_str = '';
    if (is_array($max_coord) && count($max_coord) === 2) {
        $max_str = Convert\coord_to_swu($max_coord);
    }
    if ($max_str === '') {
        return null;
    }

    $prefix = '';
    if (isset($swu_sign_object['sequence']) && is_array($swu_sign_object['sequence'])) {
        $parts = [];
        foreach ($swu_sign_object['sequence'] as $key) {
            if (preg_match('~^(' . Regex\SWU_PATTERN_NULL_OR_SYMBOL . ')$~u', $key, $km)) {
                $parts[] = $km[1];
            }
        }
        if ($parts) {
            $prefix = Unicode::fromCodePoint(0x1D800) . implode('', $parts);
        }
    }

    $signbox = '';
    if (isset($swu_sign_object['spatials']) && is_array($swu_sign_object['spatials'])) {
        $parts = [];
        foreach ($swu_sign_object['spatials'] as $spatial) {
            $sym = isset($spatial['symbol']) ? $spatial['symbol'] : null;
            if (!is_string($sym) || !preg_match('~^(' . Regex\SWU_PATTERN_SYMBOL . ')$~u', $sym, $sm)) {
                continue;
            }
            $sym = $sm[1];
            $coord = isset($spatial['coord']) ? $spatial['coord'] : null;
            $coord_str = '';
            if (is_array($coord) && count($coord) === 2) {
                $coord_str = Convert\coord_to_swu($coord);
            }
            if ($coord_str === '') {
                continue;
            }
            $parts[] = $sym . $coord_str;
        }
        $signbox = implode('', $parts);
    }

    $style_candidate = '';
    if (isset($swu_sign_object['style']) && is_string($swu_sign_object['style'])) {
        if (preg_match('~^(' . Regex\STYLE_PATTERN_FULL . ')$~', $swu_sign_object['style'], $sm)) {
            $style_candidate = $sm[1];
        }
    }

    return $prefix . $box . $max_str . $signbox . $style_candidate;
}

// ----------------------------
// SWU Info
// ----------------------------

/**
 * Function to gather sizing information about an swu sign or symbol.
 *
 * @param string $swu
 * @return array
 * @example
 * \Sgnw\Core\Swu\swu_info('𝠀񁲡񈩧𝠂𝤘𝤣񁲡𝣳𝣩񈩧𝤉𝣻-P10Z2');
 */
function swu_info($swu)
{
    $lanes = [
        Unicode::fromCodePoint(0x1D801) => 0,
        Unicode::fromCodePoint(0x1D802) => -1,
        Unicode::fromCodePoint(0x1D803) => 0,
        Unicode::fromCodePoint(0x1D804) => 1,
    ];

    $parsed_sign = swu_parse_sign($swu);
    $width = null;
    $height = null;
    $segment = 'none';
    $x1 = 490;
    $y1 = 490;
    $lane = 0;

    if (isset($parsed_sign['spatials'])) {
        $x_coords = array_map(function ($spatial) { return $spatial['coord'][0]; }, $parsed_sign['spatials']);
        $y_coords = array_map(function ($spatial) { return $spatial['coord'][1]; }, $parsed_sign['spatials']);
        $x1 = min($x_coords);
        $y1 = min($y_coords);
        $x2 = $parsed_sign['max'][0];
        $y2 = $parsed_sign['max'][1];
        $width = $x2 - $x1;
        $height = $y2 - $y1;
        $segment = 'sign';
        $lane = $lanes[$parsed_sign['box']];
        $style = \Sgnw\Core\Style\style_parse(isset($parsed_sign['style']) ? $parsed_sign['style'] : '');
    } else {
        $parsed_symbol = swu_parse_symbol($swu);
        $lane = $lanes[Unicode::fromCodePoint(0x1D803)];
        if (isset($parsed_symbol['coord'])) {
            $x1 = $parsed_symbol['coord'][0];
            $y1 = $parsed_symbol['coord'][1];
            $width = (500 - $x1) * 2;
            $height = (500 - $y1) * 2;
            $segment = 'symbol';
        }
        $style = \Sgnw\Core\Style\style_parse(isset($parsed_symbol['style']) ? $parsed_symbol['style'] : '');
    }

    $zoom = isset($style['zoom']) ? $style['zoom'] : 1;
    $padding = isset($style['padding']) ? $style['padding'] : 0;

    return [
        'minX' => $x1,
        'minY' => $y1,
        'width' => $width === null ? 20 : $width,
        'height' => $height === null ? 20 : $height,
        'segment' => $segment,
        'lane' => $lane,
        'padding' => $padding,
        'zoom' => $zoom,
    ];
}

// ----------------------------
// SWU Columns
// ----------------------------

function swu_column_defaults()
{
    return [
        'height' => 500,
        'width' => 150,
        'offset' => 50,
        'pad' => 20,
        'margin' => 5,
        'dynamic' => false,
        'punctuation' => [
            'spacing' => true,
            'pad' => 30,
            'pull' => true,
        ],
        'style' => [
            'detail' => ['black', 'white'],
            'zoom' => 1,
        ],
    ];
}

/**
 * Function to merge an object of column options with default values.
 *
 * @param array|null $options
 * @return array
 * @example
 * \Sgnw\Core\Swu\swu_column_defaults_merge(['height' => 500, 'width' => 150]);
 */
function swu_column_defaults_merge($options = null)
{
    if (!is_array($options)) {
        $options = [];
    }
    $defaults = swu_column_defaults();
    $merged = array_merge($defaults, $options);
    $merged['punctuation'] = array_merge($defaults['punctuation'], isset($options['punctuation']) ? $options['punctuation'] : []);
    $merged['style'] = array_merge($defaults['style'], isset($options['style']) ? $options['style'] : []);
    return Convert\drop_none($merged);
}

/**
 * Function to transform an SWU text to an array of columns.
 *
 * @param string $swu_text
 * @param array|null $options
 * @return array
 * @example
 * \Sgnw\Core\Swu\swu_columns('𝠀񁲡񈩧𝠃𝤘𝤣񁲡𝣳𝣩񈩧𝤉𝣻 𝠀񃊢񃊫񋛕񆇡𝠃𝤘𝤧񃊫𝣻𝤕񃊢𝣴𝣼񆇡𝤎𝤂񋛕𝤆𝣦 񏌁𝣢𝤂', ['height' => 500, 'width' => 150]);
 */
function swu_columns($swu_text, $options = null)
{
    if (!is_string($swu_text)) {
        return [];
    }
    $values = swu_column_defaults_merge($options);
    if (isset($values['style']['zoom']) && $values['style']['zoom'] === 'x') {
        $values['style']['zoom'] = 1.0;
    }

    $input_text = swu_parse_text($swu_text);
    if (!$input_text) {
        return [];
    }

    $cursor = 0.0;
    $cols = [];
    $col = [];
    $plus = 0;
    $center = intval($values['width'] / 2);
    $max_height = $values['height'] - $values['margin'];
    $pullable = true;
    $finalize = false;

    foreach ($input_text as $val) {
        $informed = swu_info($val);
        if ($informed['zoom'] === 'x') {
            $informed['zoom'] = 1.0;
        }

        $cursor += $plus;
        if ($values['punctuation']['spacing']) {
            $cursor += $values['pad'] * ($informed['segment'] === 'sign' ? 1 : 0);
        } else {
            $cursor += $values['pad'];
        }

        $finalize = ($cursor + $informed['height']) > $max_height;
        if ($informed['segment'] === 'symbol' && $values['punctuation']['pull'] && $pullable) {
            $finalize = false;
            $pullable = false;
        }
        if (!$col) {
            $finalize = false;
        }
        if ($finalize) {
            $cursor = $values['pad'];
            $cols[] = $col;
            $col = [];
            $pullable = true;
        }

        $item = $informed;
        $item['x'] = intval(
            $center
            + ($values['offset'] * $informed['lane'])
            - ((500 - $informed['minX']) * Convert\to_zoom($informed['zoom']) * Convert\to_zoom($values['style']['zoom']))
        );
        $item['y'] = intval($cursor);
        $item['text'] = $val;
        $col[] = $item;
        $cursor += $informed['height'] * Convert\to_zoom($informed['zoom']) * Convert\to_zoom($values['style']['zoom']);

        if ($values['punctuation']['spacing']) {
            $plus = ($informed['segment'] === 'sign') ? $values['pad'] : $values['punctuation']['pad'];
        } else {
            $plus = $values['pad'];
        }
    }

    if ($col) {
        $cols[] = $col;
    }

    if ($values['punctuation']['pull']) {
        foreach ($cols as &$c) {
            $last = $c[count($c) - 1];
            $diff = ($last['y'] + $last['height']) - ($values['height'] - $values['margin']);
            if ($diff > 0) {
                $adj = intval($diff / count($c)) + 1;
                foreach ($c as $i => &$item) {
                    $item['y'] -= $adj * $i + $adj;
                }
                unset($item);
            }
        }
        unset($c);
    }

    $widths = [];
    foreach ($cols as &$c) {
        $mins = [$center - $values['offset'] - $values['pad']];
        $maxs = [$center + $values['offset'] + $values['pad']];
        foreach ($c as $item) {
            $mins[] = $item['x'] - $values['pad'];
            $maxs[] = $item['x'] + $item['width'] + $values['pad'];
        }
        $min_val = min($mins);
        $max_val = max($maxs);

        $width = $values['width'];
        $adj = 0;
        if (!$values['dynamic']) {
            $adj = $center - intval(($min_val + $max_val) / 2);
        } else {
            $width = $max_val - $min_val;
            $adj = -$min_val;
        }

        foreach ($c as &$item) {
            $item['x'] += $adj;
        }
        unset($item);
        $widths[] = $width;
    }
    unset($c);

    return ['options' => $values, 'widths' => $widths, 'columns' => $cols];
}
