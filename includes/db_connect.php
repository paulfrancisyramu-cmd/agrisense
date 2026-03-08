<?php
// PostgreSQL connection for Render (and other hosts)

// Load .env file if it exists
if (file_exists(__DIR__ . '/../agrisense.env')) {
    $lines = file(__DIR__ . '/../agrisense.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue; // Skip comments
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($value);
        putenv(trim($key) . "=" . trim($value));
    }
}

// 1) Prefer a single DATABASE_URL 
$databaseUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? '');

if ($databaseUrl) {
    $parts = parse_url($databaseUrl);
    $host = $parts['host'] ?? 'localhost';
    $port = $parts['port'] ?? 5432;
    $user = $parts['user'] ?? 'postgres';
    $pass = $parts['pass'] ?? '';
    $db   = ltrim($parts['path'] ?? '/agrisense', '/');
} else {
    // Fall back to individual env vars
    $host = getenv('PGHOST') ?: ($_ENV['PGHOST'] ?? 'localhost');
    $port = getenv('PGPORT') ?: ($_ENV['PGPORT'] ?? 5432);
    $user = getenv('PGUSER') ?: ($_ENV['PGUSER'] ?? 'postgres');
    $pass = getenv('PGPASSWORD') ?: ($_ENV['PGPASSWORD'] ?? '');
    $db   = getenv('PGDATABASE') ?: ($_ENV['PGDATABASE'] ?? 'agrisense');
}

$dsn = "pgsql:host={$host};port={$port};dbname={$db};";

try {
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>
