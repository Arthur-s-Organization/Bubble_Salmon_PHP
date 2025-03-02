<?php

namespace src\Utils;
class StringValidator {
    public static function isOnlyLetters(string $str): bool {
        return preg_match('/^[\p{L}]+$/u', $str) === 1;
    }
}
