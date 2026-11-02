

<?php


require_once __DIR__ . '/../Partials/forum_helpers.php';


// Get thread ID from URL
$threadId = filter_var(
   $_GET['id'] ?? null,
   FILTER_VALIDATE_INT,
   ['options' => ['min_range' => 1]]
);


$thread = null;
$replies = [];
$error = '';


if (!$threadId) {


   http_response_code(400);
   $error = 'Invalid discussion ID.';


} else {


   try {


       $response = forumRequest([
           'type' => 'forum_get_thread',
           'thread_id' => $threadId
       ]);


       if (
           !is_array($response) ||
           !($response['success'] ?? false)
       ) {
           http_response_code(404);
           $error = 'Discussion not found.';


       } else {


           $thread = $response['thread'];
           $replies = $response['replies'] ?? [];
       }


   } catch (Throwable $e) {


       error_log('Thread error: ' . $e->getMessage());
       $error = 'Unable to load discussion.';
   }
}


?>


<!DOCTYPE html>
<html lang="en">


<head>
   <meta charset="UTF-8">
   <meta name="viewport"
         content="width=device-width, initial-scale=1.0">


   <title>Forum Discussion</title>


   <link rel="stylesheet" href="css/styles.css">


   <style>
       .thread-container {
           max-width: 950px;
           margin: 40px auto;
           padding: 20px;
           color: #f1f5f9;
       }


       .thread-container a {
           color: #93c5fd;
       }


       .discussion-card,
       .reply-card {
           background: #1e293b;
           border: 1px solid #334155;
           padding: 25px;
           border-radius: 12px;
           margin: 15px 0;
       }


       .discussion-meta {
           color: #cbd5e1;
           font-size: 14px;
           margin: 12px 0;
       }


       .discussion-body {
           white-space: pre-wrap;
           overflow-wrap: anywhere;
           line-height: 1.7;
       }


       .reply-form {
           margin-top: 30px;
       }


       .reply-form textarea {
           width: 100%;
           box-sizing: border-box;
           min-height: 140px;
           padding: 12px;
           border-radius: 8px;
           border: 1px solid #64748b;
           background: #0f172a;
           color: white;
           resize: vertical;
       }


       .reply-form button {
           margin-top: 15px;
           padding: 12px 20px;
           background: #2563eb;
           color: white;
           border: none;
           border-radius: 8px;
           cursor: pointer;
       }


       .thread-error {
           color: #fca5a5;
       }
   </style>
</head>


<body>


<?php require __DIR__ . '/../Partials/Navigation.php'; ?>


<main class="thread-container">


   <p>
       <a href="forum.php">← All Discussions</a>
   </p>


   <?php if ($error !== ''): ?>


       <p class="thread-error">
           <?= forumEscape($error) ?>
       </p>


   <?php elseif ($thread): ?>


       <article class="discussion-card">


           <h1>
               <?= forumEscape($thread['title']) ?>
           </h1>


           <div class="discussion-meta">


               Category:
               <?= forumEscape($thread['category']) ?>


               <br>


               Posted by:
               <?= forumEscape($thread['username']) ?>


               <br>


               Created:
               <?= forumEscape($thread['created_at']) ?>


           </div>


           <p class="discussion-body">
               <?= forumEscape($thread['body']) ?>
           </p>


       </article>


       <h2>Replies (<?= count($replies) ?>)</h2>


       <?php foreach ($replies as $reply): ?>


           <article class="reply-card">


               <div class="discussion-meta">


                   <?= forumEscape($reply['username']) ?>


                   <br>


                   <?= forumEscape($reply['created_at']) ?>


               </div>


               <p class="discussion-body">
                   <?= forumEscape($reply['body']) ?>
               </p>


           </article>


       <?php endforeach; ?>


       <?php if (forumUser()): ?>


           <div class="reply-form">


               <h2>Post a Reply</h2>


               <form
                   method="POST"
                   action="forum_action.php"
               >


                   <input
                       type="hidden"
                       name="csrf_token"
                       value="<?= forumEscape(forumToken()) ?>"
                   >


                   <input
                       type="hidden"
                       name="action"
                       value="reply"
                   >


                   <input
                       type="hidden"
                       name="thread_id"
                       value="<?= (int)$thread['id'] ?>"
                   >


                   <textarea
                       name="body"
                       minlength="1"
                       maxlength="10000"
                       required
                       placeholder="Write your reply..."
                   ></textarea>


                   <button type="submit">
                       Post Reply
                   </button>


               </form>


           </div>


       <?php else: ?>


           <p>
               <a href="login.php">Log in</a>
               to reply to this discussion.
           </p>


       <?php endif; ?>


   <?php endif; ?>


</main>


</body>
</html>
