<?php
/**
 * Copyright (c) Alexander Mamchenkov. (http://alex.mamchenkov.net)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Alexander Mamchenkov. (http://alex.mamchenkov.net)
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace AlexMamchenkov\Multichain\JsonRPC;

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
 * $instance = new AlexMamchenkov\Multichain\JsonRPC\Client([
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
     * @var array $config Client configuration
     */
    protected $config = [
        'url' => NULL,
        'user' => NULL,
        'pass' => NULL,
        'chain' => NULL
    ];

    /**
     * @var array $prohibitedMethods List of RPC calls that are not allowed
     *
     * We don't really need help and stop is critical
     */
    protected $prohibitedMethods = ['help', 'stop'];

	/**
	 * GuzzleHttp\Client $httpClient Instance of HTTP Client
	 */
	protected $httpClient = null;

    /**
     * Class constructor
     *
     * @params array $config Initial config
     */
    public function __construct($config = [])
	{
		if (empty($config['url'])) {
			throw new \RuntimeException("Missing required config param 'url'");
		}

		$this->config = array_merge($this->config, $config);

		$this->httpClient = new HttpClient([
			'auth' => ((!empty($config['user']) && !empty($config['pass'])) ? [$config['user'], $config['pass']] : [])
		]);
    }

    /**
     * Exec API method with given params
     *
     * @params string $method API method name
     * @params array $params API method call params
     *
     * @return array parsed response body
     */
    public function exec($method, $params = [])
    {
        // non-empty method required
        if (empty($method) || !is_string($method)) {
            throw new \InvalidArgumentException("Method name must be a non empty string");
        }
        // params must be an array
        if (!is_array($params)) {
            throw new \InvalidArgumentException("Params must be an array");
        }
        // prevent calling prohibited method
        if (in_array($method, $this->prohibitedMethods)) {
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

            // In some cases (like 404, 500), server will return JSON with error messages,
            // but GuzzleHttp will wrap it into their own error message.

            // try to see if we actually managed to do a request and got response with
            // some JSON in it
            if (preg_match('/^.*?({.*})$/s', trim($e->getMessage()), $matches)) {

                // just return decoded JSON part which should have error
                // message in it
                return json_decode($matches[1], true);
            }

            // otherwise throw the exception we have as it is probably Client/configuration
            // issue of HttpClient, not the server side
            throw $e;
        }

        // under normal execution return decoded JSON body as an array
		return json_decode((string) $response->getBody(), true);
    }

    /**
     * Magic __call that will allow to call API method directly
     * by their names
     *
     * @param string $method Method name that was called
     * @param array $args Arguments passed to the method
     *
     * @return array parsed response body
     */
    function __call($method, $args)
    {
        return $this->exec($method, $args);
    }
}
