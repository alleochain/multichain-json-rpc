multichain-jsonrpc
==================

About
-----

Multichain JsonRPC Client class allows to interact with a JSON RPC blockchain APIs
by sending the commands and getting back response arrays of data.

This implementation also allows to work with MultiChain (https://www.multichain.com/)
blockchain platform by providing chain name during the initialization of the instance.

As none of the blockchain APIs are directly defined in the Client, but instead a proxy
via magic method is used, it should be possible to use this client with more or less
any blockchain JSON-RPC service.

Developed by Alexander Mamchenkov (http://alex.mamchenkov.net)

Usage Example
-------------

```php
# Create new instance
$instance = new AlexMamchenkov\Multichain\JsonRPC\Client([
    'url'  => 'http://127.0.0.1:7208',
    'user'  => 'rpcuser',
    'pass'  => 'rpcpass',
    'chain' => 'test'
]);

# Get blockchain info
print_r($instance->getinfo());

# For MultiChain streams
print_r($instance->liststreamitems('test_stream'));
```

