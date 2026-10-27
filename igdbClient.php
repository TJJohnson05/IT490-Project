<?php

/*
IT490 ITWorked Yesterday Project
IGDB APT Integration
Purpose: This will retrieve video game data from IGDB
*/

require_once __DIR__. '/igdbAuth.php';

function searchGames($gameName) {

    $gameName = trim($gameName);

    if ($gameName === "") {
        throw new Exception("Please enter a game name.");
    }


    $gameName = str_replace(["\r", "\n"], " ", $gameName);
    $gameName = addcslashes($gameName, "\\\"");

    $query = 'search "' . $gameName . '"; '
           . 'fields name,cover.image_id,first_release_date,rating,summary; '
           . 'limit 10;';

    $response = igdbRequest($query);

    $games = json_decode($response, true);

    if (!is_array($games)) {
        throw new Exception("Invalid IGDB response.");
    }

    return $games;
}

if (isset ($_SERVER["SCRIPT_FILENAME"]) && realpath($_SERVER["SCRIPT_FILENAME"]) === __FILE__) {

	try {
		$gameName = implode(" ", array_slice($argv, 1));

		$games = searchGames($gameName);

		echo json_encode($games, JSON_PRETTY_PRINT) . "\n";

	} catch (Exception $e) {
		fwrite(STDERR, $e->getMessage() . "\n");
		exit(1);
	}
}
?>
