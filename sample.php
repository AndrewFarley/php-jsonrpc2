<?php

require_once('SampleJSONRPCServer.class.php');

echo "
/**
 * Sample 1
 *
 * First, a by-name parameter example which takes advantage of PHP reflection to re-order your input parameters
 * based on their variable name in the function definition in your class.  This is a requirement of JSON-RPC 2.0.
 * If you notice, we pass the parameters in the wrong order but in a keyed array in params.  The output of the 
 * method will show that these parameters were rearranged
 */
 
";
 

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

echo "Sample 1 Input: \n" . print_r($jsonrpcrequest, true) . "\nSample 1 Output (raw): \n";
ob_start();
SampleJSONRPCServer::handle('', json_encode($jsonrpcrequest), FALSE );
$output = ob_get_contents();
ob_flush();
ob_end_clean();
echo "\n\nSample 1 Output (decoded): \n";
echo print_r(json_decode($output, true), TRUE);

echo "
/**
 * Sample 2
 *
 * Then, set our API key to an invalid value see what happens...
 */
 
";

$jsonrpcrequest['api_key'] = 'invalid_value';

echo "Sample 2 Input: \n" . print_r($jsonrpcrequest, true) . "\nSample 2 Output (raw): \n";
ob_start();
SampleJSONRPCServer::handle('', json_encode($jsonrpcrequest), FALSE );
$output = ob_get_contents();
ob_flush();
ob_end_clean();
echo "\n\nSample 2 Output (decoded): \n";
echo print_r(json_decode($output, true), TRUE);

echo "
/**
 * Sample 3
 *
 * Then, set our method to an invalid value to see what happens...
 */
 
 ";

$jsonrpcrequest['api_key'] = 'valid_api_key';
$jsonrpcrequest['method'] = 'SampleAPIClass.invalidMethod';

echo "Sample 3 Input: \n" . print_r($jsonrpcrequest, true) . "\nSample 3 Output (raw): \n";
ob_start();
SampleJSONRPCServer::handle('', json_encode($jsonrpcrequest), FALSE );
$output = ob_get_contents();
ob_flush();
ob_end_clean();
echo "\n\nSample 3 Output (decoded): \n";
echo print_r(json_decode($output, true), TRUE);

echo "
/**
 * Sample 4
 *
 * Then, set our class to an invalid class and see what happens...
 */

";

$jsonrpcrequest['method'] = 'invalidClass.invalidMethod';

echo "Sample 4 Input: \n" . print_r($jsonrpcrequest, true) . "\nSample 4 Output (raw): \n";
ob_start();
SampleJSONRPCServer::handle('', json_encode($jsonrpcrequest), FALSE );
$output = ob_get_contents();
ob_flush();
ob_end_clean();
echo "\n\nSample 4 Output (decoded): \n";
echo print_r(json_decode($output, true), TRUE);


echo "
/**
 * Sample 6
 *
 * Then, setting our parameters to json-rpc 1.0 positional params array, you should see that this output will now
 * have value1, value3, then value2, since it's based on their position (positional, 1.0) not their key (by-name, 2.0)
 */

";

$jsonrpcrequest['params'] = array('value1','value3','value2');

echo "Sample 6 Input: \n" . print_r($jsonrpcrequest, true) . "\nSample 6 Output (raw): \n";
ob_start();
SampleJSONRPCServer::handle('', json_encode($jsonrpcrequest), FALSE );
$output = ob_get_contents();
ob_flush();
ob_end_clean();
echo "\n\nSample 6 Output (decoded): \n";
echo print_r(json_decode($output, true), TRUE);
unset($jsonrpcrequest['params']['param4_is_invalid']);


echo "
/**
 * Sample 7
 *
 * Then, setting our parameters to invalid values (by-name parameters), setting a non-existant param param4_is_invalid
 */

";

$jsonrpcrequest['method'] = 'SampleAPIClass.outputThreeParameters';
$jsonrpcrequest['params'] = array(
            'param1'=>'value1',
            'param3'=>'value3',
            'param2'=>'value2'
        );
$jsonrpcrequest['params']['param4_is_invalid'] = 'value4';


echo "Sample 7 Input: \n" . print_r($jsonrpcrequest, true) . "\nSample 7 Output (raw): \n";
ob_start();
SampleJSONRPCServer::handle('', json_encode($jsonrpcrequest), FALSE );
$output = ob_get_contents();
ob_flush();
ob_end_clean();
echo "\n\nSample 7 Output (decoded): \n";
echo print_r(json_decode($output, true), TRUE);
unset($jsonrpcrequest['params']['param4_is_invalid']);

echo "
/**
 * Sample 8
 *
 * Then, on a new class with a __call method, attempting to invoke it by calling some random non-existant method name
 */

";

$jsonrpcrequest['method'] = 'SampleAPICallClass.thisMethodDoesNotExist';

echo "Sample 8 Input: \n" . print_r($jsonrpcrequest, true) . "\nSample 8 Output (raw): \n";
ob_start();
SampleJSONRPCServer::handle('', json_encode($jsonrpcrequest), FALSE );
$output = ob_get_contents();
ob_flush();
ob_end_clean();
echo "\n\nSample 8 Output (decoded): \n";
echo print_r(json_decode($output, true), TRUE);
unset($jsonrpcrequest['params']['param4_is_invalid']);