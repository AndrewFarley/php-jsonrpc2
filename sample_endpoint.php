<?php
/**
  * @package    php-jsonrpc2
  * @see        https://github.com/AndrewFarley/php-jsonrpc2
  * @version    1.4
  */

// To make a JSON-RPC Endpoint in your system it's as easy as including the class...
require_once('SampleJSONRPCServer.class.php');

// And handling the request
SampleJSONRPCServer::handle();

// Now, simply point JSON-RPC requests now to this file on your webserver and you have a JSON-RPC 2.0 Endpoint