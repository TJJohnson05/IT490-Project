
<?php

require_once __DIR__ . '/path.inc';
require_once __DIR__ . '/get_host_info.inc';
require_once __DIR__ . '/rabbitMQLib.inc';
require_once __DIR__ . '/igdbAuth.php';


$batchSize = 500;

// Create a brand new file to track progess of while loop 
$progressFile = __DIR__ . '/igdb_import_progress.json';

// ID starting at 0 
$lastId = 0;

// how many games processed while running script 
$totalProcessed = 0;

try {

    // STEP 1: Load previous import progress if had been submitted 
    if (file_exists($progressFile)) {

        $progress = json_decode(
            file_get_contents($progressFile),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (!is_array($progress) ||
            !isset($progress['last_id']) ||
            !is_int($progress['last_id']) ||
            $progress['last_id'] < 0) {

            throw new RuntimeException(
                "Invalid import progress file."
            );
        }

        $lastId = $progress['last_id'];

        echo "Resuming import after IGDB ID: $lastId"
           . PHP_EOL;
    }

    //Rabbit MQ call 
    $client = new rabbitMQClient(
        __DIR__ . '/testRabbitMQDatabase.ini',
        'databaseServer'
    );

    // While loop call for infinite games 
    while (true) {

        echo PHP_EOL;
        echo "Fetching games after IGDB ID: $lastId"
           . PHP_EOL;

        
        $query = 'fields id, name, cover.image_id, '
               . 'first_release_date, rating, summary; '
               . 'where id > ' . $lastId . '; '
               . 'sort id asc; '
               . 'limit ' . $batchSize . ';';

        //Request games from IGDB
        $response = igdbRequest($query);

        //Decode JSON 
        $games = json_decode(
            $response,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (!is_array($games) || !array_is_list($games)) {
            throw new RuntimeException(
                "Unexpected IGDB response format."
            );
        }

        $count = count($games);

        echo "Received $count games from IGDB."
           . PHP_EOL;

        // Stop when IGDB returns no more games
        if ($count === 0) {

            echo "No more games returned."
               . PHP_EOL;

            break;
        }

        //Find highest IGDB ID 
        $lastGame = end($games);
        $newLastId = (int)($lastGame['id'] ?? 0);

        if ($newLastId <= $lastId) {
            throw new RuntimeException(
                "IGDB pagination did not advance."
            );
        }

        //Send the games to DB
        $request = [
            "type" => "import_games",
            "games" => $games
        ];

        $dbResponse = $client->send_request($request);

        // Make sure DB gets a response or error 
        if (!is_array($dbResponse) ||
            ($dbResponse['success'] ?? false) !== true) {

            throw new RuntimeException(
                "Database import failed after ID $lastId: " .
                (is_array($dbResponse)
                    ? ($dbResponse['message'] ?? 'Unknown error')
                    : 'Invalid response')
            );
        }

        //Update on games processed count 
        $processed = (int)(
            $dbResponse['processed'] ?? $count
        );

        $totalProcessed += $processed;

        echo "Imported/updated: $processed"
           . PHP_EOL;

        echo "Total processed this run: $totalProcessed"
           . PHP_EOL;

        //Update last successfully imported ID
        $lastId = $newLastId;

        //Save import progress
        $progressData = json_encode(
            ['last_id' => $lastId],
            JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
        );

        $temporaryFile = $progressFile . '.tmp';

        if (file_put_contents(
            $temporaryFile,
            $progressData,
            LOCK_EX
        ) === false) {

            throw new RuntimeException(
                "Failed to write import progress."
            );
        }

        if (!rename($temporaryFile, $progressFile)) {
            throw new RuntimeException(
                "Failed to save import progress."
            );
        }

        echo "Saved progress at IGDB ID: $lastId"
           . PHP_EOL;

        // Stop when the final batch is reached
        if ($count < $batchSize) {

            echo "Reached the final batch."
               . PHP_EOL;

            break;
        }

        //delay between API requests
        usleep(300000);
    }

    echo PHP_EOL;
    echo "IGDB synchronization complete."
       . PHP_EOL;

    echo "Total imported/updated this run: "
       . $totalProcessed
       . PHP_EOL;

    echo "Last imported IGDB ID: "
       . $lastId
       . PHP_EOL;

} catch (Throwable $e) {

    error_log(
        "IGDB synchronization failed: " .
        $e->getMessage()
    );

    echo "Error: " . $e->getMessage()
       . PHP_EOL;

    exit(1);
}
