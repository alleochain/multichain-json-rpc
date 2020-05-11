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

namespace AlleoChain\Multichain\JsonRPC;

use GuzzleHttp\Client as HttpClient;

/**
 * Multichain JsonRPC Client class allows to interact with a JSON RPC blockchain APIs
 * by sending the commands and getting back response arrays of data.
 *
 * This implementation also allows to work with MultiChain (https://www.multichain.com/)
 * blockchain platform by providing chain name during the initialization of the instance.
 *
 * It is also possible to use this client to work with traditional blockchains API like
 * BitCoin or LiteCoin
 *
 * Example usage:
 *
 * # Create new instance
 * $instance = new AlleoChain\Multichain\JsonRPC\Client([
 *   'url'  => 'http://127.0.0.1:7208',
 *   'user'  => 'rpcuser',
 *   'pass'  => 'rpcpass',
 *   'chain' => 'test'
 * ]);
 *
 * # Get blockchain info
 * print_r($instance->getinfo());
 *
 * # For MultiChain streams
 * print_r($instance->liststreamitems('test_stream'))
 *
 * @method array getinfo()
 * @method array getblockchainparams(array $params)
 * @method array listaccounts()
 * @method array getnewaddress(array $params)
 * @method array getaccountaddress(array $params)
 * @method array getaddressesbyaccount(array $params)
 * @method array getbalance(array $params)
 * @method array validateaddress(array $params)
 */
final class Client implements ClientInterface
{
    /**
     * @var mixed[] $config Client configuration
     */
    protected $config = [
        'url' => null,
        'user' => null,
        'pass' => null,
    ];

    /**
     * @var HttpClientInterface
     */
    protected $httpClient;

    /**
     * @var array<int, string>
     */
    private $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Connection: close',
    ];

    /**
     * @var int|string|null
     */
    private $id = null;

    /**
     * @param array<string, string> $config
     */
    public function __construct(array $config, HttpClientInterface $httpClient = null)
    {
        $this->validateConfig($config);

        $this->config = array_merge($this->config, $config);

        if (array_key_exists('user', $this->config) && array_key_exists('pass', $this->config)) {
            $auth = base64_encode($this->config['user'] . ':' . $this->config['pass']);
            $this->headers[] = 'Authorization: Basic ' . $auth;
        }

        if (null === $httpClient) {
            $httpClient = new Curl($this->config['url']);
        }

        $this->httpClient = $httpClient;
    }

    /**
     * @param array<string, string> $config
     * @throws InvalidArgumentException
     */
    private function validateConfig(array $config): void
    {
        if (! array_key_exists('url', $config)) {
            throw new InvalidArgumentException('Parameter "url" is required');
        }

        if (! is_string($config['url']) || '' === trim($config['url'])) {
            throw new InvalidArgumentException('Parameter "url" must be a non-empty string');
        }
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function exec(string $method, array $params = []): array
    {
        if ('' === trim($method)) {
            throw new PayloadException('Method name must be a non-empty string');
        }

        if (in_array($method, self::PROHIBITED_METHODS, true)) {
            throw new PayloadException(sprintf('"%s" method is prohibited', $method));
        }

        $this->httpClient->setOption(CURLOPT_POSTFIELDS, $this->payload($method, $params));
        $this->httpClient->setOption(CURLOPT_HTTPHEADER, $this->headers);
        $this->httpClient->setOption(CURLOPT_RETURNTRANSFER, true);

        /** @var string|false */
        $result = $this->httpClient->execute();

        if (false === $result) {
            throw new NetworkException(
                sprintf('%d: %s', $this->httpClient->errorCode(), $this->httpClient->errorMessage()),
                -32000
            );
        }

        $result = json_decode($result, true);
        $result['id'] = $this->id;

        return $result;
    }

    /**
     * @param array<int, mixed> $params
     */
    private function payload(string $method, array $params): string
    {
        $payload = [
            'jsonrpc' => self::VERSION,
            'method' => $method,
            'params' => $params,
            'id'  => $this->id
        ];

        try {
            $result = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new PayloadException($e->getMessage(), 0, $e);
        }

        return $result;
    }

    /**
     * Magic __call that will allow to call API method directly by their names
     *
     * @param mixed[] $args
     * @return mixed[]
     */
    public function __call(string $method, array $args): array
    {
        return $this->exec($method, $args);
    }
}
