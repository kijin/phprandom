<?php

/*
 * -----------------------------------------------------------------------------
 *      PHPRandom : Just another random number/string generator for PHP 5+
 * -----------------------------------------------------------------------------
 *
 * Copyright (c) 2012-2014, Kijin Sung <kijin@kijinsung.com>
 * 
 * All rights reserved.
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation
 * the right to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class PHPRandom
{
    // Le version, what else?
    
    const VERSION = '1.2.0';
    
    // Remember the list of sources from the last operation.
    
    protected static $_sources = array();
    
    // Get a random number between $min and $max (inclusive).
    
    public static function getInteger($min = 0, $max = 0x7fffffff)
    {
        $bytes_required = min(4, ceil(log($max - $min, 2) / 8) + 1);
        $bytes = self::getBinary($bytes_required);
        $offset = abs(hexdec(bin2hex($bytes)) % ($max - $min + 1));
        return intval($min + $offset);
    }
    
    // Get a random floating-point number between 0 and 1.
    
    public static function getFloat()
    {
        $bytes = self::getBinary(8);
        return abs(hexdec(bin2hex($bytes))) / pow(2, 64);
    }
    
    // Get a random alphanumeric string of the specified length.
    
    public static function getString($length = 32)
    {
        if ($length < 1) return '';
        $bytes_required = ceil($length * 3 / 4);
        $bytes = self::getBinary($bytes_required);
        $replacements = chr(rand(65, 90)) . chr(rand(97, 122)) . strval(rand(0, 9));
        return substr(strtr(base64_encode($bytes), '+/=', $replacements), 0, $length);
    }
    
    // Get a random hexademical string of the specified length.
    
    public static function getHexString($length = 32)
    {
        if ($length < 1) return '';
        $bytes_required = ceil($length / 2);
        $bytes = self::getBinary($bytes_required);
        return substr(bin2hex($bytes), 0, $length);
    }
    
    // Get a random binary string of the specified length.
    
    public static function getBinary($length = 32)
    {
        if ($length < 1) return '';
        
        // There's not much point reading more than 256 bits of entropy from any single source.
        
        $capped_length = min($length, 32);
        
        // As usual, Windows requires special consideration.
        
        $is_windows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        // Variables to store state during entropy collection.
        
        $entropy = array();
        $sources = array();
        $total_strength = 0;
        $required_strength = 5;
        
        // Try getting entropy from various sources that are known to be good.
        
        if (function_exists('openssl_random_pseudo_bytes') && (!$is_windows || version_compare(PHP_VERSION, '5.4', '>=')))
        {
            $entropy[] = openssl_random_pseudo_bytes($capped_length, $crypto_strong);
            $sources[] = 'openssl';
            $total_strength += $crypto_strong ? 3 : 1;
        }
        elseif (function_exists('mcrypt_create_iv') && (!$is_windows || version_compare(PHP_VERSION, '5.3.7', '>=')))
        {
            $entropy[] = mcrypt_create_iv($capped_length, MCRYPT_DEV_URANDOM);
            $sources[] = 'mcrypt_dev_urandom';
            $total_strength += 4;
        }
        elseif ($is_windows && function_exists('mcrypt_create_iv') && defined('MCRYPT_RAND'))
        {
            $entropy[] = mcrypt_create_iv($capped_length, MCRYPT_RAND);
            $sources[] = 'mcrypt_rand';
            $total_strength += 2;
        }
        elseif (!$is_windows && file_exists('/dev/urandom') && is_readable('/dev/urandom'))
        {
            $entropy[] = fread($fp = fopen('/dev/urandom', 'rb'), $capped_length); fclose($fp);
            $sources[] = 'dev_urandom';
            $total_strength += 4;
        }
        
        // Supplement with multiple calls to rand() and mt_rand().
        
        while ($total_strength < $required_strength)
        {
            $rand = '';
            for ($i = 0; $i < $capped_length; $i += 4)
            {
                $rand .= pack('L', rand(0, 0x7fffffff) ^ mt_rand(0, 0x7fffffff));
            }
            $entropy[] = $rand;
            $sources[] = 'mt_rand';
            $total_strength += 1;
        }
        
        // Mix the entropy sources together using SHA-512.
        
        $mixer_content = end($entropy);
        $mixer_output = '';
        
        if (function_exists('hash_hmac') && in_array('sha256', hash_algos()))
        {
            for ($i = 0; $i < $length; $i += 32)
            {
                foreach ($entropy as $item)
                {
                    $mixer_content = hash_hmac('sha256', $item, $mixer_content . $i, true);
                }
                $mixer_output .= $mixer_content;
            }
        }
        else
        {
            for ($i = 0; $i < $length; $i += 20)
            {
                foreach ($entropy as $item)
                {
                    $mixer_content = sha1($item . $mixer_content . $i . microtime(), true);
                }
                $mixer_output .= $mixer_content;
            }
        }
        
        self::$_sources = $sources;
        return substr($mixer_output, 0, $length);
    }
    
    // Get the list of sources from the last operation.
    
    public static function listSources()
    {
        if (!count(self::$_sources)) self::getBinary(4);
        return self::$_sources;
    }
}
