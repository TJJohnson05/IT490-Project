<?php
//empty variables for registration vibes 
$username = "";
$email = "";
$message = "";
$messageType = "";
//Start of php validation

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    $hasError = false;

    //Validate Username
    
    if ($username == "") {
        $message = "Username must not be empty.";
        $messageType = "danger";
        $hasError = true;

    } elseif (!preg_match("/^[a-z0-9_-]{3,30}$/", $username)) {
        $message = "Username must be 3-30 characters, lowercase, and contain only letters, numbers, underscores, or hyphens.";
        $messageType = "danger";
        $hasError = true;
    }

    // validate email

    if ($email === "") {
        $message = "Email must not be empty.";
        $messageType = "danger";
        $hasError = true;
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid Email Address.";
        $messageType = "danger";
        $hasError = true;
    }

    //validate password
    if ($password === "") {
        $message = "Password must not be empty.";
        $messageType = "danger";
        $hasError = true;
    } elseif (strlen($password) <8) {
        $message = "Password must be at least 8 characters long.";
        $messageType = "danger";
        $hasError = true;
    }

    // Confirm both passwords match 
    if ($password !== $confirmPassword) {
        $message = "Passwords do not match.";
        $messageType = "danger";
        $hasError = true;
    } 

    if (!$hasError) {

        //Meant to connect to RabbitMQ
        //Backend needs to check whetehr or not username and email already exist 
        //Backend needs to hash and store password in the database

        $message = "Validation successful. Registration is not connected yet.";
        $messageType = "success";
    }
} 
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Register</title>
        <link rel="stylesheet" href="css/styles.css">
        <!--All for responsive design like login page  -->
    </head>

    <body>
        <?php require(__DIR__ . "/../Partials/Navigation.php"); ?>
        <h2>Register</h2>

        <!-- Display PHP validation messages -->
        <?php if ($message !== ""): ?>
            <div class="<?php echo htmlspecialchars($messageType); ?>" role="status">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form onsubmit="return validate(this)" method="POST">

            <div>
                <label for="username">Username:</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    required 
                    minlength="3"
                    maxlength="'30"
                    value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>"
                />
            </div>

            <br>

            <div>
                <label for="email">Email:</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    required
                    value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                />
            </div>

            <br>

            <div>
                <label for="password">Password:</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                />
            </div>

            <br>

            <div>
                <label for = "confirm_password">Confirm Password:</label>
                <input
                    type="passwrord"
                    id="confirm_password"
                    name="confirm_password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                />
            </div>

            <br>

            <button type="submit">Register</button>
        </form>

        <p>
            Already have an account?
            <a href="login.php">Login Here</a>
        </p>

        <!-- JavaScript validation portion -->
         <div id="error-message" role="alert"></div>

         <script>
            function flash(message, type ="danger") {
                const errorBox = document.getElementById("error-message");
                errorBox.textContent = message;
                errorBox.className = type;
            }

            function validate(form) {
                let username = form.elements["username"].value.trim();
                let email = form.elements["email"].value.trim();
                let password = form.elements["password"].value;
                let confirmPassword = form.elements["confirm_password"].value;
                
                let isValid = true;

                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                const usernamePattern = /^[a-z0-9_-]{3,30}$/;

                //remove previous JS messages 

                const errorBox = document.getElementById("error-message")
                errorBox.textContent = "";
                errorBox.className = "";

                if (!usernamePattern.test(username)) {
                    flash("Username must be 3-30 characters, lowercase, and contain only letters, numbers, underscores, or hyphens.", "danger");
                    isValid = false;
                }

                if (!emailPattern.test(email)) {
                    flash("Email must be a valid email address.", "danger");
                    isValid = false;
                }

                if (password.length < 8) {
                    flash("Password must be at least 8 characters long.", "danger");
                    isValid = false;
                }

                if (!password !== confirmPassword) {
                    flash("Passwords do not match.", "danger");
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