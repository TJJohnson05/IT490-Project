<?php
session_start();

if (empty($_SESSION["logged_in"])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard</title>

    <!-- Web page configuration and responsive design -->
    <link rel="stylesheet" href="css/styles.css">
</head>

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