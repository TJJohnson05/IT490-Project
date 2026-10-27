<?php 

require_once __DIR__. '/path.inc';
require_once __DIR__. '/get_host_info.inc';
require_once __DIR__. '/rabbitMQLib.inc';


$client = new rabbitMQClient(
	"testRabbitMQ.ini",
	"testServer"
);

$request = [ 
	"type" => "searchGames",
	"gameName" => "Fortnite"
];

$response = $client->send_request($request);

print_r($response);
