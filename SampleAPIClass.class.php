<?php
/**
  * @package    php-jsonrpc2
  * @see        https://github.com/AndrewFarley/php-jsonrpc2
  * @version    1.3
  */

class SampleAPIClass {
    public function outputThreeParameters($param1, $param2, $param3) {
        return "We received ($param1, $param2, $param3)";
    }
}