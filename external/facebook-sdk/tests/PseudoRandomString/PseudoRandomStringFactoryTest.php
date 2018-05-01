<?php
/**
 * Copyright 2017 Facebook, Inc.
 *
 * You are hereby granted a non-exclusive, worldwide, royalty-free license to
 * use, copy, modify, and distribute this software in source code or binary
 * form for use in connection with the web services and APIs provided by
 * Facebook.
 *
 * As with any software that integrates with the Facebook platform, your use
 * of this software is subject to the Facebook Developer Principles and
 * Policies [http://developers.facebook.com/policy/]. This copyright notice
 * shall be included in all copies or substantial portions of the software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 */
namespace Facebook\Tests\PseudoRandomString;

use Facebook\PseudoRandomString\PseudoRandomStringGeneratorFactory;
use PHPUnit_Framework_TestCase;

class PseudoRandomStringFactoryTest extends PHPUnit_Framework_TestCase
{
    const COMMON_NAMESPACE = 'Facebook\PseudoRandomString\\';
    const COMMON_INTERFACE = 'Facebook\PseudoRandomString\PseudoRandomStringGeneratorInterface';

    /**
     * @param mixed  $handler
     * @param string $expected
     *
     * @dataProvider csprngProvider
     */
    public function testCsprng($handler, $expected)
    {
        $pseudoRandomStringGenerator = PseudoRandomStringGeneratorFactory::createPseudoRandomStringGenerator($handler);

        $this->assertInstanceOf(self::COMMON_INTERFACE, $pseudoRandomStringGenerator);
        $this->assertInstanceOf($expected, $pseudoRandomStringGenerator);
    }

    /**
     * @return array
     */
    public function csprngProvider()
    {
        $providers = [
          [null, self::COMMON_INTERFACE],
        ];
        if (function_exists('random_bytes')) {
            $providers[] = ['random_bytes', self::COMMON_NAMESPACE . 'RandomBytesPseudoRandomStringGenerator'];
        }
        if (function_exists('mcrypt_create_iv')) {
            $providers[] = ['mcrypt', self::COMMON_NAMESPACE . 'McryptPseudoRandomStringGenerator'];
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            $providers[] = ['openssl', self::COMMON_NAMESPACE . 'OpenSslPseudoRandomStringGenerator'];
        }
        if (!ini_get('open_basedir') && is_readable('/dev/urandom')) {
            $providers[] = ['urandom', self::COMMON_NAMESPACE . 'UrandomPseudoRandomStringGenerator'];
        }

        return $providers;
    }
}
