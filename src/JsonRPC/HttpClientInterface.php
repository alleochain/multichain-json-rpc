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

interface HttpClientInterface
{
    /**
     * @param mixed $value
     */
    public function setOption(int $option, $value): bool;

    /**
     * @return bool|string
     */
    public function execute();

    public function errorCode(): int;

    public function errorMessage(): string;
}
