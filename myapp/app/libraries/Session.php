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
final class Session_Native
{

    public function __construct()
    {
        @session_start();

        if (SESS_TIMEOUT)
            $this->_verifyInactivity(SESS_TIMEOUT);
    }

    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function get($key)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : '';
    }

    public function getId()
    {
        return session_id();
    }

    public function delete($key)
    {
        unset($_SESSION[$key]);
    }

    public function destroy()
    {
        session_destroy();
    }

    private function _verifyInactivity($maxtime)
    {
        if (! $this->get('activity_time')) {
            $this->set('activity_time', time());
        }

        if ((time() - $this->get('activity_time')) > $maxtime) {
            $this->destroy();
        } else {
            $this->set('activity_time', time());
        }
    }
}

final class Session_Database
{

    private $_db = null;

    public function __construct()
    {
        $this->_db = DB::getContext();
        session_set_save_handler(array(
            &$this,
            "_open"
        ), array(
            &$this,
            "_close"
        ), array(
            &$this,
            "_read"
        ), array(
            &$this,
            "_write"
        ), array(
            &$this,
            "_destroy"
        ), array(
            &$this,
            "_gc"
        ));
        @session_start();
    }

    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function get($key)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : '';
    }

    public function getId()
    {
        return session_id();
    }

    public function delete($key)
    {
        unset($_SESSION[$key]);
    }

    public function _open()
    {
        if ($this->_db) {
            return true;
        }
        return false;
    }

    public function _close()
    {
        return true;
    }

    public function _read($id)
    {
        $stmt = $this->_db->prepare('SELECT data FROM sys_sessions WHERE id = ? ');
        $stmt->bindValue(1, $id);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch();
            return $row->data;
        } else {
            return '';
        }
    }

    public function _write($id, $data)
    {
        $access = time();

        $stmt = $this->_db->prepare('REPLACE INTO sys_sessions VALUES (?, ?, ?) ');
        $stmt->bindValue(1, $id);
        $stmt->bindValue(2, $data);
        $stmt->bindValue(3, $access);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function _destroy($id)
    {
        $stmt = $this->_db->prepare('DELETE FROM sys_sessions WHERE id = ? ');
        $stmt->bindValue(1, $id);

        session_regenerate_id(TRUE);

        $_SESSION = array();

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function _gc($max = 0)
    {
        $old = time() - $max;

        // Fixed SQL syntax: DELETE doesn't use * (that's SELECT syntax)
        $stmt = $this->_db->prepare('DELETE FROM sys_sessions WHERE last_accessed < ? ');
        $stmt->bindValue(1, $old);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    private function _verifyInactivity($maxtime = 0)
    {
        if (! $this->get('activity_time')) {
            $this->set('activity_time', time());
        }

        if ((time() - $this->get('activity_time')) > $maxtime) {
            $this->destroy($this->getId());
        } else {
            $this->set('activity_time', time());
        }
    }
}

final class Session
{

    private static $_context = null;

    public static function getContext($sesstype)
    {
        if (self::$_context === null) {
            $classname = 'Session_' . $sesstype;
            self::$_context = new $classname();
        }

        return self::$_context;
    }
}