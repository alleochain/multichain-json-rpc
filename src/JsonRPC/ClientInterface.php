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

interface ClientInterface
{
    public const VERSION = '1.0';

    public const PROHIBITED_METHODS = ['help', 'stop'];

    /**
     * @param mixed $id
     */
    public function setId($id): void;

    /**
     * @param string $method
     * @param mixed[] $params
     * @return mixed[]
     */
    public function exec(string $method, array $params = []): array;
}
