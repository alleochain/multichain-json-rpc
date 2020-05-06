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

class Curl implements HttpClientInterface
{
    /**
     * @var resource
     */
    private $handle;

    public function __construct(string $url)
    {
        $handle = curl_init($url);
        if (false === $handle) {
            throw new NetworkException('Failed to instantiate resource');
        }

        $this->handle = $handle;
    }

    public function setOption(int $option, $value): bool
    {
        return curl_setopt($this->handle, $option, $value);
    }

    public function execute()
    {
        return curl_exec($this->handle);
    }

    public function errorCode(): int
    {
        return curl_errno($this->handle);
    }

    public function errorMessage(): string
    {
        return curl_error($this->handle);
    }

    public function __destruct()
    {
        curl_close($this->handle);
    }
}
