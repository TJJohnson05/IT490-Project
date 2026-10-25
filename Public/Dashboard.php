<?php
session_start();

// Only logged in users
if (($_SESSION["logged_in"] ?? false) !== true) {
    header("Location: login.php");
    exit;
}

header("Cache-Control: no-store, private");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <?php require __DIR__ . "/../Partials/Navigation.php"; ?>

   
    <main>
        <h2>
            Welcome,
            <?php
            echo htmlspecialchars(
                $_SESSION["login_name"] ?? "User",
                ENT_QUOTES,
                "UTF-8"
            );
            ?>!
        </h2
    </main>
</body>
</html>