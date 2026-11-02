<?php
require_once __DIR__ . '/../Partials/forum_helpers.php';
// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
   http_response_code(405);
   exit('Method not allowed.');
}

// User must be logged in
$username = forumUser();

if ($username === null) {
   forumRedirect('login.php');
}

// Validate CSRF token
$token = $_POST['csrf_token'] ?? '';

if (
   !is_string($token) ||
   !hash_equals(forumToken(), $token)
) {
   http_response_code(403);
   exit('Invalid form token.');
}

// Get submitted action and post body
$action = $_POST['action'] ?? '';
$body = trim((string)($_POST['body'] ?? ''));

// Limit post size
if (
   strlen($body) > 30000 ||
   mb_strlen($body) > 10000
) {
   http_response_code(400);
   exit('Post is too long.');
}

try {
   // CREATE NEW DISCUSSION
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
       $response = forumRequest([
           'type' => 'forum_create_thread',
           'username' => $username,
           'title' => $title,
           'category' => $category,
           'body' => $body
       ]);

   // REPLY TO DISCUSSION
   } elseif ($action === 'reply') {

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
       //DB Request
       $response = forumRequest([
           'type' => 'forum_add_reply',
           'username' => $username,
           'thread_id' => $threadId,
           'body' => $body
       ]);


   } else {


       throw new InvalidArgumentException(
           'Invalid forum action.'
       );
   }

   // DB VM Response 
   if (
       !is_array($response) ||
       !($response['success'] ?? false)
   ) {
       throw new RuntimeException(
           'Database rejected forum request.'
       );
   }

   // Direct to the newly created discussion post 
   forumRedirect(
       'thread.php?id=' . (int)$response['thread_id']
   );

} catch (Throwable $e) {

   error_log(
       'Forum action failed: ' . $e->getMessage()
   );

   http_response_code(400);

   echo 'Unable to save your post. Please try again.';
}
