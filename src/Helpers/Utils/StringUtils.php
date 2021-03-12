<?php

namespace EzeeTools\Helpers\Utils;

class StringUtils
{
    public static function beginsWith($needle, $haystack): bool
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    public static function stripBeginning($needle, $haystack)
    {
        if (self::beginsWith($needle, $haystack)) {
            $haystack = substr($haystack, strlen($needle));
        }
        return $haystack === false ? null : $haystack;
    }

    public static function camelToSnakeCase(string $string): string
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $string, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
          $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    public static function generateRandom(int $length): string
    {
        return substr(\bin2hex(random_bytes($length)), 0, $length);
    }

}
