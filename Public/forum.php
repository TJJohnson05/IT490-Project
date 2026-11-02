

<?php


require_once __DIR__ . '/../Partials/forum_helpers.php';


$search = trim((string)($_GET['q'] ?? ''));
$search = mb_substr($search, 0, 100);


$threads = [];
$error = '';


try {
   $response = forumRequest([
       'type' => 'forum_list_threads',
       'search' => $search
   ]);


   if (
       !is_array($response) ||
       !($response['success'] ?? false)
   ) {
       throw new RuntimeException(
           'Unable to load discussions.'
       );
   }


   $threads = $response['threads'] ?? [];


} catch (Throwable $e) {
   error_log('Forum error: ' . $e->getMessage());


   $error = 'Unable to load forum discussions.';
}


?>


<!DOCTYPE html>
<html lang="en">


<head>
   <meta charset="UTF-8">
   <meta name="viewport"
         content="width=device-width, initial-scale=1.0">


   <title>Community Forum</title>


   <link rel="stylesheet" href="css/styles.css">


   <style>
       .forum-container {
           max-width: 1100px;
           margin: 40px auto;
           padding: 20px;
           color: #f1f5f9;
       }


       .forum-header {
           display: flex;
           justify-content: space-between;
           align-items: center;
           gap: 20px;
           flex-wrap: wrap;
       }


       .forum-btn {
           background: #2563eb;
           color: white;
           padding: 12px 20px;
           border-radius: 8px;
           text-decoration: none;
           display: inline-block;
       }


       .forum-search {
           margin: 25px 0;
       }


       .forum-search input {
           width: 100%;
           padding: 12px;
           border-radius: 8px;
           border: 1px solid #64748b;
           background: #1e293b;
           color: white;
           box-sizing: border-box;
       }


       .thread-card {
           background: #1e293b;
           border: 1px solid #334155;
           border-radius: 12px;
           padding: 20px;
           margin-bottom: 15px;
       }


       .thread-card h2 {
           margin: 0 0 12px;
       }


       .thread-card a {
           color: #93c5fd;
           text-decoration: none;
       }


       .thread-card a:hover {
           text-decoration: underline;
       }


       .thread-info {
           color: #cbd5e1;
           font-size: 14px;
       }


       .forum-error {
           color: #fca5a5;
       }
   </style>
</head>


<body>


<?php require __DIR__ . '/../Partials/Navigation.php'; ?>


<main class="forum-container">


   <div class="forum-header">


       <h1>Community Forum</h1>


       <?php if (forumUser()): ?>


           <a href="create_thread.php" class="forum-btn">
               + New Discussion
           </a>


       <?php else: ?>


           <a href="login.php">
               Log in to post
           </a>


       <?php endif; ?>


   </div>


   <form method="GET" class="forum-search">


       <input
           type="search"
           name="q"
           maxlength="100"
           placeholder="Search discussions..."
           value="<?= forumEscape($search) ?>"
       >


   </form>


   <?php if ($error !== ''): ?>


       <p class="forum-error">
           <?= forumEscape($error) ?>
       </p>


   <?php elseif (empty($threads)): ?>


       <p>No discussions found. Start a new discussion!</p>


   <?php else: ?>


       <?php foreach ($threads as $thread): ?>


           <article class="thread-card">


               <h2>
                   <a href="thread.php?id=<?= (int)$thread['id'] ?>">
                       <?= forumEscape($thread['title']) ?>
                   </a>
               </h2>


               <div class="thread-info">


                   Category:
                   <?= forumEscape($thread['category']) ?>


                   <br>


                   Posted by:
                   <?= forumEscape($thread['username']) ?>


                   <br>


                   Replies:
                   <?= (int)$thread['reply_count'] ?>


                   <br>


                   Created:
                   <?= forumEscape($thread['created_at']) ?>


               </div>


           </article>


       <?php endforeach; ?>


   <?php endif; ?>


</main>


</body>
</html>
