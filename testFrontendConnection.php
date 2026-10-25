<?php

$config = parse_ini_file(
    __DIR__ . "/testRabbitMQ.ini",
    true
);

if ($config === false || !isset($config["testServer"])) {
    die("Could not load RabbitMQ configuration.\n");
}

$settings = $config["testServer"];

try {
    $connection = new AMQPConnection([
        "host" => $settings["BROKER_HOST"],
        "port" => (int) $settings["BROKER_PORT"],
        "login" => $settings["USER"],
        "password" => $settings["PASSWORD"],
        "vhost" => $settings["VHOST"]
    ]);

    $connection->connect();

    echo "Successfully connected to RabbitMQ!\n";
    echo "Broker: " . $settings["BROKER_HOST"] . "\n";
    echo "Port: " . $settings["BROKER_PORT"] . "\n";
    echo "Virtual host: " . $settings["VHOST"] . "\n";

    $connection->disconnect();

} catch (Throwable $e) {
    echo "RabbitMQ connection failed: "
        . $e->getMessage() . "\n";
    exit(1);
}
