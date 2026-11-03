<?php


// IGDB APT Integration
// Will search for games and retreive game details


require_once __DIR__. '/igdbAuth.php';

function searchGames($gameName) {  //function takes game name user would like to lookup

    $gameName = trim($gameName);

    if ($gameName === "") {
        throw new Exception("Please enter a game name.");
    }


    //$gameName = str_replace(["\r", "\n"], " ", $gameName);
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

function getGameDetails($gameID)
{
    if (!is_numeric($gameID) || $gameID <= 0) {
        throw new Exception("Invalid game ID.");
    }

    // IGDB query for one specific game.
    $query = 'fields name,summary,cover.image_id,'
           . 'first_release_date,rating,genres.name; '
           . 'where id = ' . $gameID . '; '
           . 'limit 1;';

    // this will reuse the existing IGDB authentication
    $response = igdbRequest($query);

    $games = json_decode($response, true);

    if (!is_array($games)) {
        throw new Exception("Invalid IGDB response.");
    }

    // No game found with the supplied ID.
    if (empty($games)) {
        return null;
    }

    return $games[0];
}

if (isset($argv[1])) {
	$gameName = $argv[1];
	$games = searchGames($gameName);
	print_r($games);
}	
?>
