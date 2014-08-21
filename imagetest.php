<?php

include 'phprandom.php';

$img = imageCreateTrueColor(256, 256);

$colors = array();
for ($i = 0; $i < 256; $i++)
{
    $colors[$i] = imageColorAllocate($img, $i, $i, $i);
}

for ($i = 0; $i < 256; $i++)
{
    $random = PHPRandom::getBinary(256);
    
    for ($j = 0; $j < 256; $j++)
    {
        $value = ord(substr($random, $j, 1));
        imageSetPixel($img, $i, $j, $colors[$value]);
    }
}

header('Content-Type: image/png');
imagePNG($img);
imageDestroy($img);
