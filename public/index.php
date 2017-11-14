<?php
// Setup Autoloading
require_once __DIR__ . '/../vendor/autoload.php';

// Get Config
$config = require(__DIR__ . '/../config.php');

// Init and Run
$api = new \WorldApi\Api($config);

try {
    $request = \Zend\Diactoros\ServerRequestFactory::fromGlobals();
    $response = $api->handleRequest($request);

    if(!($response instanceof \Psr\Http\Message\ResponseInterface) &&
       !($response instanceof \Crell\ApiProblem\ApiProblem)){
        throw new UnexpectedValueException('api did not return a response');
    }

} catch (Exception $e) {
    $response = new \Crell\ApiProblem\ApiProblem('Internal Server Error', 'https://httpstatusdogs.com/500-internal-server-error');
    $response->setStatus(500);
    $response->setDetail(get_class($e));
    $response->setDetail($e->getMessage());
}

// Convert any API Problem into a Response
if($response instanceof \Crell\ApiProblem\ApiProblem){
    $response = new \Zend\Diactoros\Response\JsonResponse($response->asArray(), $response->getStatus(), [
        'Content-Type' => ['application/problem+json']
    ]);
}

// Send the Response
$emitter = new \Zend\Diactoros\Response\SapiEmitter();
$emitter->emit($response);
