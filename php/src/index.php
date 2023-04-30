<?php

require '../vendor/autoload.php';

$client = new \GuzzleHttp\Client();
$response = $client->request('GET', 'http://localhost:7000');

if ($response->getStatusCode() !== 200) {
    http_response_code(500);
    die('Error Proxying Page');
}

$html = $response->getBody();

$doc = new DOMDocument();
$doc->loadHTML($html);

//Capture scripts elements
$scripts = [];

$scriptElements = $doc->getElementsByTagName('script');

$elementCount = $scriptElements->length;
//Deleting using a foreach will affect the node query
while ($elementCount > 0) {
    $elementCount--;
    $scriptElement = $scriptElements->item($elementCount);
    //TODO: If both src and content
    if ($scriptElement->hasAttribute('src')) {
        $src = parse_url($scriptElement->getAttribute('src'));

        if (array_key_exists('host', $src)) {
            $scripts[uniqid()] = [
                'type' => 'src',
                'src' => $scriptElement->getAttribute('src'),
            ];
        } else {
            $response = $client->request('GET', "http://localhost:7000/".$scriptElement->getAttribute('src'));
            $scripts[uniqid()] = [
                'type' => 'inline',
                'content' => $response->getBody()->getContents(),
            ];            
        }
    } else {
        $scripts[uniqid()] = [
            'type' => 'inline',
            'content' => $scriptElement->textContent,
        ];
    }

    $scriptElement->parentNode->removeChild($scriptElement); 
}

//Capture style elements
$styles = [];

$styleElements = $doc->getElementsByTagName('link');

$elementCount = $styleElements->length;

//Deleting using a foreach will affect the node query
while ($elementCount > 0) {
    $elementCount--;
    $styleElement = $styleElements->item($elementCount);
    if ($styleElement->hasAttribute('rel') && $styleElement->getAttribute('rel') === 'stylesheet') {
        $src = parse_url($styleElement->getAttribute('href'));
        if (array_key_exists('host', $src)) {
            $styles[uniqid()] = [
                'type' => 'src',
                'src' => $styleElement->getAttribute('href'),
            ];
        } else {
            $response = $client->request('GET', "http://localhost:7000/".$styleElement->getAttribute('href'));
            $styles[uniqid()] = [
                'type' => 'inline',
                'content' => $response->getBody()->getContents(),
            ];            
        }
    }

    $styleElement->parentNode->removeChild($styleElement); 
}

//TODO: Replace Body tag with div id='body'
$bodyElement = $doc->getElementsByTagName('body')->item(0);

//Return as html
$response = [
    "html" => addslashes($doc->saveHTML($bodyElement)),
    "scripts" => $scripts,  
    "styles" => $styles,
];

http_response_code(200);
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
