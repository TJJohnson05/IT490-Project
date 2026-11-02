<?php
session_start();

header('Cache-Control: no-store, private');

// User logged in
if (($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}

$username = trim($_GET['username'] ?? '');

if ($username === '') {
    header('Location: browse.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Profile</title>
    <link rel="stylesheet" href="css/styles.css?v=profiles1">
</head>

<body>

<?php require __DIR__ . '/../Partials/Navigation.php'; ?>

<main class="profile-page">

    <h2><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>'s Profile</h2>

    <section class="profile-watchlist">
        <h3>Watchlist</h3>
        <p>This user's saved games will appear here.</p>
    </section>

</main>

</body>
</html>
