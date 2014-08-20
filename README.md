PHPRandom
=========

**_Just another random number/string generator for PHP 5_**

This library is similar to ircmaxell/RandomLib, except it is compatible
with PHP 5.0 and above, and comes with a much simpler interface.
Seriously, PHP ain't Java. Why do we need a dozen files and classes
inheriting one another just to get some random bytes? :p

How to Install
--------------

Traditional method:

    include 'phprandom.php';

Composer:

    "require": {
        "kijin/phprandom": "dev-master"
    }

How to Use
----------

Get a random integer between two points:

    $random = PHPRandom::getInteger($min, $max);

Get a random alphanumeric string (0-9, a-z, A-Z):

    $random = PHPRandom::getString($length);

Get a random hexademical string (0-9, a-f):

    $random = PHPRandom::getHexString($length);

Get a random binary string:

    $random = PHPRandom::getBinary($length);

Find out where your precious entropy comes from:

    $sources = PHPRandom::listSources();

Configuration
-------------

PHPRandom requires no configuration.

You don't have to choose whether you want low-quality or high-quality entropy.
You always get the highest quality of entropy that your system supports.
Since the marginal cost of asking for high-quality entropy is negligible
in most cases, there is rarely any need to ask for low-quality entropy.

Weaker sources of entropy are automatically mixed with other sources
using the best mixing algorithm that your system supports.

Supported sources, in order of preference:

  - `openssl_random_pseudo_bytes()`
  - `mcrypt_create_iv()`
  - `/dev/urandom` (Linux/Unix only)
  - `CAPICOM.Utilities.getRandom()` (Windows only)
  - `rand()` xor `mt_rand()`

Supported mixers, in order of preference:

  - `hash_hmac()` with SHA-512
  - `sha1()`

License
-------

PHPRandom is free software released under the MIT license.
