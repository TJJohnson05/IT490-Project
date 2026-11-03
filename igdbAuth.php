<?php

//IGDB Authentication (tokens expired and new ones have to be requested often)

function igdbCredentials() {
    $home = getenv("HOME");

    if (!$home) {
        throw new Exception("HOME directory is not set.");
    }

    $file = $home . "/.config/IT490/igdb.ini";

    if (!file_exists($file)) {
        throw new Exception("IGDB credentials file not found.");
    }

    $config = parse_ini_file($file);

    if (!$config || empty($config["client_id"]) || empty($config["client_secret"])) {
        throw new Exception("Invalid IGDB credentials.");
    }

    return $config;
}


function getIGDBToken($config, $forceNew = false) {
    $tokenFile = getenv("HOME") . "/.config/IT490/igdb-token.json";

    if (!$forceNew && file_exists($tokenFile)) {
	$savedToken = json_decode(file_get_contents($tokenFile), true);

	if (
		!empty($savedToken["access_token"]) &&
		!empty($savedToken["expires_at"]) && 
		$savedToken["expires_at"] > time()
	) {
		return $savedToken["access_token"];
	}
    }

    $ch = curl_init("https://id.twitch.tv/oauth2/token");

    $cacheFile = getenv("HOME") .
                 "/.config/IT490/igdb-token.json";

$postData = http_build_query([
        "client_id" => $config["client_id"],
        "client_secret" => $config["client_secret"],
        "grant_type" => "client_credentials"
    ]);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("Token request failed: " . $error);
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status != 200) {
        throw new Exception("Token request failed. HTTP code: " . $status);
    }

    $data = json_decode($response, true);

    if (empty($data["access_token"]) || empty($data["expires_in"])) {
        throw new Exception("Invalid token response.");
    }

    $saveData = [
        "access_token" => $data["access_token"],
        "expires_at" => time() + $data["expires_in"]
    ];

    file_put_contents($tokenFile, json_encode($saveData));

    return $data["access_token"];
}

function igdbRequest($query) {
    $config = igdbCredentials();
    $token = getIGDBToken($config);

    $ch = curl_init("https://api.igdb.com/v4/games");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Client-ID: " . $config["client_id"],
        "Authorization: Bearer " . $token,
        "Content-Type: text/plain"
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("IGDB request failed: " . $error);
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status == 401) {
        $token = getIGDBToken($config, true);

        $ch = curl_init("https://api.igdb.com/v4/games");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Client-ID: " . $config["client_id"],
            "Authorization: Bearer " . $token,
            "Content-Type: text/plain"
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception("IGDB request failed: " . $error);
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }

    if ($status != 200) {
        throw new Exception("IGDB API error. HTTP code: " . $status);
    }

    return $response;
}

?>
