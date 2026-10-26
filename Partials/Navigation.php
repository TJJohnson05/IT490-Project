<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Identify the current page and authentication status.
$currentPage = basename($_SERVER["SCRIPT_NAME"] ?? "");
$isLoggedIn = ($_SESSION["logged_in"] ?? false) === true;
?>

<nav class="site-nav" aria-label="Main navigation">
    <div class="nav-container">
        <a
            class="nav-brand"
            href="<?php echo $isLoggedIn ? 'Dashboard.php' : 'login.php'; ?>"
        >
            IT490 Project
        </a>

        <div class="nav-links">
            <?php if ($isLoggedIn): ?>

                <a
                    href="Dashboard.php"
                    class="<?php echo $currentPage === 'Dashboard.php' ? 'active' : ''; ?>"
                    <?php if ($currentPage === 'Dashboard.php') echo 'aria-current="page"'; ?>>
                    Dashboard
                </a>

                <a href="profile.php"
                   class="<?php echo $currentPage === 'profile.php' ? 'active' : ''; ?>"
                   <?php if ($currentPage === 'profile.php') echo 'aria-current="page"'; ?>>
                    My Profile
                </a>

                <a href="browse.php"
                    class="<?php echo $currentPage === 'browse.php' ? 'active' : ''; ?>">
                    <?php if ($currentPage === 'browse.php') echo 'aria-current="page"'; ?>
                    Browse Games
                </a>

                <form action="logout.php" method="POST" class="nav-logout">
                    <button type="submit">Logout</button>
                </form>
            <?php else: ?>

                <a
                    href="login.php"
                    class="<?php echo $currentPage === 'login.php' ? 'active' : ''; ?>"
                    <?php if ($currentPage === 'login.php') echo 'aria-current="page"'; ?>>
                    Login
                </a>

                <a
                    href="register.php"
                    class="<?php echo $currentPage === 'register.php' ? 'active' : ''; ?>"
                    <?php if ($currentPage === 'register.php') echo 'aria-current="page"'; ?>>
                    Register
                </a>

            <?php endif; ?>
        </div>
    </div>
</nav>