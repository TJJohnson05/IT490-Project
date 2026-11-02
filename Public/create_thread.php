
<?php
require_once __DIR__ . '/../Partials/forum_helpers.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Discussion</title>

    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/create_thread.css">
</head>

<body>

<?php require __DIR__ . '/../Partials/Navigation.php'; ?>

<main class="create-container">

    <p><a href="forum.php">← Back to Forum</a></p>

    <h1>Start a Discussion</h1>

    <?php if (!forumUser()): ?>

        <p>You must <a href="login.php">log in</a> to create a discussion.</p>

    <?php else: ?>

        <form action="forum_action.php" method="POST" class="create-form">

            <input type="hidden" name="csrf_token"
                   value="<?= forumEscape(forumToken()) ?>">

            <input type="hidden" name="action" value="create">

            <label for="title">Discussion Title</label>
            <input type="text" id="title" name="title"
                   minlength="5" maxlength="150" required
                   placeholder="What would you like to discuss?">

            <label for="category">Category</label>
            <select id="category" name="category">
                <option value="General Discussion">General Discussion</option>
                <option value="Game Discussion">Game Discussion</option>
                <option value="Help & Tips">Help & Tips</option>
                <option value="News">Gaming News</option>
            </select>

            <label for="body">Your Post</label>
            <textarea id="body" name="body"
                      minlength="5" maxlength="10000" required
                      placeholder="Write your discussion here..."></textarea>

            <button type="submit">Publish Discussion</button>

        </form>

    <?php endif; ?>

</main>

</body>
</html>
