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
        echo "Made it past the resultcount";

        if ($resultCount == 0){
            echo "resultcount is 0";
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
                echo "we are in foreach loop";
                if (password_verify($password, $row['password_hash']))
                {
                    echo "Password verified!";
                    return true;
                }
            }

            echo "Invalid login.";
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

function requestProcessor($request)
{
  echo "received request".PHP_EOL;
  var_dump($request);
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

