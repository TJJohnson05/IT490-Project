#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');
require_once('igdbClient.php');

function sentDatabaseRequest($request) {
    try {
        $client = new rabbitMQClient (
            "testRabbitMQDatabase.ini",
            "databaseServer"
        );

        return $client->send_request($request);

    }   catch (Throwable $e) {
        error_log("Database RabbitMQ error: " . $e->getMessage());
        return false;
    }
}

function doLogin($username,$password)
{
    echo "Processing login request..." . PHP_EOL;

    if(empty($username) || empty($password)) {
        return false;
    }

    $request = [
        "type" => "login",
        "username" => $username,
        "password" => $password
    ];
    
    //Send request to db vm 
    $response = sentDatabaseRequest($request);
    echo "Database login result: "
    . ($response === true ? "SUCCESS" : "FAILED")
    . PHP_EOL;

    return $response === true;
}

function doRegister($username, $email, $password)
{
  echo "registration request recieved." . PHP_EOL;

  $username = trim($username);
  $email = trim($email);

  if (
    empty($username) ||
    empty($email) ||
    empty($password)
  ) {
    return false;
  }

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return false;
  }

  $request = [
    "type" => "register",
    "username" => $username,
    "email" => $email,
    "password" => $password
  ];

  //Send registration request to DB VM
  $response = sentDatabaseRequest($request);
  echo "Database registration result: "
    . ($response === true ? "SUCCESS" : "FAILED")
    . PHP_EOL;
  return $response === true;
}

function requestProcessor($request)
{
  echo "received request from frontend" . PHP_EOL;

  //should not return whole array cause of passwords, even if password is hashed, we wanna avoid this 
  if (!is_array($request) || !isset($request['type'])) {
    return false;
  }

  
  switch ($request["type"]) {
      case "login":
          if (!isset($request["username"], $request["password"])) {
              return false;
          }

          return doLogin(
              $request["username"],
              $request["password"]
          );

      case "register":
          if (!isset(
              $request["username"],
              $request["email"],
              $request["password"]
          )) {
              return false;
          }

          return doRegister(
              $request["username"],
              $request["email"],
              $request["password"]
          );

      case "test_database";
          return sentDatabaseRequest([
            "type" => "test_database"
          ]);

      case "validate_session":
          return false;
        // search for games feature on the browse page
      case "searchGames":
        try {
            $gameName = $request["gameName"] ?? "";
            $games = searchGames($gameName);
            
            return [
                "success"  => true,
                "games" => $games
            ];
        } catch (Exception $e) {
            return [
                "success" => false,
                "message" => $e->getMessage()
            ];
        }

        default:
            echo "Unknown request type" . PHP_EOL;
            return false;
            //view details on the browse page cards
            case "getGameDetails":
                try {
                    $gameId = filter_var(
                        $request["gameId"] ?? null,
                        FILTER_VALIDATE_INT,
                        ["options" => ["min_range" => 1]]
                    );
            
                    if ($gameId === false) {
                        return [
                            "success" => false,
                            "message" => "Invalid game ID."
                        ];
                    }
            
                    echo "Fetching IGDB game ID: " . $gameId . PHP_EOL;
            
                    $game = getGameDetails($gameId);
            
                    if ($game === null) {
                        return [
                            "success" => false,
                            "message" => "Game not found."
                        ];
                    }
            
                    return [
                        "success" => true,
                        "game" => $game
                    ];
            
                } catch (Throwable $e) {
                    error_log("IGDB game details error: " . $e->getMessage());
            
                    return [
                        "success" => false,
                        "message" => "Unable to retrieve game details."
                    ];
                }       
    }

  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}

//backend is listening for frontend requests 
$server = new rabbitMQServer("testRabbitMQ.ini","testServer");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>

