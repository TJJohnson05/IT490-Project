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
            ? $item['rating'] //? number_format((float)$item['rating'], 1) . '/100'
            : 0;
            $igdb_id = $item['id'];
            $name = $item['name'];
            $summary = $item['summary'] ?? null;



            $queryCount= "SELECT COUNT(*) FROM games WHERE igdb_id = ?"; // AND password_hash = '$password_hash';
            $stmtCount = $pdo->prepare($queryCount);
            $execCount = $stmtCount->execute([$igdb_id]);
            $resultCount = $stmtCount->fetchColumn();
            //echo "Made it past the resultcount" . PHP_EOL;
            echo "adding game " . $name . PHP_EOL;
            if ($resultCount == 0){
                echo "creating new entry for game" . PHP_EOL;
                $query = "INSERT INTO games (igdb_id, name, summary, cover, first_release_date, rating) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($query);
                $result = $stmt->execute([$igdb_id, $name, $summary, $cover, $date, $rating]);
                echo "The result of the query is: " . $result["IT490"] . PHP_EOL;
            }
            else
            {
                echo "entry already exists, updating";
                $query2 = "UPDATE games SET name=?, summary=?, cover=?, first_release_date=?,rating=? WHERE igdb_id = ?";
                $stmt2 = $pdo->prepare($query2);
                $result = $stmt2->execute([$name, $summary, $cover, $date, $rating, $igdb_id]);

                echo "The result of the query is: " . $result["IT490"] . PHP_EOL;
            }

        }
        echo "finished with games" . PHP_EOL;
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
    echo "writing new rating" . PHP_EOL;
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
        $ratings_list = [];
        $query = "SELECT game_id, username, rating, review from game_ratings WHERE game_id = ?;";
        $stmt = $pdo->prepare($query);
        $exec = $stmt->execute([$game_id]);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach($result as $row)
        {
            $rating_individual = [
                "game_id" => $row['game_id'],
                "username" => $row['username'],
                "rating" => $row['rating'],
                "review" => $row['review']
            ];
            $ratings_list[] = $rating_individual;
        }
        $rating_return = [
            "success" => true,
            "ratings" => $ratings_list
        ];
        return $rating_return;


    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());

        echo "Failed to connect to MYSQL" . PHP_EOL;
        return false;
        //return false if not valid
    }
}

function doGetThread($thread_id)
{
    echo "we are gonna try to get thread" . PHP_EOL;
    try
    {
        $pdo = getDatabaseConnection();
        echo "Connected to MySQL db." . PHP_EOL;
        $query = "SELECT thread_id, username, category, title, body, updated_at from forum_threads WHERE thread_id = ?;";
        $stmt = $pdo->prepare($query);
        $exec = $stmt->execute([$thread_id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $thread_info = [
            "username" => $result['username'],
            "category" => $result['category'],
            "title" => $result['title'],
            "body" => $result['body'],
            "updated_at" => $result['updated_at']
        ];

        //get replies

        $query2 = "SELECT * from forum_replies WHERE thread_id = ?;";
        $stmt2 = $pdo->prepare($query2);
        $exec2 = $stmt2->execute([$thread_id]);
        $result2 = $stmt2->fetchAll(\PDO::FETCH_ASSOC);
        $thread_replies = [];
        echo "about to check for replies" . PHP_EOL;
        foreach ($result2 as $row)
        {
            $reply_individual = [
                "reply_id" => $row['reply_id'],
                "username" => $row['username'],
                "body" => $row['body'],
                "updated_at" => $row['updated_at']
            ];
            $thread_replies[] = $reply_individual;
        }
        echo "creating the return" . PHP_EOL;
        $thread_return = [
            "success" => true,
            "thread_info" => $thread_info,
            "thread_replies" => $thread_replies
        ];
        echo "returning the get thread" . PHP_EOL;
        return $thread_return;


    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());

        echo "Failed to connect to MYSQL" . PHP_EOL;
        return false;
        //return false if not valid
    }
}

function doWriteThread($username, $category, $title, $body)
{

    echo "we are gonna try to write thread" . PHP_EOL;
    try
    {
        $pdo = getDatabaseConnection();
        echo "Connected to MySQL db." . PHP_EOL;
        $query = "INSERT INTO forum_threads (username, category, title, body) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([$username, $category, $title, $body]);
        echo "The result of the query is: " . $result["IT490"] . PHP_EOL;

        return true;

    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());

        echo "Failed to connect to MYSQL" . PHP_EOL;
        return false;
    }
}

function doWriteReply($username, $thread_id, $body)
{

    echo "we are gonna try to write a reply" . PHP_EOL;
    try
    {
        $pdo = getDatabaseConnection();
        echo "Connected to MySQL db." . PHP_EOL;
        $query = "INSERT INTO forum_replies (username, thread_id, body) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([$username, $thread_id, $body]);
        echo "The result of the query is: " . $result["IT490"] . PHP_EOL;

        return true;

    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());

        echo "Failed to connect to MYSQL" . PHP_EOL;
        return false;
    }
}

function doAddWatchlist($username, $game_id)
{
    try
    {
        $pdo = getDatabaseConnection();
        echo "Connected to MySQL db." . PHP_EOL;
        $query = "INSERT INTO watchlists (username, game_id) VALUES (?, ?)";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([$username, $game_id]);
        echo "The result of the query is: " . $result["IT490"] . PHP_EOL;

        return true;

    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());

        echo "Failed to connect to MYSQL" . PHP_EOL;
        return false;
        //return false if not valid
    }
}

function doRemoveWatchlist($username, $game_id)
{
    try
    {
        $pdo = getDatabaseConnection();
        echo "Connected to MySQL db." . PHP_EOL;
        $query = "DELETE FROM watchlists WHERE username = ? AND game_id = ?";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([$username, $game_id]);
        echo "The result of the query is: " . $result["IT490"] . PHP_EOL;

        return true;

    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());

        echo "Failed to connect to MYSQL" . PHP_EOL;
        return false;
        //return false if not valid
    }
}

function doListThreads($search)
{

    echo "we are gonna try to list threads" . PHP_EOL;
    echo "the search value is " . $search . PHP_EOL;
    try
    {
        $pdo = getDatabaseConnection();
        echo "Connected to MySQL db." . PHP_EOL;
        $query = "SELECT * from forum_threads WHERE title LIKE CONCAT('%', ?, '%');";
        $stmt = $pdo->prepare($query);
        $exec = $stmt->execute([$search]);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $thread_list = [];
        foreach($result as $row)
        {
            echo "found thread named " . $row['title'] . PHP_EOL;
            $thread_individual = [
                "thread_id" => $row['thread_id'],
                "title" => $row['title'],
                "username" => $row['username'],
                "category" => $row['category'],
                "updated_at" => $row['updated_at']
            ];
            $thread_list[] = $thread_individual;
        }

        $thread_return = [
            "success" => true,
            "thread_list" => $thread_list,
        ];
        return $thread_return;


    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());

        echo "Failed to connect to MYSQL" . PHP_EOL;
        return false;
        //return false if not valid
    }
}

function doGetWatchlist($username)
{
    try
    {
        $pdo = getDatabaseConnection();
        echo "Connected to MySQL db." . PHP_EOL;
        echo "Getting watchlist for "  . $username . PHP_EOL;
        $game_names = [];
        $query = "SELECT game_id from watchlists WHERE username = ?";
        $stmt = $pdo->prepare($query);
        $exec = $stmt->execute([$username]);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach($result as $row)
        {
            $game_id_individual = $row['game_id'];
            //$game_ids[] = $game_id_individual;

            //second query to get game name
            $query2 = "SELECT name from games WHERE igdb_id = ?";
            $stmt2 = $pdo->prepare($query2);
            $exec2 = $stmt2->execute([$game_id_individual]);

            $result2 = $stmt2->fetch(\PDO::FETCH_ASSOC);
            $game_name = $result2['name'];
            $game_names[] = [
                'name' => $game_name,
            'game_id' => $game_id_individual
            ];
        }
        return ($game_names);


    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());

        echo "Failed to connect to MYSQL" . PHP_EOL;
        return false;
        //return false if not valid
    }
}

function doUpdateEmailNotification($username, $email_notif)
{
    try
    {
        $pdo = getDatabaseConnection();
        echo "Connected to MySQL db." . PHP_EOL;
        $query = "UPDATE users SET email_notif = ? WHERE username = ?;";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([(int)$email_notif, $username]);
        echo "The result of the query is: " . $result["IT490"] . PHP_EOL;
        echo "Updated email notifs for user " . $username . " to " . $email_notif . PHP_EOL;
        echo "Updated email notif" . PHP_EOL;
        return true;

    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());

        echo "Failed to connect to MYSQL" . PHP_EOL;
        return false;
        //return false if not valid
    }
}

function doGetEmailNotification($username)
{
    try {
        $pdo = getDatabaseConnection();
        echo "Connected to MySQL db." . PHP_EOL;
        echo "getting email notification for "  . $username . PHP_EOL;
        $game_names = [];
        $query = "SELECT email_notif from users WHERE username = ?";
        $stmt = $pdo->prepare($query);
        $exec = $stmt->execute([$username]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $result_return = [
            "success" => true,
            "email_notification" => $result['email_notif']
        ];
        return($result_return);


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
      case "add_watchlist":
          if(!isset($request["username"]))
          {
              return false;
        }
        return doAddWatchlist($request["username"],$request["game_id"]);
      case "get_watchlist":
          if(!isset($request["username"]))
          {
              return false;
          }
          return doGetWatchlist(
              $request["username"]
          );
      case "remove_watchlist":
          return doRemoveWatchlist($request['username'], $request['game_id']);
      case "forum_get_thread":
          return doGetThread($request["thread_id"]);
      case "forum_create_thread":
          return doWriteThread(
              $request["username"],
              $request["category"],
              $request["title"],
              $request["body"]
          );

      case "forum_list_threads":
          return doListThreads(
              $request["search"]
        );
      case "forum_create_reply":
          echo "case is forum_create_reply" . PHP_EOL;
          return doWriteReply($request['username'], $request['thread_id'], $request['body']);
      case "get_email_notification":
          return doGetEmailNotification($request['username']);
      case "update_email_notification":
          echo "receiving email notif update request" . PHP_EOL;
          return doUpdateEmailNotification($request['username'], $request['email_notif']);
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

