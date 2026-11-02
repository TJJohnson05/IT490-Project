

<?php


// Start the session if it is not already running
if (session_status() !== PHP_SESSION_ACTIVE) {
   session_start();
}


// Escape text before displaying it on the website
function forumEscape($value): string
{
   return htmlspecialchars(
       (string)$value,
       ENT_QUOTES,
       'UTF-8'
   );
}


// Get the logged-in username
function forumUser(): ?string
{
   if (($_SESSION['logged_in'] ?? false) !== true) {
       return null;
   }


   $username = $_SESSION['username']
       ?? $_SESSION['login_name']
       ?? null;


   if (
       !is_string($username) ||
       !preg_match('/^[a-zA-Z0-9_-]{3,30}$/D', $username)
   ) {
       return null;
   }


   return $username;
}


// Generate a CSRF token for submitting forum forms
function forumToken(): string
{
   if (!isset($_SESSION['forum_csrf'])) {
       $_SESSION['forum_csrf'] = bin2hex(random_bytes(32));
   }


   return $_SESSION['forum_csrf'];
}


// Send requests directly to the Database VM
function forumRequest(array $request)
{
   require_once __DIR__ . '/../path.inc';
   require_once __DIR__ . '/../get_host_info.inc';
   require_once __DIR__ . '/../rabbitMQLib.inc';


   $client = new rabbitMQClient(
       __DIR__ . '/../testRabbitMQDatabase.ini',
       'databaseServer'
   );


   return $client->send_request($request);
}


// Redirect to another page
function forumRedirect(string $path): void
{
   header('Location: ' . $path, true, 303);
   exit;
}
