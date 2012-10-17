<?php
/**
  * @package    JSON-RPC 2 Server
  * @version    1.2
  */

class SampleAPICallClass {
    public function __call($method, $args) {
        return "__call called for method $method and args " . print_r($args, true);
    }
}