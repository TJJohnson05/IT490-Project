<?php
session_start();
if (($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}
header('Cache-Control: no-store, private');
require_once __DIR__ . '/../Partials/profile_helpers.php';

// logged in users watchlist 

// Retrieve the logged-in user's watchlist
$watchlist = [];
$watchlistError = '';

$username = $_SESSION['username']
    ?? $_SESSION['login_name']
    ?? null;

if (!is_string($username) || $username === '') {
    $watchlistError = 'Unable to identify the logged-in user.';
} else {
    try {
        require_once __DIR__ . '/../path.inc';
        require_once __DIR__ . '/../get_host_info.inc';
        require_once __DIR__ . '/../rabbitMQLib.inc';

        $dbClient = new rabbitMQClient(
            __DIR__ . '/../testRabbitMQDatabase.ini',
            'databaseServer'
        );

        $response = $dbClient->send_request([
            'type' => 'get_watchlist',
            'username' => $username
        ]);

        if (
            is_array($response) === true 
        ) {
            $watchlist = $response;
        } else {
            $watchlistError = 'Unable to load your watchlist.';
        }

    } catch (Throwable $e) {
        error_log('Watchlist lookup failed: ' . $e->getMessage());
        $watchlistError = 'Watchlist service unavailable.';
    }
}

// The existing login returns only true, so reverify the password at the worker.
// Never accept an account ID or account selector from a submitted form.
$_SESSION['profile_csrf'] ??= bin2hex(random_bytes(32));
$message = '';
$success = false;
$profile = $_SESSION['profile_details'] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!hash_equals($_SESSION['profile_csrf'], profileInput('csrf'))) {
            throw new RuntimeException('Your form expired. Refresh the page and try again.');
        }
        $action = profileInput('action');
        if (!in_array($action, ['get_profile', 'update_profile', 'change_password'], true)) {
            throw new RuntimeException('Invalid profile action.');
        }
        $password = profileInput('current_password');
        if ($password === '') {
            throw new RuntimeException('Enter your current password.');
        }
        $request = ['type' => $action, 'login_name' => $_SESSION['login_name'],
                    'current_password' => $password];
        if ($action === 'update_profile') {
            $request['username'] = trim(profileInput('username'));
            $request['email'] = trim(profileInput('email'));
            if (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/D', $request['username'])) {
                throw new RuntimeException('Username must be 3–30 letters, numbers, underscores, or hyphens.');
            }
            if (strlen($request['email']) > 255 || !filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Enter a valid email address.');
            }
        }
        if ($action === 'change_password') {
            $request['new_password'] = profileInput('new_password');
            if (strlen($request['new_password']) < 8 || strlen($request['new_password']) > 72) {
                throw new RuntimeException('New password must be 8–72 bytes long.');
            }
            if ($request['new_password'] !== profileInput('confirm_password')) {
                throw new RuntimeException('New passwords do not match.');
            }
        }
        $response = profileRequest($request);
        $success = $response['success'] === true;
        $message = $response['message'] ?? ($success ? 'Profile loaded.' : 'Unable to complete request.');
        if ($success && isset($response['profile'])) {
            $profile = $response['profile'];
            $_SESSION['profile_details'] = $profile;
            $_SESSION['login_name'] = $profile['username'];
        }
        if ($success) {
            session_regenerate_id(true);
            $_SESSION['profile_csrf'] = bin2hex(random_bytes(32));
        }
    } catch (Throwable $e) {
        $message = 'Unable to contact the profile service. Check that the database worker is running.';
        if ($e instanceof RuntimeException) { $message = $e->getMessage(); }
        // Do not log requests: they contain passwords.
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="css/styles.css?v=profiles1">
</head>
<body>
<section class="profile-watchlist">
    <h2>My Watchlist</h2>
    <?php if ($watchlistError !== ''): ?>
        <p><?= htmlspecialchars($watchlistError) ?></p>
    <?php elseif (empty($watchlist)): ?>
        <p>You haven't added any games yet.</p>
        <a href="browse.php">Browse Games</a>
    <?php else: ?>
        <p><?= count($watchlist) ?> games saved</p>
        <div class="watchlist-grid">
            <?php foreach ($watchlist as $game): ?>
                <?php
                    $name = htmlspecialchars(
                        (string)(
                            is_array($game)
                                ? ($game['name'] ?? $game['game_name'] ?? $game['game_id'] ?? 'Unknown Game')
                                : $game
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                ?>

                <div class="watchlist-card">
                    <h3><?= $name ?></h3>
                </div>

            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../Partials/Navigation.php'; ?>
<main class="profile-page">
    <h2>My Profile</h2>
    <?php if ($message !== ''): ?>
        <div class="<?= $success ? 'success' : 'danger' ?>" role="status"><?= profileEscape($message) ?></div>
    <?php endif; ?>
    <!-- No password is saved in the session or prefilled in the HTML. -->
    <form method="POST" class="profile-form">
        <h3>Load account details</h3>
        <p>Confirm your current password to load your username and email.</p>
        <input type="hidden" name="csrf" value="<?= profileEscape($_SESSION['profile_csrf']) ?>">
        <input type="hidden" name="action" value="get_profile">
        <label for="load-password">Current password</label>
        <input id="load-password" type="password" name="current_password" autocomplete="current-password" required>
        <button type="submit">Load Profile</button>
    </form>
    <?php if (is_array($profile)): ?>
        <p class="profile-public-link"><a href="profile_public.php?username=<?= rawurlencode($profile['username']) ?>">View public profile</a></p>
        <form method="POST" class="profile-form">
            <h3>Account details</h3>
            <input type="hidden" name="csrf" value="<?= profileEscape($_SESSION['profile_csrf']) ?>">
            <input type="hidden" name="action" value="update_profile">
            <label for="username">Username</label>
            <input id="username" type="text" name="username" value="<?= profileEscape($profile['username']) ?>" pattern="[a-zA-Z0-9_-]{3,30}" minlength="3" maxlength="30" autocomplete="username" required>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="<?= profileEscape($profile['email']) ?>" maxlength="255" autocomplete="email" required>
            <label for="save-password">Current password</label>
            <input id="save-password" type="password" name="current_password" autocomplete="current-password" required>
            <button type="submit">Save Profile</button>
        </form>
    <?php endif; ?>
    <form method="POST" class="profile-form" id="password-form">
        <h3>Change password</h3>
        <input type="hidden" name="csrf" value="<?= profileEscape($_SESSION['profile_csrf']) ?>">
        <input type="hidden" name="action" value="change_password">
        <label for="current-password">Current password</label>
        <input id="current-password" type="password" name="current_password" autocomplete="current-password" required>
        <label for="new-password">New password (8–72 bytes)</label>
        <input id="new-password" type="password" name="new_password" minlength="8" maxlength="72" autocomplete="new-password" required>
        <label for="confirm-password">Confirm new password</label>
        <input id="confirm-password" type="password" name="confirm_password" minlength="8" maxlength="72" autocomplete="new-password" required>
        <button type="submit">Change Password</button>
    </form>
</main>
<script>
// Browser validation helps with typos; PHP and the worker validate again.
const newPassword = document.getElementById('new-password');
const confirmation = document.getElementById('confirm-password');
function checkMatch() {
    confirmation.setCustomValidity(newPassword.value === confirmation.value ? '' : 'New passwords must match.');
}
newPassword.addEventListener('input', checkMatch);
