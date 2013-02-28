<?php
/**
  * @package    php-jsonrpc2
  * @see        https://github.com/AndrewFarley/php-jsonrpc2
  * @version    1.4
  */

// Get our host and path (to figure out if we're on apache/nginx/etc or just being executed on a cli)

$sapi = php_sapi_name();
$return_char = "\n";

function output($data) {
    global $return_char;
    if ($return_char == "\n")
        $data = strip_tags($data);
    else
        $data = nl2br($data);
    if (substr($data,0,2) == '<h')
        echo $data;
    else
        echo $data . $return_char;
}

if (stristr($sapi, 'cli')) {
    output("Client Test Suite");
    output("Running in a CLI, skipping jsonRPCClient tests since we do not know the endpoint URL, re-run this php script from a server");
} else {
    $return_char = "<br>\n";
    output("<h2>Client Test Suite</h2>");
    // Figure out if we can run client tests by figuring out our hostname/url/etc
    $url = $host = $proto = '';
    if (!empty($_SERVER["PHP_SELF"]))
        $url = $_SERVER["PHP_SELF"];
    if (empty($url) && !empty($_SERVER["REQUEST_URI"]))
        $url = $_SERVER["REQUEST_URI"];
    if (!empty($_SERVER['HTTP_HOST']))
        $host = $_SERVER['HTTP_HOST'];
    else if (!empty($_SERVER['SERVER_NAME']))
        $host = $_SERVER['SERVER_NAME'];

    // Set the typical PHP server HTTPS=ON if we're using https (via the info from the amazon load balancer)
    if ((isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') || !empty($_SERVER['HTTPS'])) {
        $proto = 'https';
    } else {
        $proto = 'http';
    }
    
    if (empty($host) || empty($url)) {
        output("Unable to figure out our url, skipping jsonRPCClient tests since we can't figure out the url");
    } else {
        // If we have all the data, this should be our endpoint url
        $endpoint_url = $proto.'://'.$host.dirname($url).'/sample_endpoint.php';

        // Grab our jsonrpc client library
        require_once('jsonRPC2Client.class.php');
        
        // Method #1
        output("<h4>Method #1: Stringing internal method calls to do custom header/session nonsense, and using by-name (JSON-RPC 2.0) data</h4>");
        $client = new jsonRPC2Client();
        $result = $client->_setParams(array('param1'=>'param1-data', 'param3'=>'param3-data', 'param2'=>'param2-data'))->_setURL($endpoint_url)->_setClassMethod('SampleAPIClass.outputThreeParameters')->_setTopLevelItem('api_key', 'valid_api_key')->_setHTTPHeader('farley', 'value')->_setHTTPHeader('user_agent','custom')->_execute();
        output("Got result: " . print_r($result, true) );

        // Method #2
        output("<h3>Method #2: Simple method abstraction</h3>");
        $client = new jsonRPC2Client($endpoint_url);
        // Incase you need to set an API key or session in a top-level argument
        $client->_setTopLevelItem('api_key', 'valid_api_key');
        // Incase you need to set an API key or session in a HTTP header (ours does not process/handle this, but some systems use headers for sessions/api keys)
        // $client->_setHTTPHeader('api_key', 'valid_api_key');
        $result = $client->SampleAPICallClass->thisIsARandomMethod('value1', 'value2', 'value3');
        output("Got result: " . print_r($result, true) );

        // Method #3
        output("<h3>Method #3: Simple method abstraction with keyed array</h3>");
        $client = new jsonRPC2Client($endpoint_url);
        $client->_setTopLevelItem('api_key', 'valid_api_key');
        $result = $client->SampleAPIClass->outputThreeParameters__keyed(array('param3'=>'param3-data'));
        output("Got result: " . print_r($result, true) );
    }
}
output('');

output("<h2>Server Test Suite</h2>");

// Grab our Customized JSON-RPC Server
require_once('SampleJSONRPCServer.class.php');

output("<h3> - Sample 1</h3>");

output("    First, a by-name parameter example which takes advantage of PHP reflection to re-order your input parameters
    based on their variable name in the function definition in your class.  This is a requirement of JSON-RPC 2.0.
    If you notice, we pass the parameters in the wrong order but in a keyed array in params.  The output of the 
    method will show that these parameters were rearranged
");
 
$jsonrpcrequest = array(
    'id'=>3,
    'api_key'=>'valid_api_key',
    'method'=>'SampleAPIClass.outputThreeParameters',
    'params'=>array(
            'param1'=>'value1',
            'param3'=>'value3',
            'param2'=>'value2'
        )
    );

output("<h4>Sample 1 Input: </h4>" . print_r($jsonrpcrequest, true) . "<h4>Sample 1 Output (raw):</h4>");
ob_start();
SampleJSONRPCServer::handle('', json_encode($jsonrpcrequest), FALSE );
$output = ob_get_contents();
ob_flush();
ob_end_clean();
output("$return_char<h4>Sample 1 Output (decoded): </h4>" . print_r(json_decode($output, true), TRUE) . "$return_char" );

output("<h3> - Sample 2</h3>");

output("    Then, set our API key to an invalid value see what happens...
");

$jsonrpcrequest['api_key'] = 'invalid_value';

output("<h4>Sample 2 Input: </h4>" . print_r($jsonrpcrequest, true) . "<h4>Sample 2 Output (raw):</h4>");
ob_start();
SampleJSONRPCServer::handle('', json_encode($jsonrpcrequest), FALSE );
$output = ob_get_contents();
ob_flush();
ob_end_clean();
output("$return_char<h4>Sample 2 Output (decoded): </h4>" . print_r(json_decode($output, true), TRUE) . "$return_char" );


output("<h3> - Sample 3</h3>");

output("    Then, set our method to an invalid value to see what happens...
");


$jsonrpcrequest['api_key'] = 'valid_api_key';
$jsonrpcrequest['method'] = 'SampleAPIClass.invalidMethod';

output("<h4>Sample 3 Input: </h4>" . print_r($jsonrpcrequest, true) . "<h4>Sample 3 Output (raw):</h4>");
ob_start();
SampleJSONRPCServer::handle('', json_encode($jsonrpcrequest), FALSE );
$output = ob_get_contents();
ob_flush();
ob_end_clean();
output("$return_char<h4>Sample 3 Output (decoded): </h4>" . print_r(json_decode($output, true), TRUE) . "$return_char" );

output("<h3> - Sample 4</h3>");

output("    Then, set our class to an invalid class and see what happens...
");

$jsonrpcrequest['method'] = 'invalidClass.invalidMethod';

output("<h4>Sample 4 Input: </h4>" . print_r($jsonrpcrequest, true) . "<h4>Sample 4 Output (raw):</h4>");
ob_start();
SampleJSONRPCServer::handle('', json_encode($jsonrpcrequest), FALSE );
$output = ob_get_contents();
ob_flush();
ob_end_clean();
output("$return_char<h4>Sample 4 Output (decoded): </h4>" . print_r(json_decode($output, true), TRUE) . "$return_char" );


output("<h3> - Sample 5</h3>");

output("    Then, setting our parameters to json-rpc 1.0 positional params array, you should see that this output will now
    have value1, value3, then value2, since it's based on their position (positional, 1.0) not their key (by-name, 2.0)
");

$jsonrpcrequest['method'] = 'SampleAPIClass.outputThreeParameters';
$jsonrpcrequest['params'] = array('value1','value3','value2');

output("<h4>Sample 5 Input: </h4>" . print_r($jsonrpcrequest, true) . "<h4>Sample 5 Output (raw):</h4>");
ob_start();
SampleJSONRPCServer::handle('', json_encode($jsonrpcrequest), FALSE );
$output = ob_get_contents();
ob_flush();
ob_end_clean();
output("$return_char<h4>Sample 5 Output (decoded): </h4>" . print_r(json_decode($output, true), TRUE) . "$return_char" );

output("<h3> - Sample 6</h3>");

output("    Then, setting our parameters to invalid values (by-name parameters), setting a non-existant param param4_is_invalid
");

$jsonrpcrequest['method'] = 'SampleAPIClass.outputThreeParameters';
$jsonrpcrequest['params'] = array(
            'param1'=>'value1',
            'param3'=>'value3',
            'param2'=>'value2'
        );
$jsonrpcrequest['params']['param4_is_invalid'] = 'value4';


output("<h4>Sample 6 Input: </h4>" . print_r($jsonrpcrequest, true) . "<h4>Sample 6 Output (raw):</h4>");
ob_start();
SampleJSONRPCServer::handle('', json_encode($jsonrpcrequest), FALSE );
$output = ob_get_contents();
ob_flush();
ob_end_clean();
output("$return_char<h4>Sample 6 Output (decoded): </h4>" . print_r(json_decode($output, true), TRUE) . "$return_char" );
unset($jsonrpcrequest['params']['param4_is_invalid']);

output("<h3> - Sample 7</h3>");

output("    Then, on a new class with a __call method, attempting to invoke it by calling some random non-existant method name
");

$jsonrpcrequest['method'] = 'SampleAPICallClass.thisMethodDoesNotExist';

output("<h4>Sample 7 Input: </h4>" . print_r($jsonrpcrequest, true) . "<h4>Sample 7 Output (raw):</h4>");
ob_start();
SampleJSONRPCServer::handle('', json_encode($jsonrpcrequest), FALSE );
$output = ob_get_contents();
ob_flush();
ob_end_clean();
output("$return_char<h4>Sample 7 Output (decoded): </h4>" . print_r(json_decode($output, true), TRUE) . "$return_char" );
