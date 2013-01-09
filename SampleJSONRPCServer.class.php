<?php
/**
  * @package    JSON-RPC 2 Server
  * @version    1.2
  */

/**
 * Required includes, our parent class
 */
require_once('jsonRPC2Server.class.php');

/**
 * The SampleJSONRPCServer object extends our jsonRPC2Server.  The parent class handles all raw json-rpc related tasks
 * while calling observer methods that are optional to implement in order to allow implementation of codebase-specific tasks
 * such as data input/output handling, session handling, API key handling, custom CRCs, encryption, etc.
 *
 * @package    default
 */
class SampleJSONRPCServer extends jsonRPC2Server {
    
    // Whether we want debug output or not
    static $debug = FALSE;
    static $time_started;
    static $extra_properties = array();
    
    /**
     * observer method: has_Started()
     *
     * Typically used for session stuff if using HTTP headers/cookies for sessions, and/or to do any early sanity checking on the input of the HTTP headers/cookies
     * but can also be used to override some security checks on the content of the remote request (such as the content type and request method)
     */
    public static function has_Started() {
        // This bypasses the jsonrpc server's content-type checks by force-setting the input to json so our class doesn't error out
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        // And temporarily force-setting our request method to a POST even if it wasn't
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Debugging
        if (self::$debug) error_log(__FUNCTION__.' called' );
        
        // For performance/metrics logging
        static::$time_started = microtime(TRUE);
    }
    
    /**
     * observer method: has_GotRawData(& $rawdata)
     *
     * Typically used for debug logging, the raw, undecoded JSON data is not really very interesting or usable, you probably want to
     * use has_GotData below, but this method can be good and used for dealing with encrypted/compressed payloads (since HTTP POST
     * by default does not support compression) or for implementing custom security or CRC mechanisms/checks on your raw data stream
     * which you validate through a HTTP header.  It can also be used temporarily for debugging to manually set the raw data stream
     * to a value since it's pass-by-reference.  Also, you can validate stuff like sessions here if they are passed via headers to save
     * the computing overhead of decoding the json if the session is invalid.
     */
    public static function has_GotRawData(& $rawdata) {
        if (self::$debug) error_log(__FUNCTION__.' called with arguments '.print_r($rawdata,TRUE) );
        
        // Do things with potential parameters in HTTP headers, often used for mobile-apis to track OS and app versions, for now lets just store these things
        /*
        // Attempt to load a session passed in via the URL, eg: http://website/apiendpoint.php?sessionid=1234567890
        if (isset($_REQUEST['sessionid'])) {
            session_id(static::$request['sessionid']);
            session_start();
            // Should maybe do a check to ensure your session is valid and you're logged in here, for example...
            if (!SystemHandler::isLoggedIn())
                throw new Exception('Error - invalid session');
                session_destroy();
            }
        }
        // Save some app/version-specific data passed in via HTTP headers
        if (isset($_SERVER['HTTP_DEVICE_MODEL'])) {
            list(static::$extra_properties['mobile_device'], static::$extra_properties['mobile_model']) = explode('-', $_SERVER['HTTP_DEVICE_MODEL']);
        }
        if (isset($_SERVER['HTTP_APP_VERSION'])) {
            static::$extra_properties['app_version'] = $_SERVER['HTTP_APP_VERSION'];
        }
        if (self::$debug) error_log(__FUNCTION__.' called with extra parameters '. print_r(static::$extra_properties, true) );
        */
    }
    
    /**
     * observer method: has_GotData()
     *
     * Typically used to setup sessions, validate API keys, or validate CRCs which send the data inside the payload
     * the payload is accessible via the static::$request variable, and can be modified if necessary before it's
     * sent to and processed by the class method
     */
    public static function has_GotData() {
        
        // Validate our API key within' our JSON-RPC payload, (specified at the top level)
        if( !isset(static::$request['api_key']) ) {
            throw new Exception('Error in JSON payload, no API key specified');
        } else {
            // Do the actual validation, which should throw an exception if it fails, you should put real validation logic here instead of just a string check
            if (static::$request['api_key'] != "valid_api_key") {
                throw new Exception('This is an invalid API key');
            }
        }
        
        // Validate our sessionid if the user passed a session id in their json-rpc
        if (isset(static::$request['sessionid']) && strlen(static::$request['sessionid']) > 0) {
            session_id(static::$request['sessionid']);
            session_start();
            // Should maybe do a check to ensure your session is valid and you're logged in here, for example...
            /*
            if (!SystemHandler::isLoggedIn())
                throw new Exception('Error - invalid session');
                session_destroy();
            }
            */
        }
        
        // Debugging
        if (self::$debug) error_log(__FUNCTION__.' returns with arguments '.print_r(static::$request,TRUE) );
    }
    
    /**
     * observer method: has_GotClassAndMethodName(& $classname, & $methodname)
     *
     * Typically used to have your code go require_once the necessary class from wherever your codebase stores them,
     * this is called right before attempting to process the request.  Also can be used to modify the class/method
     * name incase you have some complex class/method wiring logic in your ORM.  Or incase you want to rename or deprecate
     * methods.  Simply change what $classname or $methodname is to rewrite this request to use a different class/method
     */
    public static function has_GotClassAndMethodName(& $classname, & $methodname) {
        if (self::$debug) error_log('Request for '. $classname . '.' . $methodname);
        // Do something like include our API class here
        if (!is_file( "$classname.class.php" )) {
            throw new Exception("Invalid class name: $classname");
        }
        require_once( "$classname.class.php" );
    }
    
    /**
     * observer method: has_HandledRequestOK(& $result)
     *
     * This is called after handling the request from your class::method($opts) successfully without having an exception
     * thrown.  Can be used to trim/cleanse any data (security purposes?) or your typical debugging and logging purposes
     */
    public static function has_HandledRequestOK(& $result) {
        $args = func_get_args();
        if (self::$debug) error_log(__FUNCTION__.' called with arguments '.print_r($args,TRUE) );
    }
    
    
    /***********************************************************
     * NOTE: TODO: DO NOT THROW EXCEPTIONS IN ANY METHODS BELOW HERE, OTHERWISE BAD THINGS HAPPEN
     ***********************************************************/
     
    /**
     * observer method: has_HandledRequestERROR(& $string, & $code)
     *
     * This is called if an exception is thrown at anytime while handling your request, and it could be initiated 
     * from your own observer methods above if you threw an error (eg: invalid session/api key).
     * No typical use-case other than for debugging and logging purposes
     */
    public static function has_HandledRequestERROR(& $string, & $code) {
        $args = func_get_args();
        if (self::$debug) error_log(__FUNCTION__.' called with arguments '.print_r($args,TRUE) );
        // For debugging
        error_log((isset(static::$request['class']) ? static::$request['class'] : '') . (isset(static::$request['method'])?'.' . static::$request['method'] : '') . 'Caught exception (' . $code . ') ' . $string);
    }
    
    
    /**
     * observer method: has_HandledRequestERROR(& $response)
     *
     * This method is called right before serializing your response and outputting it to the other endpoint.  This is
     * intended to be used to re-set top-level custom arguments
     */
    public static function has_ResponseReady(& $response) {
        if (self::$debug) error_log(__FUNCTION__.' called with data '.print_r($response,TRUE) );
        // For performance/metrics, but only if we know when we started
        if (self::$debug && isset(static::$time_started) && strlen(static::$time_started) > 0) {
            $time_ended = microtime(TRUE);
            $time_took  = sprintf('%.4f ms ',(($time_ended - static::$time_started) * 1000));
            error_log(static::$request['class'] . '.' . static::$request['method'] . ' took a total of '.$time_took);
        }
    }
    
    
    /**
     * This overrides the jsonrpc server encode json method so that we properly encode beans based 
     * on whether they want short beans and based on their access level
     */
    public static function encodeJSON($data) {
        // At the very least, you must return a json-serialized version of this data
        return json_encode($data);
        /*
        // But you might want to do stuff security-related, like pruning/trimming data based on security levels and depending on the
        //  object/arrays being output.  And/or convert objects into exportable json elements (because objects do not json_encode in php)
        $auth_level = SystemUtils::getAuthLevel();
        return json_encode(SystemUtils::securely_encode_output($data, $auth_level));
        */
    }
}