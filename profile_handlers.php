<?php
/**
 * DATABASE VM ONLY. Include from the existing database worker.
 * Existing schema: users(id, username, email, password_hash, created_at).
 * Do not log these requests: current/new passwords travel in the payload.
 */
function handleProfileRequest(PDO $pdo, array $request): array
{
    // function for error responses and reading the action that the frontend is requesting 
    $fail = static fn(string $message): array => ['success' => false, 'message' => $message];
    $text = static fn(string $key): string => isset($request[$key]) && is_string($request[$key]) ? $request[$key] : '';
    $type = $text('type');

    // start of db exceptions
    //Looking for public username in db
    try {
        if ($type === 'get_public_profile') {
            $username = trim($text('username'));
            if (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/D', $username)) {
                return $fail('Invalid username.');
            }
            // Explicitly exclude email, password hash, and internal account ID.
            $stmt = $pdo->prepare('SELECT username FROM users WHERE username = :username LIMIT 1');
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ? ['success' => true, 'profile' => $user]
                         : ['success' => false, 'not_found' => true, 'message' => 'User not found.'];
        }
        // Includes the private info like password and updating your profile by changing password username or email
        if (!in_array($type, ['get_profile', 'update_profile', 'change_password'], true)) {
            return $fail('Unsupported profile action.');
        }
        $login = $text('login_name');
        $password = $text('current_password');
        if ($login === '' || $password === '') {
            return $fail('Current password is required.');
        }
        // Same username-or-email convention as the existing login page.
        $column = str_contains($login, '@') ? 'email' : 'username';
        $stmt = $pdo->prepare("SELECT id, username, email, password_hash FROM users WHERE $column = :login LIMIT 1");
        $stmt->execute([':login' => $login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        // Makes sure password and account exist, rejects if not so 
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return $fail('Account or current password is incorrect.');
        }
        //Now if everything is correct in terms of right user you can now update the profile in the db 
        if ($type === 'update_profile') {
            $username = trim($text('username'));
            $email = trim($text('email'));
            if (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/D', $username)) {
                return $fail('Username must be 3–30 letters, numbers, underscores, or hyphens.');
            }
            if (strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $fail('Enter a valid email address.');
            }
            $stmt = $pdo->prepare('UPDATE users SET username = :username, email = :email WHERE id = :id AND password_hash = :old_hash');
            $stmt->execute([':username' => $username, ':email' => $email, ':id' => $user['id'], ':old_hash' => $user['password_hash']]);
            if ($stmt->rowCount() === 0 && ($username !== $user['username'] || $email !== $user['email'])) {
                return $fail('Account changed during this request. Reload your profile.');
            }
            $user['username'] = $username;
            $user['email'] = $email;
        }
        if ($type === 'change_password') {
            $new = $text('new_password');
            if (strlen($new) < 8 || strlen($new) > 72) {
                return $fail('New password must be 8–72 characters long.');
            }
            $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id AND password_hash = :old_hash');
            $stmt->execute([':hash' => password_hash($new, PASSWORD_DEFAULT), ':id' => $user['id'], ':old_hash' => $user['password_hash']]);
            if ($stmt->rowCount() !== 1) {
                return $fail('Account changed during this request. Try again.');
            }
        }
        // simply letting you know the changes were a success
        return ['success' => true,
                'message' => match ($type) {
                    'update_profile' => 'Profile saved.',
                    'change_password' => 'Password updated successfully.',
                    default => 'Profile loaded.'
                },
                'profile' => ['username' => $user['username'], 'email' => $user['email']]];
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            return $fail('That username or email is already in use.');
        }
        return $fail('Unable to save or load the profile.');
    }
}