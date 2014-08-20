<?php

include 'phprandom.php';

class PHPRandom_UnitTest extends PHPUnit_Framework_TestCase
{
    public function test_integer_without_range()
    {
        $count = 18000;
        $tolerance = 0.8;
        $buckets = array_fill(0, 256, 0);
        for ($i = 0; $i < $count; $i++)
        {
            $number = PHPRandom::getInteger();
            $bucket_index = $number >> 23;
            $buckets[$bucket_index]++;
        }
        for ($i = 0; $i < 256; $i++)
        {
            $this->assertGreaterThan(($count / 256) * (1 - $tolerance), $buckets[$i]);
            $this->assertLessThan(($count / 256) * (1 + $tolerance), $buckets[$i]);
        }
    }
    
    public function test_integer_with_range()
    {
        $count = 12000;
        $tolerance = 0.6;
        $buckets = array_fill(100, 101, 0);
        for ($i = 0; $i < $count; $i++)
        {
            $number = PHPRandom::getInteger(100, 200);
            $buckets[$number]++;
        }
        for ($i = 100; $i < 200; $i++)
        {
            $this->assertGreaterThan(($count / 100) * (1 - $tolerance), $buckets[$i]);
            $this->assertLessThan(($count / 100) * (1 + $tolerance), $buckets[$i]);
        }
        $this->assertEquals($buckets[200], 0);
    }
    
    public function test_string()
    {
        for ($i = 1; $i <= 600; $i++)
        {
            $str = PHPRandom::getString($i);
            $this->assertEquals(strlen($str), $i);
            $this->assertRegExp('/^[0-9a-zA-Z]+$/', $str);
        }
    }
    
    public function test_hex_string()
    {
        for ($i = 1; $i <= 600; $i++)
        {
            $str = PHPRandom::getHexString($i);
            $this->assertEquals(strlen($str), $i);
            $this->assertRegExp('/^[0-9a-f]+$/', $str);
        }
    }
    
    public function test_binary()
    {
        for ($i = 1; $i <= 200; $i++)
        {
            $str = PHPRandom::getRandomBytes($i);
            $this->assertEquals(strlen($str), $i);
        }
    }
    
    public function test_list_sources()
    {
        $list = PHPRandom::listSources();
        $this->assertGreaterThan(0, count($list));
    }
}
