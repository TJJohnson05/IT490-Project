
<?php
$email = "";
$message = "";
$messageType = "";

// PHP validation
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $hasError = false;

    if (empty($email)) {
        $message = "Email/Username must not be empty.";
        $messageType = "danger";
        $hasError = true;
    }

    if (str_contains($email, "@")) {

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Checks email validation
            $message = "Invalid email address.";
            $messageType = "danger";
            $hasError = true;
        }

    } else {

        if (!preg_match("/^[a-z0-9_-]{3,30}$/", $email)) {
            $message = "Username must be lowercase, alphanumeric, and can only contain _ or -.";
            $messageType = "danger";
            $hasError = true;
        }
    }

    if ($password === "") {
        $message = "Password must not be empty.";
        $messageType = "danger";
        $hasError = true;
    }

    if (!$hasError) {

        // Need to connect to RabbitMQ authentication later
        $message = "Validation successful. Authentication is not connected yet.";
        $messageType = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login</title>

    <!-- Web page configuration and responsive design -->
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>

    <?php
    require(__DIR__ . "/../Partials/Navigation.php");
    ?>

    <h2>Login</h2>

    <!-- Display PHP validation messages -->
    <?php if ($message !== ""): ?>
        <div class="<?php echo htmlspecialchars($messageType); ?>" role="status">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form onsubmit="return validate(this)" method="POST">

        <label for="email">Email or Username</label>

        <input
            type="text"
            id="email"
            name="email"
            required
            value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
        />

        <br><br>

        <label for="password">Password:</label>

        <input
            type="password"
            id="password"
            name="password"
            required
        />

        <br><br>

        <button type="submit">Log In</button>

    </form>

    <p>
        Don't have an account?
        <a href="register.php">Register Here</a>
    </p>

    <!-- JavaScript error messages -->
    <div id="error-message" role="alert"></div>

    <script>

        function flash(message, type = "danger") {

            const errorBox = document.getElementById("error-message");

            errorBox.textContent = message;
            errorBox.className = type;
        }

        function validate(form) {

            let email = form.email.value.trim();
            let password = form.password.value;
            let isValid = true;

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const usernamePattern = /^[a-z0-9_-]{3,30}$/;

            // Clear previous JavaScript messages
            const errorBox = document.getElementById("error-message");
            errorBox.textContent = "";
            errorBox.className = "";

            if (email.includes("@")) {

                if (!emailPattern.test(email)) {
                    flash("Email must be a valid email address.", "danger");
                    isValid = false;
                }

            } else {

                if (!usernamePattern.test(email)) {
                    flash(
                        "Username must be 3-30 characters, numbers, underscores, or hyphens only.",
                        "danger"
                    );
                    isValid = false;
                }
            }

            if (password.length === 0) {
                flash("Password must not be empty.", "danger");
                isValid = false;
            }

            if (!isValid) {
                return false;
            }

            return true;
        }

    </script>

</body>
</html>
