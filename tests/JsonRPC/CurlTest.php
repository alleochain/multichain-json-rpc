<?php

/**
 * Copyright (c) AlleoChain Ltd. (https://alleochain.com)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) AlleoChain Ltd. (https://alleochain.com)
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace AlleoChain\Multichain\Tests\JsonRPC;

use AlleoChain\Multichain\JsonRPC\Curl;
use PHPUnit\Framework\TestCase;

final class CurlTest extends TestCase
{
    /**
     * @var Curl
     */
    private $curl;

    protected function setUp(): void
    {
        $this->curl = new Curl('');
    }

    protected function tearDown(): void
    {
        unset($this->curl);
    }

    public function testExecute(): void
    {
        $this->curl->setOption(CURLOPT_RETURNTRANSFER, true);
        $this->assertFalse($this->curl->execute());
    }

    public function testSetOption(): void
    {
        $this->assertTrue($this->curl->setOption(CURLOPT_RETURNTRANSFER, true));
    }

    public function testErrorCode(): void
    {
        $this->assertSame(0, $this->curl->errorCode());
    }

    public function testErrorMessage(): void
    {
        $this->assertSame('', $this->curl->errorMessage());
    }
}
