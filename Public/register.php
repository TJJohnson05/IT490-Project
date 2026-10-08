<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>
    <h2>Create Account</h2>
    <form action="/register-handler" method="POST">
        <label for="username">Username:</label><br>
        <input type="text" id="username" name="username" required><br><br>

        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email" required><br><br>
        
        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password" required><br><br>

        <label for="confirm-password">Confirm Password:</label><br>
        <input type="password" id="confirm-password" name="confirm-password" required><br><br>
        
        <button type="submit">Register</button>
    </form>
</body>
</html>
