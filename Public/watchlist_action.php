
<?php
session_start();

if (($_SESSION['logged_in'] ?? false) !== true) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

$search = trim((string)($_POST['search'] ?? ''));
$sort = (string)($_POST['sort'] ?? 'name');

if (!in_array($sort, ['name', 'newest', 'oldest'], true)) {
    $sort = 'name';
}

if (mb_strlen($search) > 100) {
    $search = '';
}

$redirect = 'browse.php?' . http_build_query([
    'search' => $search,
    'sort' => $sort
]);

function finishWatchlist(
    bool $success,
    string $message,
    string $redirect
): void {
    $_SESSION['watchlist_notice'] = [
        'success' => $success,
        'message' => $message
    ];

    header('Location: ' . $redirect, true, 303);
    exit;
}

// Validate the form's CSRF token
$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    !isset($_SESSION['watchlist_csrf']) ||
    !hash_equals($_SESSION['watchlist_csrf'], $token)
) {
    finishWatchlist(
        false,
        'Invalid form token. Please refresh and try again.',
        $redirect
    );
}

// Validate game ID and logged-in username
$gameId = filter_var(
    $_POST['game_igdb_id'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

$username = $_SESSION['username']
    ?? $_SESSION['login_name']
    ?? null;

if (
    $gameId === false ||
    $gameId === null ||
    !is_string($username) ||
    !preg_match('/^[a-zA-Z0-9_-]{3,30}$/D', $username)
) {
    finishWatchlist(
        false,
        'Invalid game or login session.',
        $redirect
    );
}

// RabbitMQ dependencies
require_once __DIR__ . '/../path.inc';
require_once __DIR__ . '/../get_host_info.inc';
require_once __DIR__ . '/../rabbitMQLib.inc';

try {
    // Frontend communicates directly with Database VM
    $dbClient = new rabbitMQClient(
        __DIR__ . '/../testRabbitMQDatabase.ini',
        'databaseServer'
    );

    $response = $dbClient->send_request([
        'type' => 'add_watchlist',
        'username' => $username,
        'game_id' => $gameId
    ]);

    if (
        $response === true
    ) {
        finishWatchlist(
            true,
            'Game added to your watchlist!',
            $redirect
        );
    }

    finishWatchlist(
        false,
        'Could not add game to watchlist. The database may not support this request yet.',
        $redirect
    );

} catch (Throwable $e) {
    error_log('Watchlist add failed: ' . $e->getMessage());

    finishWatchlist(
        false,
        'Unable to contact the watchlist service.',
        $redirect
    );
}
