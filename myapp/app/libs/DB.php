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

        try {
            self::$_context = new PDO($dsn, $user, $pass);
            self::$_context->exec('SET NAMES utf8');
            self::$_context->setAttribute(PDO::ATTR_PERSISTENT, true);
            self::$_context->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            self::$_context->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$_context->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        } catch (PDOException $ex) {
            throw new Exception($ex->getMessage(), $ex->getCode());
        }

        return self::$_context;
    }
}
