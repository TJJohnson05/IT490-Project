<?php
require_once __DIR__ . '/../Partials/forum_helpers.php';

//thread id
$threadId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$thread = null;
$replies = [];
$error = '';

if (!$threadId) {
    http_response_code(400);
    $error = 'Invalid discussion ID.';
} else {
    try {
        //DB Request
        $response = forumRequest([
            'type' => 'forum_get_thread',
            'thread_id' => $threadId
        ]);

        if (!is_array($response) || !($response['success'] ?? false)) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum Discussion</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/thread.css">
</head>
<body>
<?php require __DIR__ . '/../Partials/Navigation.php'; ?>

<main class="thread-container">
    <p><a href="forum.php">← All Discussions</a></p>

    <?php if ($error !== ''): ?>
        <p class="thread-error"><?= forumEscape($error) ?></p>
    <?php elseif ($thread): ?>
        <!--Main-->
        <article class="discussion-card">
            <h1><?= forumEscape($thread['title']) ?></h1>
            <div class="discussion-meta">
                Category: <?= forumEscape($thread['category']) ?><br>
                Posted by: <?= forumEscape($thread['username']) ?><br>
                Created: <?= forumEscape($thread['created_at']) ?>
            </div>
            <p class="discussion-body"><?= forumEscape($thread['body']) ?></p>
        </article>

        <h2>Replies (<?= count($replies) ?>)</h2>
        <?php foreach ($replies as $reply): ?>
            <article class="reply-card">
                <div class="discussion-meta">
                    <?= forumEscape($reply['username']) ?><br>
                    <?= forumEscape($reply['created_at']) ?>
                </div>
                <p class="discussion-body"><?= forumEscape($reply['body']) ?></p>
            </article>
        <?php endforeach; ?>

        <!--logged in users-->
        <?php if (forumUser()): ?>
            <div class="reply-form">
                <h2>Post a Reply</h2>
                <form method="POST" action="forum_action.php">
                    <input type="hidden" name="csrf_token" value="<?= forumEscape(forumToken()) ?>">
                    <input type="hidden" name="action" value="reply">
                    <input type="hidden" name="thread_id" value="<?= (int)$thread['id'] ?>">
                    <textarea name="body" minlength="1" maxlength="10000" required placeholder="Write your reply..."></textarea>
                    <button type="submit">Post Reply</button>
                </form>
            </div>
        <?php else: ?>
            <p><a href="login.php">Log in</a> to reply to this discussion.</p>
        <?php endif; ?>
    <?php endif; ?>
</main>
</body>
</html>
