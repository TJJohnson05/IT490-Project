<?php
/*
IT490 ItWorkedYesterday Project
IGDB Automatic Authentication
*/

function igdbCredentials() {

    $home = getenv("HOME");

    if (!$home) {
        throw new Exception("HOME directory is not set.");
    }

    $file = $home . "/.config/IT490/igdb.ini";

    if (!is_readable($file)) {
        throw new Exception("IGDB credentials file not found.");
    }

    $config = parse_ini_file($file);

    if (!$config ||
        empty($config["client_id"]) ||
        empty($config["client_secret"])) {
        throw new Exception("Invalid IGDB credentials.");
    }

    return $config;
}


function getIGDBToken($config, $forceNew = false) {

    $cacheFile = getenv("HOME") .
                 "/.config/IT490/igdb-token.json";

    
    if (!$forceNew && is_file($cacheFile)) {

        $cached = json_decode(
            file_get_contents($cacheFile), true
        );

        if (is_array($cached) &&
            !empty($cached["access_token"]) &&
            ($cached["client_id"] ?? "") === $config["client_id"] &&
            ($cached["expires_at"] ?? 0) > time() + 120) {

            return $cached["access_token"];
        }
    }

    $ch = curl_init("https://id.twitch.tv/oauth2/token");

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_POSTFIELDS => http_build_query([
            "client_id" => $config["client_id"],
            "client_secret" => $config["client_secret"],
            "grant_type" => "client_credentials"
        ])
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $status !== 200) {
        throw new Exception(
            "Twitch token request failed: " .
            ($error ?: "HTTP " . $status)
        );
    }

    $data = json_decode($response, true);

    if (empty($data["access_token"]) ||
        empty($data["expires_in"])) {
        throw new Exception("Invalid Twitch token response.");
    }

    $cache = [
        "client_id" => $config["client_id"],
        "access_token" => $data["access_token"],
        "expires_at" => time() + (int)$data["expires_in"]
    ];

    $temporaryFile = tempnam(dirname($cacheFile), "token-");

    if ($temporaryFile === false) {
        throw new Exception("Cannot create IGDB token cache.");
    }

    chmod($temporaryFile, 0600);

    if (file_put_contents(
        $temporaryFile, json_encode($cache)
    ) === false || !rename($temporaryFile, $cacheFile)) {
        @unlink($temporaryFile);
        throw new Exception("Cannot save IGDB token cache.");
    }

    return $data["access_token"];
}

function igdbRequest($query) {

    $config = igdbCredentials();

    for ($attempt = 0; $attempt < 2; $attempt++) {

        $token = getIGDBToken($config, $attempt > 0);

        $ch = curl_init("https://api.igdb.com/v4/games");

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_POSTFIELDS => $query,
            CURLOPT_HTTPHEADER => [
                "Client-ID: " . $config["client_id"],
                "Authorization: Bearer " . $token,
                "Content-Type: text/plain"
            ]
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new Exception("IGDB connection error: " . $error);
        }

        if ($status === 200) {
            return $response;
        }

        if ($status !== 401 || $attempt === 1) {
            throw new Exception("IGDB API Error: HTTP " . $status);
        }
    }

    throw new Exception("IGDB authentication failed.");
}
