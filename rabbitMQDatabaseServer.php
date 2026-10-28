#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

require_once __DIR__ . "/database.php";
require_once __DIR__ . "/profile_handlers.php";

function doLogin($username,$password)
{
    // lookup username in databas
    // check password
    try
    {
        $pdo = getDatabaseConnection();
        echo "Connected to MySQL db." . PHP_EOL;

        // $password_hash = password_hash($password, PASSWORD_DEFAULT);
        // $query = "SELECT * FROM users WHERE username = '$username' AND password_hash = '$password_hash'";
        // $stmt = $pdo->query($query);
        // $result = $stmt->execute();
        // echo "The result of the query is: " . $result["IT490"] . PHP_EOL;

        $queryCount= "SELECT COUNT(*) FROM users WHERE username = ?"; // AND password_hash = '$password_hash';
        $stmtCount = $pdo->prepare($queryCount);
        $execCount = $stmtCount->execute([$username]);
        $resultCount = $stmtCount->fetchColumn();
        echo "Made it past the resultcount" . PHP_EOL;

        if ($resultCount == 0){
            echo "resultcount is 0" . PHP_EOL;
            return false;
        }
        else
        {
            echo "we are selecting password_hash";
            $query2 = "SELECT password_hash FROM users WHERE username = ?";
            $stmt2 = $pdo->prepare($query2);
            $exec2 = $stmt2->execute([$username]);
            $result2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            foreach ($result2 as $row)
            {
                echo "we are in foreach loop" . PHP_EOL;
                if (password_verify($password, $row['password_hash']))
                {
                    echo "Password verified!" . PHP_EOL;
                    return true;
                }
            }

            echo "Invalid login." . PHP_EOL;
            return false;
        }


    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());

        echo "Failed to connect to MYSQL" . PHP_EOL;
        return false;
        //return false if not valid
    }
}

function doRegister($username, $email, $password)
{
    echo "registration request received." . PHP_EOL;
    echo "Username: " . $username . PHP_EOL;
    echo "Email: " . $email . PHP_EOL;
  
    try
    {
        $pdo = getDatabaseConnection();
        echo "Connected to MySQL db." . PHP_EOL;
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([$username, $email, $password_hash]);
        echo "The result of the query is: " . $result["IT490"] . PHP_EOL;

        return true;
    
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());

        echo "Failed to connect to MYSQL" . PHP_EOL;
        return false;
    }
}

function doUpdateDB($games)
{
    try
    {
        $pdo = getDatabaseConnection();
        echo "Connected to MySQL db." . PHP_EOL;
        foreach ($games as $item) {
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
            $igdb_id = $item['id'];



            $queryCount= "SELECT COUNT(*) FROM games WHERE igdb_id = ?"; // AND password_hash = '$password_hash';
            $stmtCount = $pdo->prepare($queryCount);
            $execCount = $stmtCount->execute([$igdb_id]);
            $resultCount = $stmtCount->fetchColumn();
            //echo "Made it past the resultcount" . PHP_EOL;

        if ($resultCount == 0){
            echo "creating new entry for game" . PHP_EOL;
            $query = "INSERT INTO games (igdb_id, name, summary, cover, first_release_date, rating) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($query);
            $result = $stmt->execute([$igdb_id, $name, $summary, $cover, $timestamp, $rating]);
            echo "The result of the query is: " . $result["IT490"] . PHP_EOL;
        }
        else
        {
            echo "entry already exists, updating";
            $query2 = "UPDATE games SET name=?, summary=?, cover=?, first_release_date=?,rating=? WHERE igdb_id = ?;"
            $stmt2 = $pdo->prepare($query2);
            $result = $stmt2->execute([$name, $summary, $cover, $timestamp, $rating, $igdb_id]);

            echo "The result of the query is: " . $result["IT490"] . PHP_EOL;
        }
        return true;

    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());

        echo "Failed to connect to MYSQL" . PHP_EOL;
        return false;
        //return false if not valid
    }



}

function doWriteRating($game_id, $username, $rating, $review)
{
    try
    {
        $pdo = getDatabaseConnection();
        echo "Connected to MySQL db." . PHP_EOL;
        $query = "INSERT INTO game_ratings (game_id, username, rating, review) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([$game_id, $username, $rating, $review]);
        echo "The result of the query is: " . $result["IT490"] . PHP_EOL;

        return true;

    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());

        echo "Failed to connect to MYSQL" . PHP_EOL;
        return false;
    }
}

function doGetRatings($game_id)
{
    try
    {
        $pdo = getDatabaseConnection();
        echo "Connected to MySQL db." . PHP_EOL;

        $ratings_list = []
        foreach ($ratings as $rating)
        {
            $query = "SELECT from game_ratings (game_id, username, rating, review) WHERE game_id = ?";
            $stmt = $pdo->prepare($query);
            $exec = $stmt->execute([$game_id]);
            $result = $exec->fetchAll(\PDO::FETCH_ASSOC);
            foreach($result as $row)
            {
                $rating_individual = [
                    "game_id" => $row['game_id'],
                    "username" => $row['username'],
                    "rating" => $row['rating'],
                    "review" => $row['review']
                ]
                $ratings_list[] = $rating_individual;
            }
            return $ratings_list;

        }

    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());

        echo "Failed to connect to MYSQL" . PHP_EOL;
        return false;
        //return false if not valid
    }
}


function requestProcessor($request)
{
  echo "received request".PHP_EOL;
  //var_dump($request);
  if(!isset($request['type']))
  {
    return "ERROR: unsupported message type";
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
      case "update_db":
        if(!isset($request["games"]))
        {
            return false;
        }
        return doUpdateDB(
            $request["games"]
        );
      case "get_ratings":
          if (!isset($request["game_id"]))
          {
              return false;
        }
        return doGetRatings(
            $request['game_id']
        );
      case "write_rating":
          if (!isset($request["rating"]))
          {
              return false;
        }
        return doWriteRating(
            $request['game_id'],
            $request["username"],
            $request["rating"],
            $request["review"]
        );
      case "validate_session":
          return false;

      default:
          return false;
  }
  //New stuff added for profile implementation
  if (
    is_array($request)
    && in_array(
        $request["type"] ?? "",
        [
            "get_profile",
            "update_profile",
            "change_password",
            "get_public_profile"
        ],
        true
        )) 
        {
        return handleProfileRequest(
            getDatabaseConnection(),
            $request
        );
}

  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}

$server = new rabbitMQServer("databaseServer.ini","databaseServer");

echo "databaseServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "databaseServer END".PHP_EOL;
exit();
?>

