#!/usr/bin/php
<?php
echo "test mail begin" . PHP_EOL;

$message = "This is a test email. Check it out, baby!!!!!!!";
$message = wordwrap($message, 70, "\r\n");
$headers = 'From: noreply@games-db.real' . '\r\n' .
    'Reply-To: noreply@games-db.real' . '\r\n' .
    'X-Mailer: PHP/' . phpversion();
mail('metallicminiboss@gmail.com', 'TEST EMAIL!!', $message, $headers);





?>
