<?php
require __DIR__ . '/src/estimator.php';
require __DIR__ . '/vendor/autoload.php';
use Spatie\ArrayToXml\ArrayToXml;

// Json decimal precision hack.. - https://stackoverflow.com/a/41827056/380138
ini_set('serialize_precision','-1');

$eta = -hrtime(true);

// Remove trailing slashe
$uri = rtrim($_SERVER['REQUEST_URI'], '/');

$responseCode = (in_array($uri, [
	'/api/v1/on-covid-19/xml',
	'/api/v1/on-covid-19/logs',
	'/api/v1/on-covid-19',
	'/api/v1/on-covid-19/json'

])) ? 200 : 404;

$data = ['status' => 'NOT FOUND 404', 'code' => 404];
if ($responseCode == 200) {

	// Read the input stream
	$body = file_get_contents("php://input");
	// Decode the JSON object
	$object = json_decode($body, true);
	// Send the object for processing...
	$data = covid19ImpactEstimator($object);
}

$eta += hrtime(true);
$eta = floor($eta/1e+6);

// Log the request
logOperation($responseCode);
// Set headers and show response..
setHeadersAndShowResponse($data);


function logOperation(){
	global $eta;
	global $responseCode;

	$requestString = $_SERVER['REQUEST_METHOD'] ."\t\t" . $_SERVER['REQUEST_URI'] ."\t\t". $responseCode. "\t\t".$eta." ms\r\n";
	file_put_contents("requests.file", $requestString, FILE_APPEND | LOCK_EX);
}

function setHeadersAndShowResponse($data = []){
	global $responseCode;
	global $uri;
	// Remove trailing slashe
	$uri = rtrim($_SERVER['REQUEST_URI'], '/');

	// Proceed to set the necessary headers..
	switch($uri){
		case '/api/v1/on-covid-19/xml':
			http_response_code($responseCode);
			header ("Content-Type: application/xml");
			echo ArrayToXml::convert($data, 'root');
		break;
		case '/api/v1/on-covid-19/logs':
			http_response_code($responseCode);
			header("Content-Type: text/plain");
			echo file_get_contents("requests.file");
		break;
		case '/api/v1/on-covid-19':
		case '/api/v1/on-covid-19/json':
			http_response_code($responseCode);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($data);
		break;
		default:
			http_response_code(404);
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($data);
	}
}


function debug($array = []){
	print("<pre>".print_r($array,true)."</pre>");
}