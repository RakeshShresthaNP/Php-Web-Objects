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

    private static $_context = null;

    public static function getContext(): object
    {
        if (self::$_context) {
            return self::$_context;
        }

        list ($dbtype, $host, $user, $pass, $dbname) = unserialize(DB_CON);

        $dsn = $dbtype . ':host=' . $host . ';dbname=' . $dbname;

        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => true,

            // This forces the connection session to UTC
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00'"
        ];

        try {
            self::$_context = new PDO($dsn, $user, $pass, $options);
            self::$_context->exec('SET NAMES utf8');
        } catch (PDOException $ex) {
            throw new Exception($ex->getMessage(), $ex->getCode());
        }

        return self::$_context;
    }
}
