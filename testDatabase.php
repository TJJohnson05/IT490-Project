<?php

require_once __DIR__ . "/database.php";

try {
    $pdo = getDatabaseConnection();

    echo "Successfully connected to MySQL!" . PHP_EOL;

    $stmt = $pdo->query("SELECT DATABASE() AS db_name");
    $result = $stmt->fetch();

    echo "Connected database: " . $result["db_name"] . PHP_EOL;

} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());

    echo "Failed to connect to MySQL." . PHP_EOL;
}
?>
