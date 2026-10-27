<?php

/*
IT490 - Game Data

Retrieves game information from the backend
through RabbitMQ.

Frontend VM -> RabbitMQ -> Backend VM -> IGDB API
*/

require_once __DIR__ . '/../path.inc';
require_once __DIR__ . '/../get_host_info.inc';
require_once __DIR__ . '/../rabbitMQLib.inc';


function searchGamesFromBackend(string $gameName): array
{
    $gameName = trim($gameName);

    if ($gameName === "") {
        return [];
    }

    // Connect to the existing RabbitMQ testServer queue
    $client = new rabbitMQClient(
        __DIR__ . '/../testRabbitMQ.ini',
        "testServer"
    );

    // Request game information from backend
    $request = [
        "type" => "searchGames",
        "gameName" => $gameName
    ];

    $response = $client->send_request($request);

    // Check whether the backend returned valid data
    if (!is_array($response)) {
        throw new RuntimeException(
            "Invalid response from backend."
        );
    }

    if (($response["success"] ?? false) !== true) {
        throw new RuntimeException(
            $response["message"] ?? "Game search failed."
        );
    }

    return $response["games"] ?? [];
}


// Escape dynamic content before displaying it
function escapeHTML($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}