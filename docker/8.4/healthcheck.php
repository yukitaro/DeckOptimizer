<?php
// healthcheck.php - exit 0 if writable and DB reachable
$status = 0;
$messages = [];

if (!is_writable('/var/www/html/bootstrap/cache')) {
    $messages[] = 'bootstrap_not_writable';
    $status = 2;
}
if (!is_writable('/var/www/html/storage')) {
    $messages[] = 'storage_not_writable';
    $status = 2;
}

if ($status === 0) {
    // Try quick PDO connection (use env or fallback host mysql)
    $host = getenv('DB_HOST') ?: 'mysql';
    $db   = getenv('DB_DATABASE') ?: 'deckoptimizer';
    $user = getenv('DB_USERNAME') ?: 'deckuser';
    $pass = getenv('DB_PASSWORD') ?: '';
    try {
        $pdo = new PDO("mysql:host={$host};dbname={$db}", $user, $pass, [PDO::ATTR_TIMEOUT => 3]);
        $messages[] = 'db_ok';
    } catch (Exception $e) {
        $messages[] = 'db_fail';
        $status = 3;
    }
}

echo implode(';', $messages);
exit($status);
