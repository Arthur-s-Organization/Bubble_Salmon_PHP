<?php

namespace src\Service;

class CryptService
{
    // Algorithme utilisé pour le chiffrement des blocs
    private static $encrypt_method  = "aes-128-ctr";
    private static $key = "your_key";  //Générer une clef de manière cryptographique (comme openssl_random_pseudo_bytes)
    private static $secret_iv = 'your_secret_iv';

    public static function encrypt($data):?String{
        $length = openssl_cipher_iv_length(SELF::$encrypt_method);
        $iv = openssl_random_pseudo_bytes($length);
        $encrypt_text = openssl_encrypt($data, SELF::$encrypt_method, SELF::$key, $options=OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $encrypt_text, SELF::$key, $as_binary=true);
        $output = base64_encode($iv.$hmac.$encrypt_text);
        return $output;
    }


    public static function decrypt($data):?String{
        $c = base64_decode(($data));
        $length = openssl_cipher_iv_length(SELF::$encrypt_method);
        $iv = substr($c,0,$length);
        $hmac = substr($c, $length, $sha2len=32);
        $decrypt_text = substr($c, $length+$sha2len);
        $original_plaintext = openssl_decrypt($decrypt_text, SELF::$encrypt_method,  SELF::$key, $options=OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $decrypt_text,  SELF::$key, $as_binary=true);
        if (hash_equals($hmac, $calcmac))// timing attack safe comparison
        {
            return $original_plaintext;
        }

    }
}
