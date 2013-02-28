<?php
/**
  * @package    php-jsonrpc2
  * @see        https://github.com/AndrewFarley/php-jsonrpc2
  * @version    1.4
  */

class SampleAPICallClass {
    public function __call($method, $args) {
        return "__call called for method $method and args " . print_r($args, true);
    }
}