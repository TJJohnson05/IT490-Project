<?php
//This code up to the end of the foreach response games is from the Frontend.
try {
    // Must point to the same broker/queue as your working testIGDBRabbitMQ.php.
    $client = new rabbitMQClient('testRabbitMQ.ini', 'testServer');
    $response = $client->send_request([
        'type' => 'fetchGames'
    ]);

    if (!is_array($response) || ($response['success'] ?? false) !== true) {
        throw new RuntimeException(
            is_array($response) ? (string)($response['message'] ?? 'Game search failed.') : 'Invalid backend response.'
        );
    }

    if (!isset($response['games']) || !is_array($response['games'])) {
        throw new RuntimeException('Backend returned an invalid games list.');
    }

    foreach ($response['games'] as $item) {
        if (!is_array($item) || !isset($item['id'], $item['name'])) {
            continue;
        }
        $imageId = $item['cover']['image_id'] ?? '';
        $cover = is_string($imageId) && preg_match('/^[a-zA-Z0-9_]+$/', $imageId)
        ? 'https://images.igdb.com/igdb/image/upload/t_cover_big/' . $imageId . '.jpg'
        : '';
        $timestamp = $item['first_release_date'] ?? null;
        $date = is_numeric($timestamp) ? gmdate('Y-m-d', (int)$timestamp) : '';
        $rating = isset($item['rating']) && is_numeric($item['rating'])
        ? number_format((float)$item['rating'], 1) . '/100'
        : 'Not rated';


?>
