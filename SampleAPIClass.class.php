<?php
/**
  * @package    JSON-RPC 2 Server
  * @version    1.1
  */

class SampleAPIClass {
    public function outputThreeParameters($param1, $param2, $param3) {
        return "We received ($param1, $param2, $param3)";
    }
}