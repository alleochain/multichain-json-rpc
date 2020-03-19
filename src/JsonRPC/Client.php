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
 */
class Client
{
    /**
     * @var mixed[] $config Client configuration
     */
    protected $config = [
        'url' => null,
        'user' => null,
        'pass' => null,
        'chain' => null
    ];

    /**
     * We don't really need help and stop is critical
     *
     * @var string[] $prohibitedMethods List of RPC calls that are not allowed
     */
    protected $prohibitedMethods = ['help', 'stop'];

    /**
     * @var \GuzzleHttp\Client $httpClient Instance of HTTP Client
     */
    protected $httpClient;

    /**
     * Class constructor
     *
     * @param mixed[] $config Initial config
     * @param \GuzzleHttp\Client|null $httpClient HTTP Client
     */
    public function __construct(array $config = [], HttpClient $httpClient = null)
    {
        if (empty($config['url'])) {
            throw new \RuntimeException("Missing required config param 'url'");
        }

        $this->config = array_merge($this->config, $config);

        if (null === $httpClient) {
            $httpClient = new HttpClient([
                'auth' => ((!empty($config['user']) && !empty($config['pass'])) ? [$config['user'], $config['pass']] : [])
            ]);
        }

        $this->httpClient = $httpClient;
    }

    /**
     * Exec API method with given params
     *
     * @param string $method API method name
     * @param mixed[] $params API method call params
     *
     * @return mixed[] parsed response body
     */
    public function exec(string $method, array $params = []): array
    {
        // non-empty method required
        if (empty($method)) {
            throw new \InvalidArgumentException("Method name must be a non empty string");
        }
        // prevent calling prohibited method
        if (in_array($method, $this->prohibitedMethods, true)) {
            throw new \RuntimeException("Method '$method' is not allowed by API");
        }

        // make a payload
        $payload = [
            'jsonrpc'       => "1.0",
            'method'        => $method,
            'params'        => $params,
            'id'            => time()
        ];

        // add a chain name if we have one
        if (!empty($this->config['chain'])) {
            $payload['chain_name'] = $this->config['chain'];
        }

        try {
            // try to make a request to API
            $response = $this->httpClient->post($this->config['url'], ['json' => $payload]);
        } catch (\Exception $e) {
            if (is_callable([$e, 'getResponse'])) {
                // Try to parse JSON from the response with error
                $body = json_decode($e->getResponse()->getBody(true), true);

                // just return JSON if managed to parse correctly
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $body;
                }
            }

            // otherwise throw the exception we have as it is probably Client/configuration
            // issue of HttpClient, not the server side
            throw $e;
        }

        // under normal execution return decoded JSON body as an array
        return json_decode((string)$response->getBody(), true);
    }

    /**
     * Magic __call that will allow to call API method directly
     * by their names
     *
     * @param string $method Method name that was called
     * @param mixed[] $args Arguments passed to the method
     *
     * @return mixed[] parsed response body
     */
    public function __call(string $method, array $args): array
    {
        return $this->exec($method, $args);
    }
}
