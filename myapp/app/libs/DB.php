<?php

/**
 # Copyright Rakesh Shrestha (rakesh.shrestha@gmail.com)
 # All rights reserved.
 #
 # Redistribution and use in source and binary forms, with or without
 # modification, are permitted provided that the following conditions are
 # met:
 #
 # Redistributions must retain the above copyright notice.
 */
declare(strict_types = 1);

final class DB
{

    /**
     *
     * @var PDO|null
     */
    private static $_context = null;

    /**
     * Returns the active PDO connection context.
     * Automatically handles reconnections if the server has "gone away"
     * which is critical for persistent WebSocket processes.
     * * @return PDO
     *
     * @throws Exception
     */
    public static function getContext(): PDO
    {
        // 1. Check if we already have an existing connection
        if (self::$_context instanceof PDO) {
            try {
                // Perform a lightweight check to see if the connection is still alive
                // This prevents "MySQL server has gone away" errors
                self::$_context->query("SELECT 1");
                return self::$_context;
            } catch (PDOException $e) {
                // If the check fails, clear the context to trigger a fresh connection
                echo "♻️ Database connection lost. Re-establishing connection..." . PHP_EOL;
                self::$_context = null;
            }
        }

        // Expected format: serialize(['mysql', 'localhost', 'user', 'pass', 'dbname'])
        list ($dbtype, $host, $user, $pass, $dbname) = unserialize(DB_CON);

        $dsn = $dbtype . ':host=' . $host . ';dbname=' . $dbname;

        // 3. Connection Options
        $options = [
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => true,

            // This forces the connection session to UTC
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, time_zone = '+00:00'"
        ];

        try {
            // 4. Create new PDO instance
            self::$_context = new PDO($dsn, $user, $pass, $options);

            // Re-applying your original initialization commands just to be safe
            self::$_context->exec('SET NAMES utf8mb4');
            self::$_context->exec("SET time_zone = '+00:00'");
        } catch (PDOException $ex) {
            // Rethrow as standard Exception to match your original error handling
            throw new Exception($ex->getMessage(), (int) $ex->getCode());
        }

        return self::$_context;
    }
}
