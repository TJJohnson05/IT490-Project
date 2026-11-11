
<?php
//Tyler IT202 repo
session_start();
// User logged in
if (($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../Partials/forum_helpers.php';

$username = $_SESSION['username'] ?? $_SESSION['login_name'] ?? null;

//start off emailnotis
$emailNotification = true;
$emailNotificationError = '';

if ($username !== null) {
    try {
        //DB Request
        $response = forumRequest([

            'type' => 'get_email_notification',
            'username' => $username
        ]);
	error_log("GET EMAIL RESPONSE" . var_export($response, true));
	$username = $_SESSION['username'] ?? $_SESSION['login_name'] ?? null;

        if (is_array($response) &&
        ($response['success'] ?? false)) {
            $emailNotification = (bool)($response['email_notification'] ?? true);
        } else {
            $emailNotificationError = "Unable to load email Notification settings.";
        }
    } catch (Throwable $e) {
        error_log ('Email Noti Error' . $e->getMessage());
        $emailNotificationError = "Unable to load email Noti";
    }
}
header('Cache-Control: no-store, private');
// Prof files 
require_once __DIR__ . '/../Partials/profile_helpers.php';
require_once __DIR__ . '/../path.inc';
require_once __DIR__ . '/../get_host_info.inc';
require_once __DIR__ . '/../rabbitMQLib.inc';

//Watchlist 
$watchlist = [];
$watchlistError = '';

$_SESSION['watchlist_csrf'] ??= bin2hex(random_bytes(32));
if (empty($username)) {
    $watchlistError = 'Unable to identify the logged-in user.';
} else {
    try {
        $dbClient = new rabbitMQClient(
            __DIR__ . '/../testRabbitMQDatabase.ini',
            'databaseServer'
        );
        //DB Request
        $response = $dbClient->send_request([
            'type' => 'get_watchlist',
            'username' => $username
        ]);

        if (is_array($response)) {
            $watchlist = $response;
        } else {
            $watchlistError = 'Unable to load your watchlist.';
        }

    } catch (Throwable $e) {
        error_log('Watchlist lookup failed: ' . $e->getMessage());
        $watchlistError = 'Watchlist service unavailable.';
    }
}

//Profile + Post form
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

        $allowedActions = ['get_profile', 'update_profile', 'change_password'];

        if (!in_array($action, $allowedActions, true)) {
            throw new RuntimeException('Invalid profile action.');
        }

        $password = profileInput('current_password');

        if ($password === '') {
            throw new RuntimeException('Enter your current password.');
        }

        // DB Request
        $request = [
            'type' => $action,
            'login_name' => $_SESSION['login_name'],
            'current_password' => $password
        ];

        //Updated User and email
        if ($action === 'update_profile') {
            $request['username'] = trim(profileInput('username'));
            $request['email'] = trim(profileInput('email'));

            if (!preg_match('/^[a-zA-Z0-9_-]{3,30}$/D', $request['username'])) {
                throw new RuntimeException('Username must be 3-30 letters, numbers, underscores, or hyphens.');
            }

            if (strlen($request['email']) > 255 ||
                !filter_var($request['email'], FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Enter a valid email address.');
            }
        }

        //Password changes 
        if ($action === 'change_password') {
            $request['new_password'] = profileInput('new_password');

            if (strlen($request['new_password']) < 8 ||
                strlen($request['new_password']) > 72) {
                throw new RuntimeException('New password must be 8-72 characters long.');
            }

            if ($request['new_password'] !== profileInput('confirm_password')) {
                throw new RuntimeException('New passwords do not match.');
            }
        }

        //Profile reqeuest 
        $response = profileRequest($request);

        $success = $response['success'] === true;
        $message = $response['message'] ?? ($success ? 'Profile loaded.' : 'Unable to complete request.');

        //Save new profile info 
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

        if ($e instanceof RuntimeException) {
            $message = $e->getMessage();
        }
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

<?php require __DIR__ . '/../Partials/Navigation.php'; ?>
<!-- Watchlist stuff-->
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
                $gameName = is_array($game)
                    ? ($game['name'] ?? $game['game_name'] ?? $game['game_id'] ?? 'Unknown Game')
                    : $game;
                ?>
                    <div class="watchlist-card">

                        <h3>
                            <?= htmlspecialchars((string)$gameName, ENT_QUOTES, 'UTF-8') ?>
                        </h3>

                        <?php
                        $gameId = is_array($game)
                            ? ($game['igdb_id'] ?? $game['game_id'] ?? null)
                            : null;
                        ?>

                        <?php if (filter_var($gameId, FILTER_VALIDATE_INT, [
                            'options' => ['min_range' => 1]
                        ]) !== false): ?>

                        <form action="watchlist_action.php" method="POST">

                        <input type="hidden"
                            name="csrf_token"
                            value="<?= htmlspecialchars($_SESSION['watchlist_csrf'], ENT_QUOTES, 'UTF-8') ?>">

                        <input type="hidden" name="action" value="remove">

                        <input type="hidden"
                            name="game_igdb_id"
                            value="<?= (int)$gameId ?>">

                        <button type="submit">
                            Remove from Watchlist
                        </button>

                        </form>
                        <?php endif; ?>
                    </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
<!-- Email Notification Settings -->
<section class="email-settings">
    <h2>Email Notifications</h2>
    <p>
        Receive email notifications when someone replies
        to your discussion or when a watchlisted game releases.
    </p>

    <?php if ($emailNotificationError !== ''): ?>
        <p>
            <?= htmlspecialchars($emailNotificationError, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>
    <form action="notification_settings_action.php" method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(forumToken(), ENT_QUOTES, 'UTF-8') ?>"
        >
        <label>
            <input
                type="checkbox"
                name="email_notification"
                value="1"
                <?= $emailNotification ? 'checked' : '' ?>
            >
            Enable Email Notifications
        </label>
        <br><br>
        <button type="submit" <?= $emailNotificationError !== '' ? 'disabled' : '' ?>>
            Save Settings
        </button>
    </form>
</section>
<!-- Profile stuff-->
<main class="profile-page">

    <h2>My Profile</h2>

    <?php if ($message !== ''): ?>
        <div class="<?= $success ? 'success' : 'danger' ?>" role="status">
            <?= profileEscape($message) ?>
        </div>
    <?php endif; ?>

    <!-- Loading profile details  -->
    <form method="POST" class="profile-form">
        <h3>Load account details</h3>
        <p>Confirm your current password to load your username and email.</p>

        <input type="hidden" name="csrf"
               value="<?= profileEscape($_SESSION['profile_csrf']) ?>">

        <input type="hidden" name="action" value="get_profile">

        <label for="load-password">Current password</label>
        <input id="load-password" type="password"
               name="current_password"
               autocomplete="current-password" required>

        <button type="submit">Load Profile</button>
    </form>

    <!-- Updating profile details -->
    <?php if (is_array($profile)): ?>

        <p class="profile-public-link">
            <a href="profile_public.php?username=<?= rawurlencode($profile['username']) ?>">
                View public profile
            </a>
        </p>

        <form method="POST" class="profile-form">
            <h3>Account details</h3>

            <input type="hidden" name="csrf"
                   value="<?= profileEscape($_SESSION['profile_csrf']) ?>">

            <input type="hidden" name="action" value="update_profile">

            <label for="username">Username</label>
            <input id="username" type="text" name="username"
                   value="<?= profileEscape($profile['username']) ?>"
                   pattern="[a-zA-Z0-9_-]{3,30}"
                   minlength="3" maxlength="30"
                   autocomplete="username" required>

            <label for="email">Email</label>
            <input id="email" type="email" name="email"
                   value="<?= profileEscape($profile['email']) ?>"
                   maxlength="255" autocomplete="email" required>

            <label for="save-password">Current password</label>
            <input id="save-password" type="password"
                   name="current_password"
                   autocomplete="current-password" required>

            <button type="submit">Save Profile</button>
        </form>

    <?php endif; ?>

    <!-- Changing password details-->
    <form method="POST" class="profile-form" id="password-form">
        <h3>Change password</h3>

        <input type="hidden" name="csrf"
               value="<?= profileEscape($_SESSION['profile_csrf']) ?>">

        <input type="hidden" name="action" value="change_password">

        <label for="current-password">Current password</label>
        <input id="current-password" type="password"
               name="current_password"
               autocomplete="current-password" required>

        <label for="new-password">New password (8-72 characters)</label>
        <input id="new-password" type="password" name="new_password"
               minlength="8" maxlength="72"
               autocomplete="new-password" required>

        <label for="confirm-password">Confirm new password</label>
        <input id="confirm-password" type="password"
               name="confirm_password"
               minlength="8" maxlength="72"
               autocomplete="new-password" required>

        <button type="submit">Change Password</button>
    </form>

</main>

<script>
const newPassword = document.getElementById('new-password');
const confirmation = document.getElementById('confirm-password');
//make sure passswords patch 
function checkMatch() {
    if (newPassword.value !== confirmation.value) {
        confirmation.setCustomValidity('New passwords must match.');
    } else {
        confirmation.setCustomValidity('');
    }
}

newPassword.addEventListener('input', checkMatch);
confirmation.addEventListener('input', checkMatch);
</script>

</body>
</html>
