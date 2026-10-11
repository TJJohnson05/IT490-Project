<?php
//tells us the current page and its nav link
$currentPage = basename($_SERVER["SCRIPT_NAME"] ?? ""); 
?>

<nav class="site-nav" aria-label="Main navigation">
    <div class="nav-container">
        <a class="nav-brand" href="login.php">
            IT490 Project
        </a>
        <!-- Nav links -->
        <div class="nav-links">

            <a href="login.php"
                class="<?php echo $currentPage === 'login.php' ? 'active' : ''; ?>"
                <?php if ($currentPage === 'login.php') echo 'aria-current="page"'; ?>>
                Login    
            </a>
            <a href="register.php"
                class="<?php echo $currentPage === 'register.php' ? 'active' : ''; ?>"
                <?php if ($currentPage === 'register.php') echo 'aria-current="page"'; ?>>
                Register
            </a>

        </div>
    </div>
</nav>