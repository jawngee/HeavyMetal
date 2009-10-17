<?php
/**
 * Implementation of the Hashed Message Authentication Code algorithm with
 * support for a wide range of hashing algorithms available using either of
 * the "hash" or "mhash" extensions, or the native md5() and sha1() functions.
 *
 * PHP version 5
 *
 * LICENSE:
 * 
 * Copyright (c) 2005-2007, Pádraic Brady <padraic.brady@yahoo.com>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the 
 *      documentation and/or other materials provided with the distribution.
 *    * The name of the author may not be used to endorse or promote products 
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO,
 * THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @category    Encryption
 * @package     Crypt_HMAC2
 * @author      Pádraic Brady <padraic.brady@yahoo.com>
 * @license     http://opensource.org/licenses/bsd-license.php New BSD License
 * @version     $Id: HMAC2.php,v 1.1 2007/09/29 12:12:09 padraic Exp $
 * @link        http://
 */

/**
 * Crypt_HMAC2 class
 *
 * Example usage:
 *      $key = str_repeat("\xaa", 20); // insecure key only for example
 *      $data = "Hello World!";
 *      $hmac = new Crypt_HMAC2($key, 'SHA256');
 *      $hmacHash = $hmac->hash($data);
 *      
 *      Supported hashing algorithms are limited by your PHP version access
 *      to the hash, mhash extensions and supported native functions like
 *      md5() and sha1().
 *
 *      To obtain raw binary output, set the optional second parameter of
 *      Crypt_HMAC2::hash() to Crypt_HMAC2::BINARY.
 *
 *      $hmacRawHash = $hmac->hash($data, Crypt_HMAC2::BINARY);
 * 
 * @category   Encryption
 * @package    Crypt_HMAC2
 * @author     Pádraic Brady <padraic.brady@yahoo.com>
 * @copyright  2005-2007 Pádraic Brady
 * @license    http://opensource.org/licenses/bsd-license.php New BSD License
 * @link       http://
 * @version    @package_version@
 * @access     public
 */
class Crypt_HMAC2
{

    /**
     * The key to use for the hash
     *
     * @var string
     */
    protected $_key = null;

    /**
     * pack() format to be used for current hashing method
     *
     * @var string
     */
    protected $_packFormat = null;

    /**
     * Hashing algorithm; can be the md5/sha1 functions or any algorithm name
     * listed in the output of PHP 5.1.2+ hash_algos().
     *
     * @var string
     */
    protected $_hashAlgorithm = 'md5';

    /**
     * Supported direct hashing functions in PHP
     *
     * @var array
     */
    protected $_supportedHashNativeFunctions = array(
        'md5',
        'sha1',
    );

    /**
     * List of hash pack formats for each hashing algorithm supported.
     * Only required when hash or mhash are not available, and we are
     * using either md5() or sha1().
     *
     * @var array
     */
    protected $_hashPackFormats = array(
        'md5'        => 'H32',
        'sha1'       => 'H40'
    );

    /**
     * List of algorithms supported my mhash()
     *
     * @var array
     */
    protected $_supportedMhashAlgorithms = array('adler32',' crc32', 'crc32b', 'gost', 
            'haval128', 'haval160', 'haval192', 'haval256', 'md4', 'md5', 'ripemd160', 
            'sha1', 'sha256', 'tiger', 'tiger128', 'tiger160');

    /**
     * Constants representing the output mode of the hash algorithm
     */
    const STRING = 'string';
    const BINARY = 'binary';

    /**
     * Constructor; optionally set Key and Hash at this point
     *
     * @param string $key
     * @param string $hash
     */
    public function __construct($key = null, $hash = null)
    {
        if (!is_null($key)) {
            $this->setKey($key);
        }
        if (!is_null($hash)) {
            $this->setHashAlgorithm($hash);
        }
    }

    /**
     * Set the key to use when hashing
     *
     * @param string $key
     * @return Crypt_HMAC2
     */
    public function setKey($key)
    {
        if (!isset($key) || empty($key)) {
            require_once 'Crypt/HMAC2/Exception.php';
            throw new Crypt_HMAC2_Exception('provided key is null or empty');
        }
        $this->_key = $key;
        return $this;
    }

    /**
     * Getter to return the currently set key
     *
     * @return string
     */
    public function getKey()
    {
        if (is_null($this->_key)) {
            require_once 'Exception.php';
            throw new Crypt_HMAC2_Exception('key has not yet been set');
        }
        return $this->_key;
    }

    /**
     * Setter for the hash method. Supports md5() and sha1() functions, and if
     * available the hashing algorithms supported by the hash() PHP5 function or
     * the mhash extension.
     *
     * Since they are so many varied HMAC methods in PHP these days this method
     * does a lot of checking to figure out what's available and not.
     *
     * @param string $hash
     * @return Crypt_HMAC2
     */
    public function setHashAlgorithm($hash)
    {
        if (!isset($hash) || empty($hash)) {
            require_once 'Exception.php';
            throw new Crypt_HMAC2_Exception('provided hash string is null or empty');
        }
        $hash = strtolower($hash);
        $hashSupported = false;
        if (function_exists('hash_algos') && in_array($hash, hash_algos())) {
            $hashSupported = true;
        }
        if ($hashSupported === false && function_exists('mhash') && in_array($hash, $this->_supportedMhashAlgorithms)) {
            $hashSupported = true;
        }
        if ($hashSupported === false && in_array($hash, $this->_supportedHashNativeFunctions) && in_array($hash, array_keys($this->_hashPackFormats))) {
            $this->_packFormat = $this->_hashPackFormats[$hash];
            $hashSupported = true;
        }
        if ($hashSupported === false) {
            require_once 'Exception.php';
            throw new Crypt_HMAC2_Exception('hash algorithm provided is not supported on this PHP instance; please enable the hash or mhash extensions');
        }
        $this->_hashAlgorithm = $hash;
        return $this;
    }

    /**
     * Return the current hashing algorithm
     *
     * @return string
     */
    public function getHashAlgorithm()
    {
        return $this->_hashAlgorithm;
    }

    /**
     * Perform HMAC and return the keyed data
     *
     * @param string $data
     * @param bool $internal Option to not use hash() functions for testing
     * @return string
     */
    public function hash($data, $output = self::STRING, $internal = false)
    {
        if (function_exists('hash_hmac') && $internal === false) {
            if ($output == self::BINARY) {
                return hash_hmac($this->getHashAlgorithm(), $data, $this->getKey(), 1);
            }
            return hash_hmac($this->getHashAlgorithm(), $data, $this->getKey());
        }

        if (function_exists('mhash') && $internal === false) {
            if ($output == self::BINARY) {
                return mhash($this->_getMhashDefinition($this->getHashAlgorithm()), $data, $this->getKey());
            }
            $bin = mhash($this->_getMhashDefinition($this->getHashAlgorithm()), $data, $this->getKey());
            return bin2hex($bin);
        }

        // last ditch effort for MD5 and SHA1 only
        $key = $this->getKey();
        $hash = $this->getHashAlgorithm();

        if (strlen($key) < 64) {
            $key = str_pad($key, 64, chr(0));
        } elseif (strlen($key) > 64) {
           $key =  pack($this->_packFormat, $this->_digest($hash, $key, $output));
        }
        $padInner = (substr($key, 0, 64) ^ str_repeat(chr(0x36), 64));
        $padOuter = (substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64));
        
        return $this->_digest($hash, $padOuter . pack($this->_packFormat, $this->_digest($hash, $padInner . $data, $output)), $output);
    }

    /**
     * Method of working around the inability to use mhash constants; this
     * will locate the Constant value of any support Hashing algorithm named
     * in the string parameter.
     *
     * @param string $hashAlgorithm
     * @return integer
     */
    protected function _getMhashDefinition($hashAlgorithm)
    {
        for ($i = 0; $i <= mhash_count(); $i++) {
            $types[mhash_get_hash_name($i)] = $i;
        }
        return $types[strtoupper($hashAlgorithm)];
    }

    /**
     * Digest method when using native functions which allows the selection
     * of raw binary output.
     *
     * @param string $hash
     * @param string $key
     * @param string $mode
     * @return string
     */
    protected function _digest($hash, $key, $mode)
    {
        if ($mode == self::BINARY) {
            return $hash($key, true);
        }
        return $hash($key);
    }

}