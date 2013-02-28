<?php
/**
  * @package    php-jsonrpc2
  * @see        https://github.com/AndrewFarley/php-jsonrpc2
  * @version    1.4
  */

/**
 * This class acts as a PHP JSON-RPC 2.0 Client, able to handle 2.0 by-name and ordered array of parameters
 * requests by doing method chaining and/or dynamic method calls to the remote endpoint.
 *
 * So for example you can either...
 *      $client = new jsonRPC2Client();
 *      $result = $client->
 *                      _setParams(array('param1'=>'param1-data', 'param3'=>'param3-data', 'param2'=>'param2-data'))->
 *                      _setURL('http://my_not_real_sampleurl.dot.com/api.php')->
 *                      _setClassMethod('SampleAPIClass.outputThreeParameters')->
 *                      _setTopLevelItem('api_key', 'valid_api_key')->
 *                      _execute();
 *
 * Or you can use the simpler method (which only does positional parameters)
 *      $client = new jsonRPC2Client();
 *      $client->_setTopLevelItem('api_key', 'valid_api_key');
 *      $client->SampleAPIClass->outputThreeParameters('param1-data','param2-data','param3-data');
 *
 * And/or if you want to support by-name parameters (json-rpc 2.0) add __keyed to the name of your method 
 * called and pass your arguments in a single array as follows...
 *      $client = new jsonRPC2Client();
 *      $client->_setTopLevelItem('api_key', 'valid_api_key');
 *      $client->SampleAPIClass->outputThreeParameters__keyed(array('param1'=>'param1-data','param3'=>'param3-data'));
 *
 * There is generally no need to extend this class, but it has been designed with the intention so that you can extend it
 * if necessary to set default headers, api keys, do some data encryption/crc-ing/etc.
 *
 * @see     http://en.wikipedia.org/wiki/Jsonrpc
 * @see     http://www.jsonrpc.org/specification
 * @see     http://json-rpc.org/wiki/specification
 *
 * @author  Farley  <andrew@neonsurge.com>
 */
class jsonRPC2Client {
    public $request_id;
    public $request_data;
    public $request_class_method;
    public $request_url;
    public $request_top_level_array;
    public $request_http_headers;
    protected $cascader;
    protected static $last_id;
    
    /**
     * Our class constructor
     */
    public function __construct($url = '') {
        $this->init($url);
        return $this;
    }
    
    /**
     * Our class (re)init sequence
     */
    public function init($url) {
        $this->request_id = null;
        $this->request_data = null;
        $this->request_class_method = null;
        $this->request_url = $url;
        $this->cascader = array();
    }
    
    /**
     * This is to catch method cascading (see __call below)
     */
    public function __get($name) {
        echo "Get called with data: ".print_r($name, true);
        $this->cascader[] = $name;
        return $this;
    }

    /**
     * This is to allow-for dynamic execution of server methods on the client, in sexy-syntax style
     *
     * eg: $jsonrpcclient->myClassName->myMethodName('param1','param2','param3');
     */
    public function __call($method, $args) {
        $this->cascader[] = $method;
        $method = implode('.', $this->cascader);
        // echo "==== Method: ".substr($method,-7,7)."==== \n";
        if (strtolower(substr($method,-7,7)) == '__keyed') {
            $this->request_data = $args[0];
            $method = substr($method,0,strlen($method)-7);
        } else {
            $this->request_data = $args;
        }
        $this->request_class_method = $method;
        return $this->_execute();
    }
    
    /**
     * Sets our data parameters that will be sent along with our request
     */
    public function _setParams(array $array) {
        $this->request_data = $array;
        return $this;
    }
    
    /**
     * Sets the ID of this request (for our json-rpc request)
     */
    public function _setID($id) {
        $this->request_id = $id;
        return $this;
    }
    
    /**
     * Sets the URL we are trying to retrieve from
     */
    public function _setURL($url) {
        $this->request_url = $url;
        return $this;
    }
    
    /**
     * Sets the class method or just method that will be called.  This is typically in the format...
     *
     *      methodname
     *  or  classname.methodname
     *  or  classname->methodname  (rare)
     */
    public function _setClassMethod($classmethod) {
        $this->request_class_method = $classmethod;
        return $this;
    }
    
    /**
     * Sets a new item that will be placed at the top level of the json-rpc request
     */
    public function _setTopLevelItem($key, $value) {
        if ($key == 'id' || $key == 'method' || $key == 'params' || $key == 'error')
            throw new Exception ('The key '.$key.' is a reserved key value in JSON-RPC for the top-level.  Please try a non-reserved key');
        if (!is_string($key))
            throw new Exception ('The first parameter (key) must be a string (for keying an array)');
        $this->request_top_level_array[$key] = $value;
        return $this;
    }
    
    /**
     * Sets a new HTTP header
     */
    public function _setHTTPHeader($key, $value) {
        if (!is_string($key))
            throw new Exception ('The first parameter (key) must be a string (for keying an array)');
        $this->request_http_headers[$key] = $value;
        return $this;
    }
    
    /**
     * Execute our requested call
     */
    public function _execute($return_raw = FALSE) {
        // Ensure our user_agent param is set, along with any other HTTP headers
        if (!is_array($this->request_http_headers) || !isset($this->request_http_headers['user_agent'])) {
            $this->request_http_headers['user_agent'] = 'jsonRPC2Client_1.4/php_' . PHP_VERSION;
        }
        // Build our user agent string along with other HTTP headers in this weird hackey way for fopen POST requests
        $user_agent = $this->request_http_headers['user_agent'];
        unset($this->request_http_headers['user_agent']);
        foreach ($this->request_http_headers as $key=>$value) {
            $user_agent .= "\r\n" . strtoupper($key) . ': ' . $value;
        }
        ini_set('user_agent', $user_agent);

        $array_request = $this->_prepareRequestArray();
        // echo "Request looks like...";
        // print_r($array_request);
        $json_request = self::jsonify($array_request);
        // echo "Making request to ".$this->request_url."...";
        
        // performs the HTTP POST
        $opts = array ('http' => array (
                            'method'  => 'POST',
                            'header'  => 'Content-type: application/json',
                            'content' => $json_request
                            ));
        $context  = stream_context_create($opts);
        if ($fp = fopen($this->request_url, 'r', false, $context)) {
            $response = '';
            while($row = fgets($fp)) {
                $response.= trim($row)."\n";
            }
            if (!$return_raw)
                $response = json_decode($response,true);
            $this->init($this->request_url);
            return $response;
        } else {
            $this->init($this->request_url);
            throw new Exception('Unable to connect to '.$this->request_url."\n");
        }
    }
    
    /**
     * Convert the values set in this class to a JSON-RPC friendly array and return it
     */
    public function _prepareRequestArray() {
        // Prepare the standard JSON-RPC 2.0 Request
        $request = array(
                        'method' => $this->request_class_method,
                        'params' => $this->request_data,
                        'id' => ( !empty($this->request_id) ? $this->request_id : self::getNextID() )
                    );
        // If the user has any additional top-level items to add (the specification allows for this for stuff like API keys, session ids, etc)
        if (is_array($this->request_top_level_array)) {
            foreach ($this->request_top_level_array as $key=>$value) {
                $request[$key] = $value;
            }
        }
        // Return our request array
        return $request;
    }
    
    /**
     * We're putting this here so if you need to you can override this method in a child incase you
     * need to do special serialization of your obejcts for security purposes (on the client-side)
     */
    public static function jsonify($data) {
        return json_encode($data);
    }
    
    /**
     * Helper method to get the next ID, as when you use json-rpc as a client with a 
     * persistent connection, every request should have a different, ideally incrementing id.
     */
    public static function getNextID() {
        if (empty(static::$last_id)) {
            static::$last_id = 1;
        } else {
            static::$last_id++;
        }
        return static::$last_id;
    }
}
