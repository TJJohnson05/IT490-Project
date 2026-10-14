<?php

function getDatabaseConnection()
{
    $config = parse_ini_file(
        __DIR__ . "/database.ini",
        true
    );

    if ($config === false || !isset($config["database"])) {
        throw new RuntimeException("Database configuration missing.");
    }

    $db = $config["database"];

    $dsn = "mysql:host={$db['host']};" .
           "port={$db['port']};" .
           "dbname={$db['dbname']};" .
           "charset=utf8mb4";

    return new PDO(
        $dsn,
        $db["username"],
        $db["password"],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
}
?>
