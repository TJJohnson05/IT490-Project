<?php
session_start();

if (empty($_SESSION["logged_in"] ?? false) !== true) {
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

    <!-- Web page configuration and responsive design -->
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <form class="search-bar" action="" method="GET" role="search">
        <label for="search">Search</label>

        <div class="search-row">
            <input
                type="search"
                id="search"
                name="q"
                placeholder="Search..."
            >

            <button type="submit">Search</button>
        </div>
    </form>
</body>
<h2>
    <form action="logout.php" method="POST">
        <button type="submit">Logout</button>
    </form>
    Welcome,
    <?php echo htmlspecialchars(
        $_SESSION["login_name"] ?? "User",
        ENT_QUOTES,
        "UTF-8"
    ); ?>!
</h2>
</html>