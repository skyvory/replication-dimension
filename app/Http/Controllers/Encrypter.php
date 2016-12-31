<?php

namespace App\Http\Controllers;

use InterventionImage;

trait Encrypter
{
    public function encryptString($content) {
        $content = base64_encode($content);
        $key = pack('H*', config('constant.ENCRYPTION_HASH'));
        $key_size = strlen($key);

        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $content, MCRYPT_MODE_CBC, $iv);

        $ciphertext = $iv . $ciphertext;
        $ciphertext_base64 = base64_encode($ciphertext);

        return $ciphertext_base64;
    }

    public function decryptString($ciphertext_base64) {
        $key = pack('H*', config('constant.ENCRYPTION_HASH'));
        $iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $ciphertext_dec = base64_decode($ciphertext_base64);
        $iv_dec = substr($ciphertext_dec, 0, $iv_size);
        $ciphertext_dec = substr($ciphertext_dec, $iv_size);
        $raw = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);
        $raw = base64_decode($raw);
        $raw = rtrim($raw, "\0");
        return $raw;        
    }
}