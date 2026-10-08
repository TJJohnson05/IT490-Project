<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
    <h2>Login</h2>
    <form action="/login-handler" method="POST">
        <label for="username">Username:</label><br>
        <input type="text" id="username" name="username"><br><br>
        
        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password"><br><br>
        
        <button type="submit">Log In</button>
    </form>
</body>
</html>
<script>
    function validate(form) {
        //TODO 1: implement JavaScript validation (you'll do this on your own towards the end of Milestone1)
        //ensure it returns false for an error and true for success
        let email = form.email.value.trim();
        let password = form.password.value;
        let isValid = true;

        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const usernamePattern = /^[a-z0-9_-]{3,30}$/;

        if (email.includes("@")) {
            if (!emailPattern.test(email)) {
                flash("Email must be a valid email address.", "danger");
                isValid = false;
            }
        } 
        else {
            if (!usernamePattern.test(email)) {
            flash("Username must be 3-30 characters, numbers, underscores, or hyphens only", "danger");
            isValid = false;
            }
        }                
       
        if (password.length < 8) {
            flash("Password must be atleast 8 characters long.", "danger");
            isValid = false;
        }

        if (!isValid) {
            return false;
        }

        return true;
    }
</script>
