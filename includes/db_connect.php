<?php
// PostgreSQL connection for Render (and other hosts)
// 1) Prefer a single DATABASE_URL (Render's default)
// 2) Otherwise fall back to individual PG* env vars

$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl) {
    $parts = parse_url($databaseUrl);
    $host = $parts['host'] ?? 'localhost';
    $port = $parts['port'] ?? 5432;
    $user = $parts['user'] ?? 'postgres';
    $pass = $parts['pass'] ?? '';
    $db   = ltrim($parts['path'] ?? '/agrisense', '/');
} else {
    $host = getenv('PGHOST') ?: 'localhost';
    $port = getenv('PGPORT') ?: 5432;
    $user = getenv('PGUSER') ?: 'postgres';
    $pass = getenv('PGPASSWORD') ?: '';
    $db   = getenv('PGDATABASE') ?: 'agrisense';
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