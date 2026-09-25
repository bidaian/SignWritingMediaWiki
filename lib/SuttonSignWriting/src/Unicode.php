<?php
namespace Sgnw\Core;

// Unicode helpers for PHP 7.3 using mbstring.

class Unicode
{
    public static function codePointAt($str, $index)
    {
        if (!is_string($str)) {
            return null;
        }
        $len = mb_strlen($str, 'UTF-8');
        if (!is_int($index)) {
            $index = intval($index);
        }
        if ($index < 0 || $index >= $len) {
            return null;
        }
        $ch = mb_substr($str, $index, 1, 'UTF-8');
        if ($ch === '') {
            return null;
        }
        if (function_exists('mb_ord')) {
            $ord = mb_ord($ch, 'UTF-8');
            if ($ord !== false) {
                return $ord;
            }
        }

        $u = mb_convert_encoding($ch, 'UCS-4BE', 'UTF-8');
        $code = unpack('N', $u);
        return $code[1];
    }

    public static function fromCodePoint($code)
    {
        if (!is_int($code)) {
            $code = intval($code);
        }
        if ($code < 0) {
            return '';
        }
        if (function_exists('mb_chr')) {
            $ch = mb_chr($code, 'UTF-8');
            if ($ch !== false) {
                return $ch;
            }
        }
        return mb_convert_encoding(pack('N', $code), 'UTF-8', 'UCS-4BE');
    }

    public static function substrCp($str, $start, $length = null)
    {
        if (!is_string($str)) {
            return '';
        }
        if (!is_int($start)) {
            $start = intval($start);
        }
        if ($length === null) {
            return mb_substr($str, $start, null, 'UTF-8');
        }
        if (!is_int($length)) {
            $length = intval($length);
        }
        return mb_substr($str, $start, $length, 'UTF-8');
    }
}
