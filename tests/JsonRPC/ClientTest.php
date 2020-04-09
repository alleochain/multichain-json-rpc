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

use AlleoChain\Multichain\JsonRPC\Client;
use AlleoChain\Multichain\JsonRPC\HttpClientInterface;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    private const FIXTURES_PATH = __DIR__ . DIRECTORY_SEPARATOR . '..' .
        DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var HttpClientInterface&\PHPUnit\Framework\MockObject\Stub
     */
    private $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = $this->createStub(HttpClientInterface::class);
        $this->client = new Client(['url' => 'http://localhost:8000', 'chain' => 'test'], $this->httpClient);
    }

    protected function tearDown(): void
    {
        unset($this->httpClient);
        unset($this->client);
    }

    public function testValidResponse(): void
    {
        $id = 123;
        $data = (string)file_get_contents(self::FIXTURES_PATH . 'getinfo.json');

        $this->client->setId($id);

        $this->httpClient->method('execute')
             ->willReturn($data);

        $expected = json_decode($data, true);
        $expected['id'] = $id;

        $this->assertSame($expected, $this->client->getinfo());
    }
    public function testInstantiationShouldThrowExceptionWithoutUrl(): void
    {
        $this->expectException(\AlleoChain\Multichain\JsonRPC\InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Parameter "url" is required');

        $client = new Client([]);
    }

    public function testInstantiationShouldThrowExceptionWithEmptyUrl(): void
    {
        $this->expectException(\AlleoChain\Multichain\JsonRPC\InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Parameter "url" must be a non-empty string');

        $client = new Client(['url' => '']);
    }

    public function testInstantiationWithoutClient(): void
    {
        $client = new Client(['url' => 'http://localhost:8000']);

        $this->assertInstanceOf(Client::class, $client);
    }

    public function testShouldThrowExceptionWithEmptyMethodName(): void
    {
        $this->expectException(\AlleoChain\Multichain\JsonRPC\PayloadException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Method name must be a non-empty string');

        $this->client->exec('');
    }

    public function testShouldThrowExceptionWithJsonUnencodedPayload(): void
    {
        $this->expectException(\AlleoChain\Multichain\JsonRPC\PayloadException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Malformed UTF-8 characters, possibly incorrectly encoded');

        $this->client->getblockchainparams([utf8_decode('ü')]);
    }

    /**
     * @dataProvider forbiddenMethodsDataProvider
     * @param string $method Method name
     * @return void
     */
    public function testShouldThrowExceptionWithForbiddenMethods(string $method): void
    {
        $this->expectException(\AlleoChain\Multichain\JsonRPC\PayloadException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage(sprintf('"%s" method is prohibited', $method));

        $this->client->{$method}();
    }

    /**
     * @return array<array<string>>
     */
    public function forbiddenMethodsDataProvider(): array
    {
        return [
            ['help'],
            ['stop'],
        ];
    }

    public function testShouldThrowExecptionWithFalseResponse(): void
    {
        $this->expectException(\AlleoChain\Multichain\JsonRPC\NetworkException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('0: ');

        $this->httpClient->method('execute')
            ->willReturn(false);

        $this->client->getinfo();
    }
}
