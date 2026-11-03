<?php
require_once __DIR__ . '/../Partials/forum_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
   http_response_code(405);
   exit('Method not allowed.');
}

//user loggedin
$username = forumUser();

if ($username === null) {
   forumRedirect('login.php');
}

$token = $_POST['csrf_token'] ?? '';

if (
   !is_string($token) ||
   !hash_equals(forumToken(), $token)
) {
   http_response_code(403);
   exit('Invalid form token.');
}

$action = $_POST['action'] ?? '';
$body = trim((string)($_POST['body'] ?? ''));

if (
   strlen($body) > 30000 ||
   mb_strlen($body) > 10000
) {
   http_response_code(400);
   exit('Post is too long.');
}

try {
   //Discussion 
   if ($action === 'create') {

       $title = trim((string)($_POST['title'] ?? ''));

       $category = (string)(
           $_POST['category'] ?? 'General Discussion'
       );

       $allowedCategories = [
           'General Discussion',
           'Game Discussion',
           'Help & Tips',
           'News'
       ];

       if (!in_array($category, $allowedCategories, true)) {
           throw new InvalidArgumentException(
               'Invalid discussion category.'
           );
       }


       if (
           mb_strlen($title) < 5 ||
           mb_strlen($title) > 150 ||
           mb_strlen($body) < 5
       ) {
           throw new InvalidArgumentException(
               'Invalid title or post.'
           );
       }
       //DB Request
       error_log("sending forum create thread");
       $response = forumRequest([
           'type' => 'forum_create_thread',
           'username' => $username,
           'title' => $title,
           'category' => $category,
           'body' => $body
       ]);

   //Discussion reply
   } elseif ($action === 'reply') {

       error_log("Entered Reply");
       $threadId = filter_var(
           $_POST['thread_id'] ?? null,
           FILTER_VALIDATE_INT,
           ['options' => ['min_range' => 1]]
       );

       if (!$threadId || mb_strlen($body) < 1) {
           throw new InvalidArgumentException(
               'Invalid reply.'
           );
       }
       error_log("Sending forum_create_reply");
       //DB Request
       $response = forumRequest([
           'type' => 'forum_create_reply',
           'username' => $username,
           'thread_id' => $threadId,
           'body' => $body
       ]);
       error_log("RabbitMQ Response:");

   } else {

       throw new InvalidArgumentException(
           'Invalid forum action.'
       );
   }
   //Db response 
   if (
       $response !== true &&
       (!is_array($response) || !($response['success'] ?? false))
   ) {
       throw new RuntimeException(
           'Database rejected forum request.'
       );
   }

   //Redirect
if ($action === 'reply') {
   forumRedirect( 'thread.php?id=' . $threadId);
}
   forumRedirect('thread.php?id=' . (int)$response['thread_id'] );

} catch (Throwable $e) {

   error_log(
       'Forum action failed: ' . $e->getMessage()
   );

   http_response_code(400);

   echo 'Unable to save your post. Please try again.';
}
