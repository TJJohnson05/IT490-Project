
<?php
require_once __DIR__ . '/../Partials/forum_helpers.php';

//Search shows up in url 
$search = trim((string)($_GET['q'] ?? ''));
$search = mb_substr($search, 0, 100);

$threads = [];
$error = '';

try {
    //DB request
    $response = forumRequest([
        'type' => 'forum_list_threads',
        'search' => $search
    ]);

    if (!is_array($response) || !($response['success'] ?? false)) {
        throw new RuntimeException('Unable to load discussions.');
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Forum</title>

    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/forum.css">
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
            <a href="login.php">Log in to post</a>
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

    <!-- Forum thread -->
    <?php if ($error !== ''): ?>

        <p class="forum-error"><?= forumEscape($error) ?></p>

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
                    <p>Category: <?= forumEscape($thread['category']) ?></p>
                    <p>Posted by: <?= forumEscape($thread['username']) ?></p>
                    <p>Replies: <?= (int)$thread['reply_count'] ?></p>
                    <p>Created: <?= forumEscape($thread['created_at']) ?></p>
                </div>

            </article>
        <?php endforeach; ?>

    <?php endif; ?>

</main>

</body>
</html>
