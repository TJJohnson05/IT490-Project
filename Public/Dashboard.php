<?php
session_start();

if (empty($_SESSION["logged_in"])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
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