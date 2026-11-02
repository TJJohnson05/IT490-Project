<?php
session_start();
//Prof files 
require_once __DIR__ . '/../path.inc';
require_once __DIR__ . '/../get_host_info.inc';
require_once __DIR__ . '/../rabbitMQLib.inc';
//User logged in 
if (($_SESSION["logged_in"] ?? false) !== true) {
    header("Location: login.php");
    exit;
}

function searchGamesFromBackend(string $gameName): array
{
    $gameName = trim($gameName);

    if ($gameName === "") {
        return [];
    }
    $client = new rabbitMQClient(
        __DIR__ . '/../testRabbitMQ.ini',
        "testServer"
    );

    //BE Request and return said data 
    $request = [
        "type" => "searchGames",
        "gameName" => $gameName
    ];
    $response = $client->send_request($request);

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


//Prevent code from webpage 
function escapeHTML($value): string
{
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}