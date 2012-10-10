<?php

class SampleAPICallClass {
    public function __call($method, $args) {
        return "__call called for method $method and args " . print_r($args, true);
    }
}