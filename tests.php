<?php

include 'phprandom.php';

class PHPRandom_UnitTest extends PHPUnit_Framework_TestCase
{
    public function test_integer_without_range()
    {
        $count = 10000;
        $buckets = array_fill(0, 256, 0);
        for ($i = 0; $i < $count; $i++)
        {
            $number = PHPRandom::getInteger();
            $bucket_index = $number >> 23;
            $this->assertEquals(true, isset($buckets[$bucket_index]));
            $buckets[$bucket_index]++;
        }
        
        $distribution = 0;
        for ($i = 0; $i < 256; $i++)
        {
            if ($buckets[$i] > ($count / 256) * 0.5) $distribution++;
            if ($buckets[$i] < ($count / 256) * 1.5) $distribution++;
        }
        $this->assertGreaterThan(256, $distribution);
    }
    
    public function test_integer_with_range()
    {
        $count = 10000;
        $buckets = array_fill(100, 101, 0);
        for ($i = 0; $i < $count; $i++)
        {
            $number = PHPRandom::getInteger(100, 200);
            $this->assertEquals(true, isset($buckets[$number]));
            $buckets[$number]++;
        }
        
        $distribution = 0;
        for ($i = 100; $i < 200; $i++)
        {
            if ($buckets[$i] > ($count / 100) * 0.5) $distribution++;
            if ($buckets[$i] < ($count / 100) * 1.5) $distribution++;
        }
        $this->assertGreaterThan(100, $distribution);
    }
    
    public function test_float()
    {
        $count = 10000;
        $buckets = array_fill(0, 100, 0);
        for ($i = 0; $i < $count; $i++)
        {
            $number = PHPRandom::getFloat();
            $number = floor($number * 100);
            $this->assertEquals(true, isset($buckets[$number]));
            $buckets[$number]++;
        }
        
        $distribution = 0;
        for ($i = 0; $i < 100; $i++)
        {
            if ($buckets[$i] > ($count / 100) * 0.5) $distribution++;
            if ($buckets[$i] < ($count / 100) * 1.5) $distribution++;
        }
        $this->assertGreaterThan(100, $distribution);
    }
    
    public function test_string()
    {
        $count = 2000;
        $buckets = array_combine(str_split('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), array_fill(0, 62, 0));
        for ($i = 1; $i <= $count; $i++)
        {
            $len = mt_rand(2, 256);
            $str = PHPRandom::getString($len);
            $this->assertEquals($len, strlen($str));
            $this->assertEquals(true, isset($buckets[$str[0]]));
            $buckets[$str[0]]++;
        }
        
        $distribution = 0;
        foreach ($buckets as $key => $value)
        {
            if ($value > ($count / 62) * 0.5) $distribution++;
            if ($value < ($count / 62) * 1.5) $distribution++;
        }
        $this->assertGreaterThan(62, $distribution);
    }
    
    public function test_hex_string()
    {
        $count = 2000;
        $tolerance = 0.6;
        $buckets = array_combine(str_split('0123456789abcdef'), array_fill(0, 16, 0));
        for ($i = 1; $i <= $count; $i++)
        {
            $len = mt_rand(2, 256);
            $str = PHPRandom::getHexString($len);
            $this->assertEquals($len, strlen($str));
            $this->assertEquals(true, isset($buckets[$str[0]]));
            $buckets[$str[0]]++;
        }
        
        $distribution = 0;
        foreach ($buckets as $key => $value)
        {
            if ($value > ($count / 16) * 0.5) $distribution++;
            if ($value < ($count / 16) * 1.5) $distribution++;
        }
        $this->assertGreaterThan(16, $distribution);
    }
    
    public function test_binary()
    {
        $count = 2000;
        $tolerance = 0.9;
        $buckets = array_fill(0, 256, 0);
        for ($i = 1; $i <= $count; $i++)
        {
            $len = mt_rand(2, 256);
            $str = PHPRandom::getBinary($len);
            $this->assertEquals($len, strlen($str));
            $buckets[ord($str[0])]++;
        }
        
        $distribution = 0;
        foreach ($buckets as $key => $value)
        {
            if ($value > ($count / 256) * 0.5) $distribution++;
            if ($value < ($count / 256) * 1.5) $distribution++;
        }
        $this->assertGreaterThan(256, $distribution);
    }
    
    public function test_list_sources()
    {
        $list = PHPRandom::listSources();
        $this->assertGreaterThan(0, count($list));
    }
}
