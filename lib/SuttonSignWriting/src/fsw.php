<?php
namespace Sgnw\Core\Fsw;

use Sgnw\Core\Convert;
use Sgnw\Core\Regex;

/**
 * The fsw module contains functions for handling Formal SignWriting in ASCII (FSW) characters.
 *
 * FSW characters definition:
 * https://datatracker.ietf.org/doc/id/draft-slevinski-formal-signwriting-10.html#name-formal-signwriting-in-ascii
 */

// ----------------------------
// FSW structure constants
// ----------------------------

function fsw_structure_kind()
{
    return [0x100, 0x37F, 0x387];
}

function fsw_structure_category()
{
    return [0x100, 0x205, 0x2F7, 0x2FF, 0x36D, 0x37F, 0x387];
}

function fsw_structure_group()
{
    return [
        0x100,
        0x10E,
        0x11E,
        0x144,
        0x14C,
        0x186,
        0x1A4,
        0x1BA,
        0x1CD,
        0x1F5,
        0x205,
        0x216,
        0x22A,
        0x255,
        0x265,
        0x288,
        0x2A6,
        0x2B7,
        0x2D5,
        0x2E3,
        0x2F7,
        0x2FF,
        0x30A,
        0x32A,
        0x33B,
        0x359,
        0x36D,
        0x376,
        0x37F,
        0x387,
    ];
}

function fsw_structure_ranges()
{
    return [
        'all' => [0x100, 0x38B],
        'writing' => [0x100, 0x37E],
        'hand' => [0x100, 0x204],
        'movement' => [0x205, 0x2F6],
        'dynamic' => [0x2F7, 0x2FE],
        'head' => [0x2FF, 0x36C],
        'hcenter' => [0x2FF, 0x36C],
        'vcenter' => [0x2FF, 0x375],
        'trunk' => [0x36D, 0x375],
        'limb' => [0x376, 0x37E],
        'location' => [0x37F, 0x386],
        'punctuation' => [0x387, 0x38B],
    ];
}

/**
 * Test whether an FSW symbol key is of the given type/range.
 *
 * @param string $sym_key
 * @param string $type_name
 * @return bool
 * @example
 * \Sgnw\Core\Fsw\fsw_is_type('S10000', 'hand'); // true
 */
function fsw_is_type($sym_key, $type_name)
{
    if (!is_string($sym_key) || strpos($sym_key, 'S') !== 0 || strlen($sym_key) < 4) {
        return false;
    }
    $dec = null;
    try {
        $dec = intval(substr($sym_key, 1, 3), 16);
    } catch (\Throwable $e) {
        return false;
    }
    $ranges = fsw_structure_ranges();
    if (!isset($ranges[$type_name])) {
        return false;
    }
    $rng = $ranges[$type_name];
    return $rng[0] <= $dec && $dec <= $rng[1];
}

// ----------------------------
// FSW Colorize
// ----------------------------

function fsw_colors()
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
 * @param string $key
 * @return string
 * @example
 * \Sgnw\Core\Fsw\fsw_colorize('S10000'); // '#0000CC'
 */
function fsw_colorize($key)
{
    if (!is_string($key)) {
        return '#000000';
    }
    $parsed = fsw_parse_symbol($key);
    $color = '#000000';
    if (isset($parsed['symbol'])) {
        $dec = intval(substr($parsed['symbol'], 1, 3), 16);
        $category = fsw_structure_category();
        $index = -1;
        foreach ($category as $i => $val) {
            if ($val > $dec) {
                $index = $i;
                break;
            }
        }
        $colors = fsw_colors();
        $color = $colors[$index < 0 ? 6 : $index - 1];
    }
    return $color;
}

// ----------------------------
// FSW Parsing
// ----------------------------

/**
 * Parse an FSW symbol with optional coordinate and style string.
 *
 * @param string $fsw_sym
 * @return array
 * @example
 * \Sgnw\Core\Fsw\fsw_parse_symbol('S10000500x500-C');
 * // ['symbol' => 'S10000', 'coord' => [500, 500], 'style' => '-C']
 */
function fsw_parse_symbol($fsw_sym)
{
    $pattern = '~^(' . Regex\FSW_PATTERN_SYMBOL . ')(' . Regex\FSW_PATTERN_COORD . ')?(' . Regex\STYLE_PATTERN_FULL . ')?~';
    if (!preg_match($pattern, $fsw_sym, $m)) {
        return [];
    }
    return Convert\drop_none([
        'symbol' => $m[1],
        'coord' => isset($m[2]) && $m[2] !== '' ? Convert\fsw_to_coord($m[2]) : null,
        'style' => isset($m[3]) ? $m[3] : null,
    ]);
}

/**
 * Parse an FSW sign with optional style string.
 *
 * @param string $fsw_sign
 * @return array
 * @example
 * \Sgnw\Core\Fsw\fsw_parse_sign('AS10011S10019S2e704S2e748M525x535S2e748483x510S10011501x466S2e704510x500S10019476x475-C');
 */
function fsw_parse_sign($fsw_sign)
{
    $pattern = '~^(' . Regex\FSW_PATTERN_PREFIX . ')?(' . Regex\FSW_PATTERN_SIGNBOX . ')(' . Regex\STYLE_PATTERN_FULL . ')?~';
    if (!preg_match($pattern, $fsw_sign, $m)) {
        return [];
    }
    $prefix = isset($m[1]) ? $m[1] : null;
    $signbox = $m[2];
    $sequence = null;
    if ($prefix) {
        $sequence = str_split(substr($prefix, 1), 6);
    }
    $box = $signbox[0];
    $max_coord = Convert\fsw_to_coord(substr($signbox, 1, 7));
    $spatials_str = strlen($signbox) > 8 ? substr($signbox, 8) : '';
    $spatials = null;
    if ($spatials_str !== '') {
        $spatials = [];
        if (preg_match_all('/.{13}/', $spatials_str, $sm)) {
            foreach ($sm[0] as $s) {
                $spatials[] = [
                    'symbol' => substr($s, 0, 6),
                    'coord' => Convert\fsw_to_coord(substr($s, 6)),
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
 * Parse an FSW text into signs and punctuations.
 *
 * @param string $fsw_text
 * @return array
 * @example
 * \Sgnw\Core\Fsw\fsw_parse_text('AS14c20S27106M518x529S14c20481x471S27106503x489 AS18701S1870aS2e734S20500M518x533S1870a489x515S18701482x490S20500508x496S2e734500x468 S38800464x496');
 */
function fsw_parse_text($fsw_text)
{
    if (!is_string($fsw_text)) {
        return [];
    }
    $pattern = '~(?:' . Regex\FSW_PATTERN_PREFIX . ')?(?:' . Regex\FSW_PATTERN_SIGNBOX . ')(?:' . Regex\STYLE_PATTERN_FULL . ')?|' . Regex\FSW_PATTERN_SPATIAL . '(?:' . Regex\STYLE_PATTERN_FULL . ')?~';
    if (!preg_match_all($pattern, $fsw_text, $m)) {
        return [];
    }
    return $m[0];
}

// ----------------------------
// FSW Compose
// ----------------------------

/**
 * Function to compose an fsw symbol with optional coordinate and style string.
 *
 * @param array $fsw_sym_object
 * @return string|null
 * @example
 * \Sgnw\Core\Fsw\fsw_compose_symbol(['symbol' => 'S10000', 'coord' => [480, 480], 'style' => '-C']);
 * // 'S10000480x480-C'
 */
function fsw_compose_symbol($fsw_sym_object)
{
    if (!is_array($fsw_sym_object)) {
        return null;
    }
    $symbol = isset($fsw_sym_object['symbol']) ? $fsw_sym_object['symbol'] : null;
    if (!is_string($symbol)) {
        return null;
    }
    if (!preg_match('~^(' . Regex\FSW_PATTERN_SYMBOL . ')$~', $symbol, $m)) {
        return null;
    }
    $symbol = $m[1];

    $coord_str = '';
    if (isset($fsw_sym_object['coord']) && is_array($fsw_sym_object['coord']) && count($fsw_sym_object['coord']) === 2) {
        $x = strval($fsw_sym_object['coord'][0]);
        $y = strval($fsw_sym_object['coord'][1]);
        $coord_candidate = $x . 'x' . $y;
        if (preg_match('~^(' . Regex\FSW_PATTERN_COORD . ')$~', $coord_candidate, $m)) {
            $coord_str = $m[1];
        }
    }

    $style_candidate = '';
    if (isset($fsw_sym_object['style']) && is_string($fsw_sym_object['style'])) {
        if (preg_match('~^(' . Regex\STYLE_PATTERN_FULL . ')~', $fsw_sym_object['style'], $m)) {
            $style_candidate = $m[1];
        }
    }

    return $symbol . $coord_str . $style_candidate;
}

/**
 * Function to compose an fsw sign with style string.
 *
 * @param array $fsw_sign_object
 * @return string|null
 * @example
 * \Sgnw\Core\Fsw\fsw_compose_sign([
 *   'sequence' => ['S10011', 'S10019', 'S2e704', 'S2e748'],
 *   'box' => 'M',
 *   'max' => [525, 535],
 *   'spatials' => [
 *     ['symbol' => 'S2e748', 'coord' => [483, 510]],
 *     ['symbol' => 'S10011', 'coord' => [501, 466]],
 *     ['symbol' => 'S2e704', 'coord' => [510, 500]],
 *     ['symbol' => 'S10019', 'coord' => [476, 475]]
 *   ],
 *   'style' => '-C'
 * ]);
 */
function fsw_compose_sign($fsw_sign_object)
{
    if (!is_array($fsw_sign_object)) {
        return null;
    }
    $box = isset($fsw_sign_object['box']) ? $fsw_sign_object['box'] : '';
    if (!preg_match('~^(' . Regex\FSW_PATTERN_BOX . ')$~', $box, $m)) {
        return null;
    }
    $box = $m[1];

    $max_str = '';
    if (isset($fsw_sign_object['max']) && is_array($fsw_sign_object['max']) && count($fsw_sign_object['max']) === 2) {
        $x = strval($fsw_sign_object['max'][0]);
        $y = strval($fsw_sign_object['max'][1]);
        $candidate = $x . 'x' . $y;
        if (preg_match('~^(' . Regex\FSW_PATTERN_COORD . ')$~', $candidate, $m)) {
            $max_str = $m[1];
        }
    }
    if ($max_str === '') {
        return null;
    }

    $prefix = '';
    if (isset($fsw_sign_object['sequence']) && is_array($fsw_sign_object['sequence'])) {
        $prefix_parts = [];
        foreach ($fsw_sign_object['sequence'] as $key) {
            if (preg_match('~^(' . Regex\FSW_PATTERN_NULL_OR_SYMBOL . ')$~', $key, $m)) {
                $prefix_parts[] = $m[1];
            }
        }
        $prefix = $prefix_parts ? 'A' . implode('', $prefix_parts) : '';
    }

    $signbox = '';
    if (isset($fsw_sign_object['spatials']) && is_array($fsw_sign_object['spatials'])) {
        $signbox_parts = [];
        foreach ($fsw_sign_object['spatials'] as $spatial) {
            if (!is_array($spatial)) {
                continue;
            }
            $sym = isset($spatial['symbol']) ? $spatial['symbol'] : null;
            if (!is_string($sym)) {
                continue;
            }
            if (!preg_match('~^(' . Regex\FSW_PATTERN_SYMBOL . ')$~', $sym, $m)) {
                continue;
            }
            $sym = $m[1];

            $coord_str = '';
            if (isset($spatial['coord']) && is_array($spatial['coord']) && count($spatial['coord']) === 2) {
                $x = strval($spatial['coord'][0]);
                $y = strval($spatial['coord'][1]);
                $coord_candidate = $x . 'x' . $y;
                if (preg_match('~^(' . Regex\FSW_PATTERN_COORD . ')$~', $coord_candidate, $m)) {
                    $coord_str = $m[1];
                }
            }
            if ($coord_str === '') {
                continue;
            }
            $signbox_parts[] = $sym . $coord_str;
        }
        $signbox = implode('', $signbox_parts);
    }

    $style_candidate = '';
    if (isset($fsw_sign_object['style']) && is_string($fsw_sign_object['style'])) {
        if (preg_match('~^(' . Regex\STYLE_PATTERN_FULL . ')$~', $fsw_sign_object['style'], $m)) {
            $style_candidate = $m[1];
        }
    }

    return $prefix . $box . $max_str . $signbox . $style_candidate;
}

// ----------------------------
// FSW Info
// ----------------------------

/**
 * Function to gather sizing information about an fsw sign or symbol.
 *
 * @param string $fsw
 * @return array
 * @example
 * \Sgnw\Core\Fsw\fsw_info('AS14c20S27106L518x529S14c20481x471S27106503x489-P10Z2');
 */
function fsw_info($fsw)
{
    $lanes = ['B' => 0, 'L' => -1, 'M' => 0, 'R' => 1];
    $parsed_sign = fsw_parse_sign($fsw);
    $width = null;
    $height = null;
    $segment = 'none';
    $x1 = 490;
    $y1 = 490;
    $lane = 0;
    $style = \Sgnw\Core\Style\style_parse(isset($parsed_sign['style']) ? $parsed_sign['style'] : '');

    if (isset($parsed_sign['spatials'])) {
        $x_coords = [];
        $y_coords = [];
        foreach ($parsed_sign['spatials'] as $spatial) {
            $x_coords[] = $spatial['coord'][0];
            $y_coords[] = $spatial['coord'][1];
        }
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
        $parsed_symbol = fsw_parse_symbol($fsw);
        $lane = $lanes['M'];
        if (isset($parsed_symbol['coord'])) {
            $x1 = $parsed_symbol['coord'][0];
            $y1 = $parsed_symbol['coord'][1];
            $width = (500 - $x1) * 2;
            $height = (500 - $y1) * 2;
            $segment = 'symbol';
        }
        $style = \Sgnw\Core\Style\style_parse(isset($parsed_symbol['style']) ? $parsed_symbol['style'] : '');
    }

    if (!is_array($style)) {
        $style = [];
    }
    $zoom = isset($style['zoom']) ? $style['zoom'] : 1;
    $padding = isset($style['padding']) ? $style['padding'] : 0;

    return [
        'minX' => $x1,
        'minY' => $y1,
        'width' => $width ?: 20,
        'height' => $height ?: 20,
        'segment' => $segment,
        'lane' => $lane,
        'padding' => $padding,
        'zoom' => $zoom,
    ];
}

// ----------------------------
// FSW Columns
// ----------------------------

function fsw_column_defaults()
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
 * \Sgnw\Core\Fsw\fsw_column_defaults_merge(['height' => 500, 'width' => 150]);
 */
function fsw_column_defaults_merge($options = null)
{
    if (!is_array($options)) {
        $options = [];
    }
    $defaults = fsw_column_defaults();
    $merged = array_merge($defaults, $options);
    $merged['punctuation'] = array_merge($defaults['punctuation'], isset($options['punctuation']) && is_array($options['punctuation']) ? $options['punctuation'] : []);
    $merged['style'] = array_merge($defaults['style'], isset($options['style']) && is_array($options['style']) ? $options['style'] : []);
    return Convert\drop_none($merged);
}

/**
 * Function to transform an FSW text to an array of columns.
 *
 * @param string $fsw_text
 * @param array|null $options
 * @return array
 * @example
 * \Sgnw\Core\Fsw\fsw_columns('AS14c20S27106M518x529S14c20481x471S27106503x489 AS18701S1870aS2e734S20500M518x533S1870a489x515S18701482x490S20500508x496S2e734500x468 S38800464x496', ['height' => 500, 'width' => 150]);
 */
function fsw_columns($fsw_text, $options = null)
{
    if (!is_string($fsw_text)) {
        return [];
    }
    $values = fsw_column_defaults_merge($options);
    if (isset($values['style']['zoom']) && $values['style']['zoom'] === 'x') {
        $values['style']['zoom'] = 1.0;
    }

    $input_text = fsw_parse_text($fsw_text);
    if (!$input_text) {
        return [];
    }

    $cursor = 0.0;
    $cols = [];
    $col = [];
    $plus = 0;
    $center = intdiv($values['width'], 2);
    $max_height = $values['height'] - $values['margin'];
    $pullable = true;
    $finalize = false;

    foreach ($input_text as $val) {
        $informed = fsw_info($val);
        if (isset($informed['zoom']) && $informed['zoom'] === 'x') {
            $informed['zoom'] = 1.0;
        }

        $cursor += $plus;
        if ($values['punctuation']['spacing']) {
            $cursor += $informed['segment'] === 'sign' ? $values['pad'] : 0;
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
            - (
                (500 - $informed['minX'])
                * Convert\to_zoom($informed['zoom'])
                * Convert\to_zoom($values['style']['zoom'])
            )
        );
        $item['y'] = intval($cursor);
        $item['text'] = $val;
        $col[] = $item;
        $cursor += (
            $informed['height']
            * Convert\to_zoom($informed['zoom'])
            * Convert\to_zoom($values['style']['zoom'])
        );

        if ($values['punctuation']['spacing']) {
            $plus = $informed['segment'] === 'sign' ? $values['pad'] : $values['punctuation']['pad'];
        } else {
            $plus = $values['pad'];
        }
    }

    if ($col) {
        $cols[] = $col;
    }

    if ($values['punctuation']['pull']) {
        foreach ($cols as $ci => $c) {
            $last = $c[count($c) - 1];
            $diff = ($last['y'] + $last['height']) - ($values['height'] - $values['margin']);
            if ($diff > 0) {
                $adj = intdiv($diff, count($c)) + 1;
                foreach ($c as $i => $item) {
                    $cols[$ci][$i]['y'] -= ($adj * $i + $adj);
                }
            }
        }
    }

    $widths = [];
    foreach ($cols as $ci => $c) {
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
            $adj = $center - intdiv(($min_val + $max_val), 2);
        } else {
            $width = $max_val - $min_val;
            $adj = -$min_val;
        }

        foreach ($c as $i => $item) {
            $cols[$ci][$i]['x'] += $adj;
        }
        $widths[] = $width;
    }

    return ['options' => $values, 'widths' => $widths, 'columns' => $cols];
}

// ----------------------------
// FSW Tokenizer
// ----------------------------

function _default_special_tokens()
{
    return [
        ['index' => 0, 'name' => 'UNK', 'value' => '[UNK]'],
        ['index' => 1, 'name' => 'PAD', 'value' => '[PAD]'],
        ['index' => 2, 'name' => 'CLS', 'value' => '[CLS]'],
        ['index' => 3, 'name' => 'SEP', 'value' => '[SEP]'],
    ];
}

function _generate_tokens()
{
    $sequence = ['A'];
    $signbox = ['B', 'L', 'M', 'R'];
    $null_token = ['S000'];
    $base_symbols = [];
    for ($i = 0x100; $i <= 0x38B; $i++) {
        $base_symbols[] = sprintf('S%03x', $i);
    }
    $rows = [];
    for ($i = 0; $i < 16; $i++) {
        $rows[] = 'r' . dechex($i);
    }
    $cols = [];
    for ($i = 0; $i < 6; $i++) {
        $cols[] = 'c' . dechex($i);
    }
    $positions = [];
    for ($i = 250; $i <= 750; $i++) {
        $positions[] = 'p' . $i;
    }
    return array_merge($sequence, $signbox, $null_token, $base_symbols, $rows, $cols, $positions);
}

class SpecialTokenMappings
{
    public $by_index = [];
    public $by_name = [];
    public $by_value = [];

    public function __construct($by_index, $by_name, $by_value)
    {
        $this->by_index = $by_index;
        $this->by_name = $by_name;
        $this->by_value = $by_value;
    }

    public function get_by_index($index)
    {
        $unk = null;
        foreach ($this->by_index as $tok) {
            if ($tok['name'] === 'UNK') {
                $unk = $tok;
                break;
            }
        }
        return isset($this->by_index[$index]) ? $this->by_index[$index] : $unk;
    }

    public function get_by_name($name)
    {
        return isset($this->by_name[$name]) ? $this->by_name[$name] : $this->by_name['UNK'];
    }

    public function get_by_value($value)
    {
        return isset($this->by_value[$value]) ? $this->by_value[$value] : $this->by_name['UNK'];
    }

    public function get_all_values()
    {
        $vals = [];
        foreach ($this->by_index as $tok) {
            $vals[] = $tok['value'];
        }
        return $vals;
    }

    public function get_all_indices()
    {
        $vals = [];
        foreach ($this->by_index as $tok) {
            $vals[] = $tok['index'];
        }
        return $vals;
    }
}

function _create_special_token_mappings($special_tokens)
{
    $by_index = [];
    $by_name = [];
    $by_value = [];
    $indices = [];
    foreach ($special_tokens as $token) {
        if (in_array($token['index'], $indices, true)) {
            throw new \InvalidArgumentException('Duplicate token index: ' . $token['index']);
        }
        $indices[] = $token['index'];
        $by_index[$token['index']] = $token;
        $by_name[$token['name']] = $token;
        $by_value[$token['value']] = $token;
    }
    return new SpecialTokenMappings($by_index, $by_name, $by_value);
}

function _create_token_mappings($tokens, $special_token_mappings, $starting_index)
{
    $i2s = [];
    $s2i = [];
    foreach ($special_token_mappings->by_index as $tok) {
        $i2s[$tok['index']] = $tok['value'];
        $s2i[$tok['value']] = $tok['index'];
    }
    foreach ($tokens as $i => $token) {
        $index = $starting_index + $i;
        $i2s[$index] = $token;
        $s2i[$token] = $index;
    }
    return ['i2s' => $i2s, 's2i' => $s2i];
}

/**
 * Tokenizes an FSW string into an array of tokens.
 *
 * @param string $fsw
 * @param bool $sequence
 * @param bool $signbox
 * @param string|null $sep
 * @return array
 * @example
 * \Sgnw\Core\Fsw\fsw_tokenize("AS10e00M507x515S10e00492x485", false, true, null);
 */
function fsw_tokenize($fsw, $sequence = true, $signbox = true, $sep = '[SEP]')
{
    $tokenize_symbol = function ($symbol) {
        return [substr($symbol, 0, 4), 'c' . $symbol[4], 'r' . $symbol[5]];
    };
    $tokenize_coord = function ($coord) {
        return ['p' . $coord[0], 'p' . $coord[1]];
    };

    $segments = fsw_parse_text($fsw);
    $result = [];

    foreach ($segments as $segment) {
        $tokens = [];
        if (preg_match('/[BLMR]/', $segment)) {
            $sign = fsw_parse_sign($segment);

            if (isset($sign['sequence']) && $sequence) {
                $tokens[] = 'A';
                foreach ($sign['sequence'] as $seq_item) {
                    $tokens = array_merge($tokens, $tokenize_symbol($seq_item));
                }
            }

            if ($signbox) {
                $tokens[] = $sign['box'];
                $tokens = array_merge($tokens, $tokenize_coord($sign['max']));
                if (isset($sign['spatials'])) {
                    foreach ($sign['spatials'] as $spatial) {
                        $tokens = array_merge($tokens, $tokenize_symbol($spatial['symbol']));
                        $tokens = array_merge($tokens, $tokenize_coord($spatial['coord']));
                    }
                }
            }
        } else {
            $parsed = fsw_parse_symbol($segment);

            if (!$signbox && !$sequence) {
                continue;
            }

            if (!$signbox && $sequence) {
                $tokens = array_merge(['A'], $tokenize_symbol($parsed['symbol']));
            } else {
                $coord = isset($parsed['coord']) ? $parsed['coord'] : null;
                if ($coord) {
                    $rev = [1000 - $coord[0], 1000 - $coord[1]];
                    $tokens = array_merge(
                        ['M'],
                        $tokenize_coord($rev),
                        $tokenize_symbol($parsed['symbol']),
                        $tokenize_coord($coord)
                    );
                }
            }
        }

        if ($tokens && $sep !== null) {
            $tokens[] = $sep;
        }
        $result = array_merge($result, $tokens);
    }

    return $result;
}

/**
 * Converts an array of tokens back into an FSW string.
 *
 * @param array $tokens
 * @param array|null $special_tokens
 * @return string
 * @example
 * \Sgnw\Core\Fsw\fsw_detokenize(['M', 'p507', 'p515', 'S10e', 'c0', 'r0', 'p492', 'p485']);
 */
function fsw_detokenize($tokens, $special_tokens = null)
{
    $special_tokens = $special_tokens ?: _default_special_tokens();
    $special_values = [];
    foreach ($special_tokens as $t) {
        $special_values[] = $t['value'];
    }
    $filtered = array_values(array_filter($tokens, function ($t) use ($special_values) {
        return !in_array($t, $special_values, true);
    }));
    $joined = implode(' ', $filtered);
    $joined = preg_replace('/p(\\d{3})\\s+p(\\d{3})/', '$1x$2', $joined);
    $joined = preg_replace('/ c(\\d)\\d? r(.)/', '$1$2', $joined);
    $joined = preg_replace('/ c(\\d)\\d?/', '${1}0', $joined);
    $joined = preg_replace('/ r(.)/', '0$1', $joined);
    $joined = str_replace(' ', '', $joined);
    $joined = preg_replace('/(\\d)([BLMR])/', '$1 $2', $joined);
    $joined = preg_replace('/(\\d)(AS)/', '$1 $2', $joined);
    $joined = preg_replace('/(A(?:S00000|S[123][0-9a-f]{2}[0-5][0-9a-f])+)( )([BLMR])/', '$1$3', $joined);
    return $joined;
}

/**
 * Splits tokens into chunks of specified size while preserving sign boundaries.
 *
 * @param array $tokens
 * @param int $chunk_size
 * @param string $cls
 * @param string $sep
 * @param string $pad
 * @return array
 */
function fsw_chunk_tokens($tokens, $chunk_size, $cls = '[CLS]', $sep = '[SEP]', $pad = '[PAD]')
{
    if ($chunk_size < 60) {
        throw new \InvalidArgumentException('Chunk size must be at least 60 tokens to accommodate a typical sign');
    }

    $chunks = [];
    $token_index = 0;
    $total = count($tokens);

    while ($token_index < $total) {
        $current_chunk = [$cls];

        while ($token_index < $total) {
            $look_ahead = $token_index;
            while ($look_ahead < $total && $tokens[$look_ahead] !== $sep) {
                $look_ahead++;
            }
            $sign_size = $look_ahead - $token_index + 1;
            if (count($current_chunk) + $sign_size > $chunk_size - 1) {
                break;
            }
            for ($i = $token_index; $i <= $look_ahead; $i++) {
                $current_chunk[] = $tokens[$i];
            }
            $token_index = $look_ahead + 1;
        }

        while (count($current_chunk) < $chunk_size) {
            $current_chunk[] = $pad;
        }

        $chunks[] = $current_chunk;
    }

    return $chunks;
}

class FSWTokenizer
{
    public $special_tokens;
    public $i2s = [];
    public $s2i = [];
    public $length = 0;

    public function __construct($special_tokens = null, $starting_index = null)
    {
        $this->special_tokens = _create_special_token_mappings($special_tokens === null ? _default_special_tokens() : $special_tokens);
        $max_special = -1;
        $indices = $this->special_tokens->get_all_indices();
        if ($indices) {
            $max_special = max($indices);
        }
        $calculated_starting_index = $starting_index !== null ? $starting_index : ($max_special + 1);
        $tokens = _generate_tokens();
        $mappings = _create_token_mappings($tokens, $this->special_tokens, $calculated_starting_index);
        $this->i2s = $mappings['i2s'];
        $this->s2i = $mappings['s2i'];
        $this->length = count($this->i2s);
    }

    public function vocab()
    {
        return array_values($this->i2s);
    }

    public function encode_tokens($tokens)
    {
        $out = [];
        foreach ($tokens as $t) {
            $out[] = isset($this->s2i[$t]) ? $this->s2i[$t] : $this->special_tokens->get_by_value($t)['index'];
        }
        return $out;
    }

    public function decode_tokens($indices)
    {
        $unk_value = $this->special_tokens->get_by_name('UNK')['value'];
        $out = [];
        foreach ($indices as $i) {
            $out[] = isset($this->i2s[$i]) ? $this->i2s[$i] : $unk_value;
        }
        return $out;
    }

    public function encode($text, $sequence = true, $signbox = true)
    {
        $sep_value = $this->special_tokens->get_by_name('SEP')['value'];
        $tokens = fsw_tokenize($text, $sequence, $signbox, $sep_value);
        return $this->encode_tokens($tokens);
    }

    public function decode($tokens)
    {
        if (!$tokens) {
            return '';
        }
        if (is_array($tokens[0])) {
            $decoded_chunks = [];
            foreach ($tokens as $chunk) {
                $decoded_chunks = array_merge($decoded_chunks, $this->decode_tokens($chunk));
            }
            return fsw_detokenize($decoded_chunks, array_values($this->special_tokens->by_index));
        }
        $decoded_tokens = $this->decode_tokens($tokens);
        return fsw_detokenize($decoded_tokens, array_values($this->special_tokens->by_index));
    }

    public function chunk($tokens, $chunk_size)
    {
        return fsw_chunk_tokens(
            $tokens,
            $chunk_size,
            $this->special_tokens->get_by_name('CLS')['value'],
            $this->special_tokens->get_by_name('SEP')['value'],
            $this->special_tokens->get_by_name('PAD')['value']
        );
    }
}
