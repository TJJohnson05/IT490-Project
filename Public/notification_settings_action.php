
<?php

require_once __DIR__ . '/../Partials/forum_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

//User logged in 
$username = forumUser();

if ($username === null) {
    forumRedirect('login.php');
}

$token = $_POST['csrf_token'] ?? '';

if (
    !is_string($token) ||
    !hash_equals(forumToken(), $token)
) {
    http_response_code(403);
    exit('Invalid form token.');
}

//Checkbox field, true when checked 
$emailNotif = isset($_POST['email_notification']);

try {
    //DB Request
    $response = forumRequest([
        'type' => 'update_email_notification',
        'username' => $username,
        'email_notif' => $emailNotif
    ]);
    
    if ($response !== true) {
	throw new RuntimeException('Database rejected email noti update');
	}
    forumRedirect('profile.php');

} catch (Throwable $e) {

    error_log(
        'Email notification update failed: ' . $e->getMessage()
    );
    http_response_code(500);
    echo 'Unable to save email notification settings.';
}
