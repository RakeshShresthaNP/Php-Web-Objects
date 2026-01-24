<?php
declare(strict_types = 1);

// 1. Connection Logic
function db()
{
    static $pdo;
    if (! $pdo) {
        $pdo = new PDO("mysql:host=localhost;dbname=testorm", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $pdo;
}

// 2. Test Runner
function it(string $desc, callable $fn)
{
    echo str_pad($desc, 50, ".");
    try {
        $fn();
        echo "✅\n<br>";
    } catch (Throwable $e) {
        echo "❌\n   ERROR: {$e->getMessage()}\n<br>";
    }
}
