#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

require_once __DIR__ . "/database.php";

function doLogin($username,$password)
{
    // lookup username in databas
    // check password
    try
    {
    $pdo = getDatabaseConnection();
    echo "Connected to MySQL db." . PHP_EOL;
    
    $stmt = $pdo->query("SELECT * FROM DATABASE() AS db_name WHERE username = '$username' AND password = '$password'");
    $result = $stmt->fetch();
    echo "The result of the query is: " . $result["db_name"] . PHP_EOL;
    
    return true;
    
    } catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    
    echo "Failed to connect to MYSQL" . PHP_EOL;
    return false;
    //return false if not valid
}

function doRegister($username, $email, $password)
{
  echo "registration request received." . PHP_EOL;
  echo "Username: " . $username . PHP_EOL; 
  echo "Email: " . $email . PHP_EOL;
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

  return array("returnCode" => '0', 'message'=>"Server received request and processed");
}

$server = new rabbitMQServer("testRabbitMQ.ini","testServer");

echo "testRabbitMQServer BEGIN".PHP_EOL;
$server->process_requests('requestProcessor');
echo "testRabbitMQServer END".PHP_EOL;
exit();
?>

