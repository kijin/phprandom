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
    // Remember the list of sources from the last operation.
    
    protected static $_sources = array();
    
    // Get a random number between $min and $max - 1.
    
    public static function getInteger($min = 0, $max = 0x7fffffff)
    {
        $bytes_required = min(4, ceil(log($max - $min, 2) / 8) + 1);
        $bytes = self::getRandomBytes($bytes_required);
        $offset = hexdec(bin2hex($bytes)) % ($max - $min);
        return floor($min + $offset);
    }
    
    // Get a random alphanumeric string of the specified length.
    
    public static function getString($length = 32)
    {
        if ($length < 1) return '';
        $bytes_required = ceil($length * 3 / 4);
        $bytes = self::getRandomBytes($bytes_required);
        $replacements = chr(rand(65, 90)) . chr(rand(97, 122)) . strval(rand(0, 9));
        return substr(strtr(base64_encode($bytes), '+/=', $replacements), 0, $length);
    }
    
    // Get a random hexademical string of the specified length.
    
    public static function getHexString($length = 32)
    {
        if ($length < 1) return '';
        $bytes_required = ceil($length / 2);
        $bytes = self::getRandomBytes($bytes_required);
        return substr(bin2hex($bytes), 0, $length);
    }
    
    // Get a random binary string of the specified length.
    
    public static function getRandomBytes($length = 32)
    {
        if ($length < 1) return '';
        $entropy = array();
        $sources = array();
        $total_strength = 0;
        $required_strength = 5;
        
        // Try getting entropy from the OpenSSL extension.
        
        if ($total_strength < $required_strength && function_exists('openssl_random_pseudo_bytes'))
        {
            $entropy[] = openssl_random_pseudo_bytes($length);
            $sources[] = 'openssl';
            $total_strength += 3;
        }
        
        // Try getting entropy from the mcrypt extension using /dev/urandom.
        
        if ($total_strength < $required_strength && function_exists('mcrypt_create_iv') && defined('MCRYPT_DEV_URANDOM'))
        {
            $entropy[] = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
            $sources[] = 'mcrypt';
            $total_strength += 3;
        }
        
        // Try getting entropy from /dev/urandom directly.
        
        if ($total_strength < $required_strength && !strncmp(strtoupper(PHP_OS), 'LINUX', 5) && @is_readable('/dev/urandom'))
        {
            $entropy[] = fread($fp = fopen('/dev/urandom', 'rb'), $length); fclose($fp);
            $sources[] = 'urandom';
            $total_strength += 3;
        }
        
        // Try getting entropy from CAPICOM.Utilities.getRandom() if we're on Windows.
        
        if ($total_strength < $required_strength && !strncmp(strtoupper(PHP_OS), 'WIN', 3) && class_exists('COM'))
        {
            try
            {
                $capicom = new COM('CAPICOM.Utilities.1');
                $data = $capicom->GetRandom($length, 0);
                if (strlen($data) === $length)
                {
                    $entropy[] = base64_decode($data);
                    $sources[] = 'capicom';
                    $total_strength += 2;
                }
            }
            catch (Exception $e)
            {
                // no-op
            }
        }
        
        // Supplement with multiple calls to rand() and mt_rand().
        
        while ($total_strength < $required_strength)
        {
            $rand = '';
            for ($i = 0; $i < $length; $i += 2)
            {
                $rand .= pack('L', rand(0, 0x7fffffff) ^ mt_rand(0, 0x7fffffff));
            }
            $entropy[] = $rand;
            $sources[] = 'mtrand';
            $total_strength += 1;
        }
        
        // Mix the entropy sources together using SHA-512.
        
        $mixer_content = end($entropy);
        $mixer_output = '';
        
        if (function_exists('hash_hmac') && in_array('sha512', hash_algos()))
        {
            for ($i = 0; $i < $length; $i += 64)
            {
                foreach ($entropy as $item)
                {
                    $mixer_content = hash_hmac('sha512', $item, $mixer_content . $i, true);
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
                    $mixer_content = sha1($item . $mixer_content . $i . uniqud(), true);
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
        if (!count(self::$_sources)) self::getRandomBytes(4);
        return self::$_sources;
    }
}
