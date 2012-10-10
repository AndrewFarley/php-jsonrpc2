<?php
/**
 * This class acts as a PHP JSON-RPC 2.0 Server, able to handle 2.0 by-name and ordered array of parameters
 *
 * Though not necessary, typically this class is extended for your codebase with observer methods
 * that kick off as the JSON-RPC request is being handled, allowing you to modify/change/update/validate data
 * and requests, setup/teardown sessions, validate API keys, etc.
 *
 * The current supported observer methods which a parent can implement are...
 *      has_Started()
 *      has_GotRawData(& $rawdata)
 *      has_GotData()
 *      has_GotClassAndMethodName(& $classname, & $methodname)
 *      has_HandledRequestOK(& $result)
 *      has_HandledRequestERROR(& $string, & $code)
 *      has_ResponseReady(& $response)
 *
 * There are also some others which you can override including...
 *      handle_json_decoding_error()
 *      generateOK($result, $id)
 *      outputOK($result, $id)
 *      generateError($reason, $code = -32600, $id = NULL)
 *      encodeJSON($data)
 *
 * @see     http://en.wikipedia.org/wiki/Jsonrpc
 * @author  Farley  <andrew@neonsurge.com>
 */

class jsonRPC2Server {

    // Our received request is stored here so we can access it from observer methods
    public static $request = NULL;
    
    /**
     * This function handles a JSON-RPC request from stdin.  Note: This is not meant for handling a JSON-RPC two-way streams of 
     * data, for that, you should see the "stream()" method (not implemented yet) that implements threads and supports
     * a TCP connection streaming data back and forth to a client.  stream() is intended to be used NOT from a HTTP endpoint
     * but from a xinetd or custom PHP socket-based endpoint.
     *
     * @param   string      $classname  (optional) The name of the class we're calling this method on.  If this value is set then our $method parameter
     *                                  in JSON-RPC should be just the method name.  If this value is NOT set, then our $method parameter should
     *                                  contain both the classname and the method with dot notation.  For example, if we wanted to call MyClass->MyMethod()
     *                                  then either pass in $classname = "MyClass" and in the JSON set $method to "MyMethod" OR leave classname empty
     *                                  and in your JSON set $method to "MyClass.MyMethod"
     */
    public static function handle($classname = '') {
        
        // Our monolithic try/catch block, so (some of) our observers can throw exceptions
        try {
            // Observer has_Started()
            static::has_Started();
        
            // Checks if a JSON-RPC request has been received and exits if it's not valid
            $valid_types = array('application/json','application/javascript','application/json-rpc');
            if ( $_SERVER['REQUEST_METHOD'] != 'POST' || empty($_SERVER['CONTENT_TYPE']) || !in_array($_SERVER['CONTENT_TYPE'],$valid_types ) ) {
                throw new Exception('Invalid content-type ('.$_SERVER['CONTENT_TYPE'].') expecting "application/json" or "application/javascript" content-type');
            }
        
            // reads the input JSON data from the PHP input raw data stream
            $rawdata = file_get_contents('php://input');
        
            // Observer: has_GotRawData(& $rawdata)
            static::has_GotRawData($rawdata);

            // Extract our JSON data
            static::$request = json_decode($rawdata,true);
            
            // Clean up (save memory)
            unset($rawdata);
            
            // Check for and throw JSON decoding errors if any exist
            self::handle_json_decoding_error();
            
            // Check for an invalid request
            if (static::$request === NULL) {
                throw new Exception('Invalid request - try some json-rpc next time');
            } else if (!isset(static::$request['method']) || !isset(static::$request['params']) || !isset(static::$request['id'])) {
                throw new Exception('Your JSON is missing one of the three required parameters: (method,params,id)');
            }

            // Observer: has_GotData()
            static::has_GotData();
        
            // Get our class name from our method parameter if we haven't set it already
            if (strlen($classname) <= 0) {
                $pos = strpos(static::$request['method'],'.');
                if ($pos !== FALSE) {
                    $classname = substr(static::$request['method'], 0, $pos);
                    static::$request['method'] = substr(static::$request['method'],$pos+1);
                } else {
                    throw new Exception('No class specified in your method parameter (eg: Classname.methodname)');
                }
            }
            
            // Put our classname into our request (split out)
            static::$request['class'] = $classname;

            // Observer: has_GotClassAndMethodName(& $classname, & $method)
            static::has_GotClassAndMethodName($classname, static::$request['method']);
        
            // If we don't have this class after our observer (hopefully) included the files, then bail!
            // NOTE: class_exists kicks off the autoloader, so if the autoloader is setup properly, no 
            // includes are necessary from has_GotClassAndMethodName()
            if (!class_exists($classname)) {
                throw new Exception('The class ('.$classname.') was not defined or is invalid');
            }
        
            // Ensure our method exists on our class
            if (!method_exists($classname, static::$request['method'])) {
                throw new Exception('The method '.static::$request['method'].'() is not defined on the class '.$classname);
            }

            // Ensure that we can call this method (aka, public) and implement our JSON-RPC 2.0 by-name parameters
            $reflection_class   = new ReflectionClass( $classname );
            $reflection_method  = $reflection_class->getMethod( static::$request['method'] );
            $result = $reflection_method->isPublic();
            if (!$result) {
                throw new Exception('The method ('.static::$request['method'].') is not public');
            }

            // If input parameters are by name, reorder them
            if (self::detectIfInputParametersAreByName(static::$request['params']) ) {
                // If they are by name then rearrange parameters by name
                static::$request['params'] = self::rearrangeParametersByName(
                    static::$request['params'],
                    self::reflection_to_clean_array($reflection_method->getParameters())
                );
            }
            
            // Try to call our method statically if this method is static.  
            if ($reflection_method->isStatic()) {
                // Try our static method call
                $result = 
                    forward_static_call_array( 
                        array( $classname, static::$request['method']),
                        static::$request['params']
                    );
            // Otherwise, try to call our method on an instance of the object
            } else {
                // Create our new class
                $object = new $classname;
                // And try to call our method on this class
                $result = 
                    call_user_func_array(
                        array($object,static::$request['method']),
                        static::$request['params']
                    );
            }
            
            // Observer has_HandledRequestOK($result)
            static::has_HandledRequestOK($result);
        
            // Return our result and exit
            self::outputOK($result, static::$request['id']);
        
        // If either of the above paths errors out, catch the exception and handle it here
        } catch (Exception $e) {
            // Grab our error message and code
            $code       = $e->getCode();
            $message    = $e->getMessage();

            // Observer has_HandledRequestERROR($message, $code)
            static::has_HandledRequestERROR($message, $code);

            // Pass back our error message and code through JSON-RPC
            self::outputError($message, $code);
        }
        
        // We shouldn't get here, ever unless the above logic was modified
        self::outputError('Internal error while trying to handle JSON-RPC');
    }
    
    /**
     * Our primary endpoint to output json to the user.  All output goes through this method
     *
     * @param   array   $data       The prepared standardized JSON-RPC array output
     * @param   boolean $andBail    Whether we want to exit right after we output the data to the user (default: TRUE)
     */
    public static function outputJSON($data, $andBail = TRUE) {
        // If we forgot to set which version of json-rpc we're using in our data stream, which we definitely do everywhere (below), but just incase
        if (is_array($data) && !isset($data['jsonrpc'])) {
            $arr = array_reverse($data, true); 
            $arr['jsonrpc'] = '2.0'; 
            $data = array_reverse($arr, true);
        }
        
        // Call our observer before outputting incase we want to add things
        static::has_ResponseReady($data);

        // Output our raw JSON
        static::outputRAWJSON( static::encodeJSON($data) );
        
        // If we want to bail right afterwards
        if ($andBail)
            exit;
    }
    
    /**
     * This is our JSON-Encoding utility method, in place so we can potentially override this from a parent
     * and trim the data output based on certain environment variables (eg: limit output based on user access)
     *
     * @param   mixed   $data   The data that we want to encode into JSON
     *
     * @return  string          A JSON-serialized string equivelant of the data passed into this method
     */
    public static function encodeJSON($data) {
        return json_encode($data);
    }
    
    /**
     * Simple output mechansim, wanted it in one place/method incase any special handling wants to be added here in headers
     * technically a child could override this class if they wanted to to change how things are output
     *
     * @param   string   $raw_json_string   The raw json string that we want to output to the other endpoint
     */
    public static function outputRAWJSON($raw_json_string) {
        // Send our JSON-RPC output and header type
        header('Content-type: application/json');
        // And output our data
        echo $raw_json_string;
    }
    
    /**
     * If we need to output an error
     *
     * @param   string   $reason    A string describing why this call is failing
     * @param   integer  $code      The error code of why this is failing (some clients can do different things based on different codes)
     * @param   integer  $id        When a request is made it has a unique ID which we must return in our response
     */
    public static function outputError($reason, $code = -32600, $id = NULL) {
        static::outputJSON( static::generateError($reason, $code, $id) );
    }

    /**
     * If we need to generate an error to output
     *
     * @param   string   $reason    A string describing why this call is failing
     * @param   integer  $code      The error code of why this is failing (some clients can do different things based on different codes)
     * @param   integer  $id        When a request is made it has a unique ID which we must return in our response
     *
     * @return  array               A standard JSON-RPC error array structure (in array format, not json serialized yet)
     */
    public static function generateError($reason, $code = -32600, $id = NULL) {
        $code = ( !is_numeric($code) || $code == 0 ? -32600 : $code );
        $id   = ( $id === 0 || $id === '0' ? $id : (int) $id );
        return array(
                    'jsonrpc'   => '2.0',
                    'id'        => $id,
                    'result'    => NULL,
                    'error'     => array (
                         'code'     =>  (int)    $code,
                         'message'  =>  (string) $reason
                    )
            );
    }

    /**
     * If we need to output a successful result
     *
     * @param   mixed   $result    The output of the RPC call
     * @param   integer $id        When a request is made it has a unique ID which we must return in our response
     */
    public static function outputOK($result, $id) {
        static::outputJSON( static::generateOK( $result, $id ) );
    }
    
    
    /**
     * If we need to output a successful result
     *
     * @param   mixed   $result    The direct output of an RPC call (could be an array, boolean, integer, serialized binary data, whatever)
     * @param   integer $id        When a request is made it has a unique ID which we must return in our response
     */
    public static function generateOK($result, $id) {
        return array(
                    'jsonrpc'   => '2.0',
                    'id'        => (int) $id,
                    'result'    => $result,
                    'error'     => NULL
               );
    }
    
    
    /**
     * This magic method detects if an input array is by-name or by-id
     *
     * @param   array   $input_array       The input data from a user
     */
    public static function detectIfInputParametersAreByName($input_array) {
        // If input array is not an array
        if (!is_array($input_array)) {
            return false;
        }
        // If ANY of the array keys are not numerically incremented, then this is a by-name array
        for ($i = 0; $i < count($input_array); $i++) {
            if (!array_key_exists($i,$input_array)) {
                return true;
            }
        }
        // If we got here, then it's a by-id array
        return false;
    }
    
    /**
     * Another magic method which rearranged parameters by name
     *
     * @param   array   $parameter_array    An array of parameters intended to go into the RPC method called
     * @param   array   $input_array        An array of keys pre-ordered based on what is defined in the method.  This
     *                                      is intended to be the output from self::reflection_to_clean_array()
     *
     * @return  array                       Returns a (potentially) re-ordered version of the input $parameter_array according
     *                                      to what order they are defined in our method.
     */
    public static function rearrangeParametersByName($parameter_array, $ordering_array) {
        // First some validation...
        if (!is_array($ordering_array)) {
            throw new Exception('The JSON-RPC 2.0 By-Name key parameters passed in were invalid, this is a server-error please report it to the administrator');
        }
        // Then re-order our array
        $final_array = array();
        foreach ($parameter_array as $key=>$val) {
            $newkey = array_search($key,$ordering_array);
            if ($newkey === FALSE) {
                throw new Exception('The JSON-RPC 2.0 By-Name parameter ('.$key.') appears to not be a valid key name for this method, please check your code (key names are case sensitive)');
            }
            $final_array[$newkey] = $val;
        }
        // Sort keys by array
        ksort($final_array, SORT_NUMERIC);
        return $final_array;
    }
    
    /**
     * This is to clean up output from PHP Reflection typical array output, used to help us achieve JSON-RPC 2.0
     *
     * @param   array   $reflection_array    This expects the output from ReflectionMethod::getParameters(), which is an array
     *                                       of ReflectionMethod objects
     *
     * @return  array                        Returns a simple PHP array of method names from $reflection_array
     */
    public static function reflection_to_clean_array($reflection_array) {
        $return = array();
        foreach ($reflection_array as $item) {
            $return[] = $item->name;
        }
        return $return;
    }
    
    /**
     * This handles json decoding errors, it has no inputs or outputs, it merely throws Exceptions if there was
     * any issues parsing the JSON
     */
    public static function handle_json_decoding_error() {
        switch( json_last_error() ) {
            case JSON_ERROR_NONE:
                return;
            break;
            case JSON_ERROR_DEPTH:
                throw new Exception('JSON Parsing error: Maximum stack depth exceeded');
            break;
            case JSON_ERROR_CTRL_CHAR:
            case JSON_ERROR_UTF8:
                throw new Exception('JSON Parsing error: Unexpected control character found or incorrect character encoding');
            break;
            case JSON_ERROR_SYNTAX:
            case JSON_ERROR_STATE_MISMATCH:
                throw new Exception('JSON Parsing error: Malformed JSON');
            break;
        }
    }
    
    /**
     * __callStatic helps facilitate the optional has_ methods in the jsonRPC2Server class 
     * without throwing exceptions if the parent does not implement the observer
     *
     * @param string    $funcname   Name of the function the user tried to __call
     * @param array     $args       Arguments that were passed into this function
     */
    public static function __callStatic($funcname, $args) {
        // Check if this is an observer call that wasn't implemented
        if (substr($funcname,0,4) == 'has_') {
            return TRUE;
        }
        
        /**
         * If it's not, then we have a problem, but it's probably in our jsonrpcserver code somewhere, still 
         * we should throw an error so we can nail this down and fix it
         */
        throw new Exception( "Method ($funcname) does not exist" );
    }
}