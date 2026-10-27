<?php

/*
IT490 ITWorked Yesterday Project
IGDB APT Integration
Purpose: This will retrieve video game data from IGDB
*/

function searchGames($gameName) {

	$apiURL = "https://api.igdb.com/v4/games";

	$clientID = getenv("IGDB_CLIENT_ID");
	$accessToken = getenv("IGDB_ACCESS_TOKEN");

	if (!$clientID || !$accessToken) {
		throw new Exception ("IGDB credentials are missing.");
	}

	$gameName = trim($gameName);

	if($gameName === "") {
		throw new Exception("Please enter a game name.");
	}

	$gameName = trim($gameName);
	
	if ($gameName === "") {
		throw new Exception("Please enter a game name.");
	}

	$gameName = str_replace(["\r", "\n"], " ", $gameName);
	$gameName = addcslashes($gameName, "\\\"");

	$query = 'search " ' . $gameName . '"; '
		. 'fields name,cover.image_id,first_release_date,rating,summary; '
		. 'limit 10;';

	//game name, cover image, release dates 

	$ch = curl_init($apiURL);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $query);

	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		"Client-ID: $clientID", 
		"Authorization: Bearer $accessToken", 
		"Content-Type: text/plain" 
	]);

	$response = curl_exec($ch);

	if($response === false) {
		$error = curl_error($ch);
		curl_close($ch);
		throw new Exception ("API Error: " . $error);
	}
	
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if ($httpCode !== 200) {
		throw new Exception("IGDB API Error : HTTP ". $httpCode);
	}

	$games = json_decode($response, true);

	if (!is_array($games)) {
		throw new Exception("Invalide response from IGDB.");
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
