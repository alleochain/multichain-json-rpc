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
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    private const FIXTURES_PATH = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR;

    /**
     * @var \AlleoChain\Multichain\JsonRPC\Client
     */
    private $client;

    /**
     * @var \GuzzleHttp\Handler\MockHandler
     */
    private $mockHandler;

    protected function setUp(): void
    {
        $this->mockHandler = new MockHandler();

        $httpClient = new HttpClient(['handler' => $this->mockHandler]);

        $this->client = new Client(['url' => 'http://localhost:8000', 'chain' => 'test'], $httpClient);
    }

    protected function tearDown(): void
    {
        unset($this->client);
        unset($this->mockHandler);
    }

    public function testInstantiationShouldThrowExceptionWithoutUrl(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Missing required config param \'url\'');

        $client = new Client();
    }

    public function testInstantiationWithoutClient(): void
    {
        $client = new Client(['url' => 'http://localhost:8000']);

        $this->assertInstanceOf(Client::class, $client);
    }

    public function testShouldThrowExceptionWithEmptyMethodName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Method name must be a non empty string');

        $this->client->{''}();
    }

    /**
     * @dataProvider validMethodsDataProvider
     * @param string $method Method name
     * @return void
     */
    public function testValidMethods(string $method): void
    {
        $data = (string)file_get_contents(self::FIXTURES_PATH . $method . '.json');

        $this->mockHandler->append(new Response(200, [], $data));

        $this->assertSame(json_decode($data, true), $this->client->{$method}());
    }

    /**
     * @return array<array<string>>
     */
    public function validMethodsDataProvider(): array
    {
        return [
            ['getinfo'],
        ];
    }

    /**
     * @dataProvider forbiddenMethodsDataProvider
     * @param string $method Method name
     * @return void
     */
    public function testShouldThrowExceptionWithForbiddenMethods(string $method): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage(sprintf('Method \'%s\' is not allowed by API', $method));

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

    public function testRequestException(): void
    {
        $errorMessage = 'Error Communicating with Server';

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage($errorMessage);

        $this->mockHandler->append(new \InvalidArgumentException($errorMessage));

       $this->client->getinfo();
    }

    public function testRequestExceptionWithResponse(): void
    {
        $errorMessage = '{"message": "Error Communicating with Server"}';

        $this->mockHandler->append(new RequestException(
            $errorMessage,
            new Request('GET', 'test'),
            new Response(500, [], $errorMessage)
        ));

       $this->assertSame(json_decode($errorMessage, true), $this->client->getinfo());
    }
}
