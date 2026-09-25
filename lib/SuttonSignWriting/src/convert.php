<?php
namespace Sgnw\Core\Convert;

use Sgnw\Core\Unicode;
use Sgnw\Core\Regex;

/**
 * The convert module contains functions to convert between Formal SignWriting in ASCII (FSW)
 * and SignWriting in Unicode (SWU) characters, along with other types of data.
 *
 * Character set definitions:
 * https://datatracker.ietf.org/doc/id/draft-slevinski-formal-signwriting-10.html#name-character-sets
 */

// ----------------------------
// Utility functions
// ----------------------------

/**
 * Remove key-value pairs from a dictionary where the value is null.
 *
 * @param array $arr
 * @return array
 * @example
 * \Sgnw\Core\Convert\drop_none(['a' => 1, 'b' => null, 'c' => 'x']);
 * // ['a' => 1, 'c' => 'x']
 */
function drop_none($arr)
{
    if (!is_array($arr)) {
        return [];
    }
    return array_filter($arr, function ($v) {
        return $v !== null;
    });
}

/**
 * Convert zoom to float, handling 'x' and null.
 *
 * @param mixed $zoom
 * @return float
 * @example
 * \Sgnw\Core\Convert\to_zoom('x'); // 1.0
 */
function to_zoom($zoom)
{
    if ($zoom === 'x' || $zoom === null) {
        return 1.0;
    }
    return floatval($zoom);
}

// ----------------------------
// Convert functions
// ----------------------------
// An array of symbol IDs in minimized format such as "101011"
function symid_arr() {
    static $arr = [
        "101011",
        "101021",
        "101031",
        "101041",
        "101051",
        "101061",
        "101071",
        "101081",
        "101091",
        "101101",
        "101111",
        "101121",
        "101131",
        "101141",
        "102011",
        "102021",
        "102031",
        "102041",
        "102051",
        "102061",
        "102071",
        "102081",
        "102091",
        "102101",
        "102111",
        "102121",
        "102131",
        "102141",
        "102151",
        "102161",
        "103011",
        "103021",
        "103031",
        "103041",
        "103051",
        "103061",
        "103071",
        "103081",
        "103091",
        "103101",
        "103111",
        "103121",
        "103131",
        "103141",
        "103151",
        "103161",
        "103171",
        "103181",
        "103191",
        "103201",
        "103211",
        "103221",
        "103231",
        "103241",
        "103251",
        "103261",
        "103271",
        "103281",
        "103291",
        "103301",
        "103311",
        "103321",
        "103331",
        "103341",
        "103351",
        "103361",
        "103371",
        "103381",
        "104011",
        "104021",
        "104031",
        "104041",
        "104051",
        "104061",
        "104071",
        "104081",
        "105011",
        "105021",
        "105031",
        "105041",
        "105051",
        "105061",
        "105071",
        "105081",
        "105091",
        "105101",
        "105111",
        "105121",
        "105131",
        "105141",
        "105151",
        "105161",
        "105171",
        "105181",
        "105191",
        "105201",
        "105211",
        "105221",
        "105231",
        "105241",
        "105251",
        "105261",
        "105271",
        "105281",
        "105291",
        "105301",
        "105311",
        "105321",
        "105331",
        "105341",
        "105351",
        "105361",
        "105371",
        "105381",
        "105391",
        "105401",
        "105411",
        "105421",
        "105431",
        "105441",
        "105451",
        "105461",
        "105471",
        "105481",
        "105491",
        "105501",
        "105511",
        "105521",
        "105531",
        "105541",
        "105551",
        "105561",
        "105571",
        "105581",
        "106011",
        "106021",
        "106031",
        "106041",
        "106051",
        "106061",
        "106071",
        "106081",
        "106091",
        "106101",
        "106111",
        "106121",
        "106131",
        "106141",
        "106151",
        "106161",
        "106171",
        "106181",
        "106191",
        "106201",
        "106211",
        "106221",
        "106231",
        "106241",
        "106251",
        "106261",
        "106271",
        "106281",
        "106291",
        "106301",
        "107011",
        "107021",
        "107031",
        "107041",
        "107051",
        "107061",
        "107071",
        "107081",
        "107091",
        "107101",
        "107111",
        "107121",
        "107131",
        "107141",
        "107151",
        "107161",
        "107171",
        "107181",
        "107191",
        "107201",
        "107211",
        "107221",
        "108011",
        "108021",
        "108031",
        "108041",
        "108051",
        "108061",
        "108071",
        "108081",
        "108091",
        "108101",
        "108111",
        "108121",
        "108131",
        "108141",
        "108151",
        "108161",
        "108171",
        "108181",
        "108191",
        "109011",
        "109021",
        "109031",
        "109041",
        "109051",
        "109061",
        "109071",
        "109081",
        "109091",
        "109101",
        "109111",
        "109121",
        "109131",
        "109141",
        "109151",
        "109161",
        "109171",
        "109181",
        "109191",
        "109201",
        "109211",
        "109221",
        "109231",
        "109241",
        "109251",
        "109261",
        "109271",
        "109281",
        "109291",
        "109301",
        "109311",
        "109321",
        "109331",
        "109341",
        "109351",
        "109361",
        "109371",
        "109381",
        "109391",
        "109401",
        "110011",
        "110021",
        "110031",
        "110041",
        "110051",
        "110061",
        "110071",
        "110081",
        "110091",
        "110101",
        "110111",
        "110121",
        "110131",
        "110141",
        "110151",
        "110161",
        "201011",
        "201021",
        "201031",
        "201041",
        "201051",
        "201061",
        "201071",
        "201081",
        "201091",
        "201101",
        "201111",
        "201121",
        "201131",
        "201141",
        "201151",
        "201161",
        "201171",
        "202011",
        "202012",
        "202021",
        "202022",
        "202031",
        "202041",
        "202042",
        "202051",
        "202052",
        "202061",
        "202071",
        "202081",
        "202082",
        "202091",
        "202092",
        "202101",
        "202102",
        "202111",
        "202121",
        "202131",
        "203011",
        "203012",
        "203013",
        "203014",
        "203015",
        "203021",
        "203022",
        "203031",
        "203032",
        "203041",
        "203051",
        "203052",
        "203061",
        "203062",
        "203071",
        "203072",
        "203073",
        "203081",
        "203082",
        "203083",
        "203084",
        "203091",
        "203092",
        "203093",
        "203101",
        "203102",
        "203103",
        "203111",
        "203112",
        "203113",
        "203121",
        "203122",
        "203123",
        "203131",
        "203141",
        "203151",
        "203161",
        "203171",
        "203181",
        "203191",
        "203201",
        "203202",
        "203203",
        "204011",
        "204012",
        "204013",
        "204014",
        "204021",
        "204022",
        "204023",
        "204024",
        "204031",
        "204032",
        "204033",
        "204034",
        "204041",
        "204042",
        "204043",
        "204044",
        "205011",
        "205012",
        "205013",
        "205014",
        "205015",
        "205021",
        "205022",
        "205031",
        "205032",
        "205041",
        "205051",
        "205052",
        "205061",
        "205062",
        "205071",
        "205081",
        "205082",
        "205083",
        "205091",
        "205101",
        "205102",
        "205103",
        "205111",
        "205112",
        "205113",
        "205121",
        "205122",
        "205123",
        "205131",
        "205141",
        "205151",
        "205161",
        "205171",
        "205181",
        "205191",
        "206011",
        "206012",
        "206013",
        "206014",
        "206021",
        "206022",
        "206023",
        "206024",
        "206031",
        "206032",
        "206041",
        "206042",
        "206043",
        "206051",
        "206052",
        "206053",
        "206054",
        "206061",
        "206062",
        "206063",
        "206064",
        "206065",
        "206066",
        "206071",
        "206072",
        "206073",
        "206081",
        "206091",
        "206101",
        "206111",
        "207011",
        "207021",
        "207031",
        "207041",
        "207051",
        "207061",
        "207071",
        "207091",
        "207101",
        "207111",
        "207121",
        "207131",
        "207141",
        "207151",
        "207161",
        "207162",
        "207163",
        "208011",
        "208012",
        "208021",
        "208022",
        "208023",
        "208024",
        "208031",
        "208032",
        "208033",
        "208034",
        "208041",
        "208042",
        "208051",
        "208061",
        "208071",
        "208081",
        "208082",
        "208091",
        "208092",
        "208093",
        "208094",
        "208101",
        "208102",
        "208103",
        "208104",
        "208111",
        "208112",
        "208121",
        "208131",
        "208141",
        "209011",
        "209012",
        "209013",
        "209014",
        "209015",
        "209021",
        "209031",
        "209041",
        "209042",
        "209043",
        "209051",
        "209061",
        "209071",
        "209081",
        "210011",
        "210012",
        "210021",
        "210022",
        "210031",
        "210032",
        "210033",
        "210041",
        "210042",
        "210043",
        "210051",
        "210052",
        "210061",
        "210062",
        "210071",
        "210072",
        "210073",
        "210074",
        "210081",
        "210082",
        "301011",
        "301021",
        "301031",
        "301032",
        "301041",
        "301042",
        "301043",
        "301044",
        "401011",
        "401021",
        "401031",
        "401041",
        "401051",
        "401061",
        "401071",
        "401081",
        "401091",
        "401101",
        "401102",
        "402011",
        "402012",
        "402013",
        "402021",
        "402022",
        "402023",
        "402024",
        "402031",
        "402032",
        "402033",
        "402041",
        "402042",
        "402043",
        "402044",
        "402045",
        "402051",
        "402052",
        "402053",
        "402054",
        "402055",
        "402061",
        "402062",
        "402063",
        "402071",
        "402072",
        "402073",
        "402081",
        "402082",
        "402083",
        "402091",
        "402101",
        "402111",
        "403011",
        "403012",
        "403013",
        "403021",
        "403022",
        "403023",
        "403031",
        "403041",
        "403042",
        "403043",
        "403044",
        "403051",
        "403052",
        "403061",
        "403062",
        "403071",
        "403072",
        "404011",
        "404012",
        "404013",
        "404021",
        "404022",
        "404023",
        "404031",
        "404032",
        "404033",
        "404041",
        "404042",
        "404043",
        "404051",
        "404052",
        "404053",
        "404061",
        "404062",
        "404063",
        "404071",
        "404072",
        "404073",
        "404081",
        "404082",
        "404083",
        "404091",
        "404092",
        "404093",
        "404101",
        "404111",
        "404112",
        "405011",
        "405012",
        "405013",
        "405014",
        "405015",
        "405021",
        "405031",
        "405041",
        "405051",
        "405052",
        "405061",
        "405062",
        "405071",
        "405072",
        "405081",
        "405091",
        "405101",
        "405111",
        "405121",
        "405131",
        "501011",
        "501021",
        "501031",
        "501041",
        "501051",
        "501061",
        "501071",
        "501081",
        "501091",
        "502011",
        "502021",
        "502022",
        "502023",
        "502024",
        "502031",
        "502032",
        "502033",
        "502041",
        "601011",
        "601012",
        "601021",
        "601031",
        "601041",
        "601051",
        "601061",
        "601071",
        "701011",
        "701012",
        "701021",
        "701022",
        "701031",
    ];
    return $arr;
}


function _swu_to_mark_map()
{
    static $map = null;
    if ($map === null) {
        $map = [
            Unicode::fromCodePoint(0x1D800) => 'A',
            Unicode::fromCodePoint(0x1D801) => 'B',
            Unicode::fromCodePoint(0x1D802) => 'L',
            Unicode::fromCodePoint(0x1D803) => 'M',
            Unicode::fromCodePoint(0x1D804) => 'R',
        ];
    }
    return $map;
}

function _mark_to_swu_map()
{
    static $map = null;
    if ($map === null) {
        $map = array_flip(_swu_to_mark_map());
    }
    return $map;
}

/**
 * Convert an SWU structural marker to FSW equivalent.
 *
 * @param string $swuMark
 * @return string
 * @example
 * \Sgnw\Core\Convert\swu_to_mark('𝠀'); // 'A'
 */
function swu_to_mark($swuMark)
{
    $map = _swu_to_mark_map();
    return isset($map[$swuMark]) ? $map[$swuMark] : '';
}

/**
 * Convert an FSW structural marker to SWU equivalent.
 *
 * @param string $fswMark
 * @return string
 * @example
 * \Sgnw\Core\Convert\mark_to_swu('A'); // '𝠀'
 */
function mark_to_swu($fswMark)
{
    $map = _mark_to_swu_map();
    return isset($map[$fswMark]) ? $map[$fswMark] : '';
}

/**
 * Convert an SWU number character to an integer.
 *
 * @param string $swuNum
 * @return int
 * @example
 * \Sgnw\Core\Convert\swu_to_num('𝤆'); // 500
 */
function swu_to_num($swuNum)
{
    $cp = Unicode::codePointAt($swuNum, 0);
    if ($cp === null) {
        return 0;
    }
    return intval($cp) - 0x1D80C + 250;
}

/**
 * Convert a number to an SWU number character.
 *
 * @param int $num
 * @return string
 * @example
 * \Sgnw\Core\Convert\num_to_swu(500); // '𝤆'
 */
function num_to_swu($num)
{
    return Unicode::fromCodePoint(0x1D80C + intval($num) - 250);
}

/**
 * Convert two SWU number characters to an [x, y] integer list.
 *
 * @param string $swuCoord
 * @return array
 * @example
 * \Sgnw\Core\Convert\swu_to_coord('𝤆𝤆'); // [500, 500]
 */
function swu_to_coord($swuCoord)
{
    $x = Unicode::substrCp($swuCoord, 0, 1);
    $y = Unicode::substrCp($swuCoord, 1, 1);
    if ($x === '' || $y === '') {
        return [];
    }
    return [swu_to_num($x), swu_to_num($y)];
}

/**
 * Convert an [x, y] integer list to two SWU number characters.
 *
 * @param array $coord
 * @return string
 * @example
 * \Sgnw\Core\Convert\coord_to_swu([500, 500]); // '𝤆𝤆'
 */
function coord_to_swu($coord)
{
    if (!is_array($coord)) {
        return '';
    }
    if (count($coord) !== 2) {
        return '';
    }
    return num_to_swu($coord[0]) . num_to_swu($coord[1]);
}

/**
 * Convert an FSW coordinate string to [x, y] integer list.
 *
 * @param string $fswCoord
 * @return array
 * @example
 * \Sgnw\Core\Convert\fsw_to_coord('500x500'); // [500, 500]
 */
function fsw_to_coord($fswCoord)
{
    if (!is_string($fswCoord)) {
        return [];
    }
    $parts = explode('x', $fswCoord);
    if (count($parts) !== 2) {
        return [];
    }
    if (!is_numeric($parts[0]) || !is_numeric($parts[1])) {
        return [];
    }
    return [intval($parts[0]), intval($parts[1])];
}

/**
 * Convert an [x, y] list to an FSW coordinate string.
 *
 * @param array $coord
 * @return string
 * @example
 * \Sgnw\Core\Convert\coord_to_fsw([500, 500]); // '500x500'
 */
function coord_to_fsw($coord)
{
    if (!is_array($coord) || count($coord) !== 2) {
        return '';
    }
    return strval(intval($coord[0])) . 'x' . strval(intval($coord[1]));
}

/**
 * Convert an SWU symbol character to its Unicode code point.
 *
 * @param string $swuSym
 * @return int
 * @example
 * \Sgnw\Core\Convert\swu_to_code('񀀁'); // 0x40001
 */
function swu_to_code($swuSym)
{
    $cp = Unicode::codePointAt($swuSym, 0);
    return $cp === null ? 0 : intval($cp);
}

/**
 * Convert a codepoint on plane 4 to an SWU symbol character.
 *
 * @param int $code
 * @return string
 * @example
 * \Sgnw\Core\Convert\code_to_swu(0x40001); // '񀀁'
 */
function code_to_swu($code)
{
    return Unicode::fromCodePoint(intval($code));
}

/**
 * Convert an SWU symbol character to a 16-bit ID.
 *
 * @param string $swuSym
 * @return int
 * @example
 * \Sgnw\Core\Convert\swu_to_id('񀀁'); // 1
 */
function swu_to_id($swuSym)
{
    return swu_to_code($swuSym) - 0x40000;
}

/**
 * Convert a 16-bit ID to an SWU symbol character.
 *
 * @param int $id
 * @return string
 * @example
 * \Sgnw\Core\Convert\id_to_swu(1); // '񀀁'
 */
function id_to_swu($id)
{
    return code_to_swu(intval($id) + 0x40000);
}

/**
 * Convert an FSW symbol key to a 16-bit ID.
 *
 * @param string $key
 * @return int
 * @example
 * \Sgnw\Core\Convert\key_to_id('S10000'); // 1
 */
function key_to_id($key)
{
    if ($key === 'S00000') {
        return 0;
    }
    $base = intval(substr($key, 1, 3), 16);
    $fill = intval(substr($key, 4, 1), 16);
    $rot = intval(substr($key, 5, 1), 16);
    return 1 + (($base - 0x100) * 96) + ($fill * 16) + $rot;
}

/**
 * Convert a 16-bit ID to an FSW symbol key.
 *
 * @param int $id
 * @return string
 * @example
 * \Sgnw\Core\Convert\id_to_key(1); // 'S10000'
 */
function id_to_key($id)
{
    $id = intval($id);
    if ($id === 0) {
        return 'S00000';
    }
    $symcode = $id - 1;
    $base = intdiv($symcode, 96);
    $fill = intdiv($symcode - ($base * 96), 16);
    $rot = $symcode - ($base * 96) - ($fill * 16);
    return 'S' . dechex($base + 0x100) . dechex($fill) . dechex($rot);
}

/**
 * Convert an SWU symbol character to an FSW symbol key.
 *
 * @param string $swuSym
 * @return string
 * @example
 * \Sgnw\Core\Convert\swu_to_key('񀀁'); // 'S10000'
 */
function swu_to_key($swuSym)
{
    if ($swuSym === Unicode::fromCodePoint(0x40000)) {
        return 'S00000';
    }
    $symcode = swu_to_code($swuSym) - 0x40001;
    $base = intdiv($symcode, 96);
    $fill = intdiv($symcode - ($base * 96), 16);
    $rot = $symcode - ($base * 96) - ($fill * 16);
    return 'S' . dechex($base + 0x100) . dechex($fill) . dechex($rot);
}

/**
 * Convert an FSW symbol key to an SWU symbol character.
 *
 * @param string $key
 * @return string
 * @example
 * \Sgnw\Core\Convert\key_to_swu('S10000'); // '񀀁'
 */
function key_to_swu($key)
{
    if ($key === 'S00000') {
        return Unicode::fromCodePoint(0x40000);
    }
    $base = intval(substr($key, 1, 3), 16);
    $fill = intval(substr($key, 4, 1), 16);
    $rot = intval(substr($key, 5, 1), 16);
    $code = 0x40001 + (($base - 0x100) * 96) + ($fill * 16) + $rot;
    return Unicode::fromCodePoint($code);
}

/**
 * Convert SWU text to FSW text.
 *
 * @param string $swuText
 * @return string
 * @example
 * \Sgnw\Core\Convert\swu_to_fsw('𝠀񀀒񀀚񋚥񋛩𝠃𝤟𝤩񋛩𝣵𝤐񀀒𝤇𝣤񋚥𝤐𝤆񀀚𝣮𝣭');
 * // 'AS10011S10019S2e704S2e748M525x535S2e748483x510S10011501x466S2e704510x500S10019476x475'
 */
function swu_to_fsw($swuText)
{
    if (!$swuText) {
        return '';
    }

    $fsw = $swuText;
    foreach (_swu_to_mark_map() as $swuChar => $mark) {
        $fsw = str_replace($swuChar, $mark, $fsw);
    }

    $symbols = [];
    if (preg_match_all('/' . Regex\SWU_PATTERN_NULL_OR_SYMBOL . '/u', $fsw, $m)) {
        $symbols = $m[0];
    }
    foreach ($symbols as $sym) {
        $fsw = preg_replace('/' . preg_quote($sym, '/') . '/u', swu_to_key($sym), $fsw, 1);
    }

    $coords = [];
    if (preg_match_all('/' . Regex\SWU_PATTERN_COORD . '/u', $fsw, $m)) {
        $coords = $m[0];
    }
    foreach ($coords as $coord) {
        $fsw = preg_replace('/' . preg_quote($coord, '/') . '/u', coord_to_fsw(swu_to_coord($coord)), $fsw, 1);
    }

    return $fsw;
}

/**
 * Convert FSW text to SWU text.
 *
 * @param string $fswText
 * @return string
 * @example
 * \Sgnw\Core\Convert\fsw_to_swu('AS10011S10019S2e704S2e748M525x535S2e748483x510S10011501x466S2e704510x500S10019476x475');
 * // '𝠀񀀒񀀚񋚥񋛩𝠃𝤟𝤩񋛩𝣵𝤐񀀒𝤇𝣤񋚥𝤐𝤆񀀚𝣮𝣭'
 */
function fsw_to_swu($fswText)
{
    if (!$fswText) {
        return '';
    }

    $text = $fswText;

    $prefixes = [];
    if (preg_match_all('/' . Regex\FSW_PATTERN_PREFIX . '/u', $text, $m)) {
        $prefixes = $m[0];
    }
    foreach ($prefixes as $prefix) {
        $keys = [];
        for ($i = 1; $i < strlen($prefix); $i += 6) {
            $keys[] = substr($prefix, $i, 6);
        }
        $swuSeq = '';
        foreach ($keys as $key) {
            $swuSeq .= key_to_swu($key);
        }
        $text = preg_replace('/' . preg_quote($prefix, '/') . '/u', Unicode::fromCodePoint(0x1D800) . $swuSeq, $text, 1);
    }

    $boxCoords = [];
    if (preg_match_all('/' . Regex\FSW_PATTERN_BOX . Regex\FSW_PATTERN_COORD . '/u', $text, $m)) {
        $boxCoords = $m[0];
    }
    foreach ($boxCoords as $box) {
        $mark = $box[0];
        $coordStr = substr($box, 1, 7);
        $replacement = mark_to_swu($mark) . coord_to_swu(fsw_to_coord($coordStr));
        $text = preg_replace('/' . preg_quote($box, '/') . '/u', $replacement, $text, 1);
    }

    $spatials = [];
    if (preg_match_all('/' . Regex\FSW_PATTERN_SPATIAL . '/u', $text, $m)) {
        $spatials = $m[0];
    }
    foreach ($spatials as $spatial) {
        $key = substr($spatial, 0, 6);
        $coordStr = substr($spatial, 6, 7);
        $replacement = key_to_swu($key) . coord_to_swu(fsw_to_coord($coordStr));
        $text = preg_replace('/' . preg_quote($spatial, '/') . '/u', $replacement, $text, 1);
    }

    return $text;
}

/**
 * Convert base or full symbol ID Min to symbol ID Max.
 *
 * @param string $symid_min
 * @return string
 * @example
 * \Sgnw\Core\Convert\symid_max('101011'); // '01-01-001-01'
 * @example
 * \Sgnw\Core\Convert\symid_max('101011616'); // '01-01-001-01-06-16'
 */
function symid_max($symid_min)
{
    if (!preg_match('/^\d{6}(\d{3})?$/', $symid_min)) {
        return '';
    }
    $parts = str_split(substr($symid_min, 0, 6));
    $max = '0' . $parts[0] . '-' . $parts[1] . $parts[2] . '-0' . $parts[3] . $parts[4] . '-0' . $parts[5];
    if (strlen($symid_min) > 6) {
        $extra = str_split(substr($symid_min, 6, 3));
        $max .= '-0' . $extra[0] . '-' . $extra[1] . $extra[2];
    }
    return $max;
}

/**
 * Convert base or full symbol ID Max to symbol ID Min.
 *
 * @param string $symid_max
 * @return string
 * @example
 * \Sgnw\Core\Convert\symid_min('01-01-001-01'); // '101011'
 * @example
 * \Sgnw\Core\Convert\symid_min('01-01-001-01-06-16'); // '101011616'
 */
function symid_min($symid_max)
{
    if (!preg_match('/^0(\d)-(\d{2})-0(\d{2})-0(\d)(?:-0(\d)-(\d{2}))?$/', $symid_max, $m)) {
        return '';
    }
    $base = $m[1] . $m[2] . $m[3] . $m[4];
    if (!empty($m[5])) {
        return $base . $m[5] . $m[6];
    }
    return $base;
}

/**
 * Convert base or full symbol ID Max to FSW symbol key.
 *
 * @param string $symid
 * @return string
 * @example
 * \Sgnw\Core\Convert\symid_to_key('01-01-001-01'); // 'S100'
 * @example
 * \Sgnw\Core\Convert\symid_to_key('01-01-001-01-06-16'); // 'S1005f'
 */
function symid_to_key($symid)
{
    if (!preg_match('/^0(\d)-(\d{2})-0(\d{2})-0(\d)(?:-0(\d)-(\d{2}))?$/', $symid, $m)) {
        return '';
    }
    $symid_min = $m[1] . $m[2] . $m[3] . $m[4];
    $arr = symid_arr();
    $i = array_search($symid_min, $arr, true);
    if ($i === false) {
        return '';
    }

    if (!empty($m[5])) {
        $fill = intval($m[5], 10) - 1;
        $rot = intval($m[6], 10) - 1;
        return 'S' . dechex(256 + $i) . strval($fill) . dechex($rot);
    }
    return 'S' . dechex(256 + $i);
}

/**
 * Convert base or full FSW symbol key to maximized symbol ID.
 *
 * @param string $key
 * @return string
 * @example
 * \Sgnw\Core\Convert\key_to_symid('S100'); // '01-01-001-01'
 * @example
 * \Sgnw\Core\Convert\key_to_symid('S1005f'); // '01-01-001-01-06-16'
 */
function key_to_symid($key)
{
    if (!preg_match('/^S([1-3][0-9a-f]{2})(?:([0-5])([0-9a-f]))?$/i', $key, $m)) {
        return '';
    }
    $i = intval($m[1], 16) - 256;
    $arr = symid_arr();
    if ($i >= count($arr)) {
        return '';
    }

    $base = symid_max($arr[$i]);
    if (isset($m[3]) && $m[3] !== '') {
        $fill = 1 + intval($m[2]);
        $rot = intval($m[3], 16) + 1;
        return $base . '-0' . $fill . '-' . str_pad(strval($rot), 2, '0', STR_PAD_LEFT);
    }
    return $base;
}
