<?php

namespace src\Utils;
class StringValidator {
    public static function isOnlyLetters(string $str): bool {
        return preg_match('/^[\p{L}]+$/u', $str) === 1;
    }

    public static function phoneIsValid(string $phone): bool {
        return preg_match('/^\+?[0-9]+$/', $phone) === 1;
    }
}
