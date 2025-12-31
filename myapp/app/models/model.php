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
class model
{

    protected $db = null;

    private $_rs = array();

    private $_pk;

    private $_table;

    public function __construct(string $table = '', string $pk = 'id')
    {
        $this->_pk = $pk;
        $this->_table = $table;
        $this->db = db();
    }

    public function __set(string $key, $val)
    {
        $this->_rs[$key] = $val;
    }

    public function __get(string $key)
    {
        return isset($this->_rs[$key]) ? $this->_rs[$key] : '';
    }

    public function select(string $selectwhat = '*', string $wherewhat = '', $bindings = null)
    {
        if (is_scalar($bindings)) {
            $bindings = mb_trim($bindings) ? array(
                $bindings
            ) : array();
        }
        $sql = 'SELECT ' . $selectwhat . ' FROM ' . $this->_table;
        if ($wherewhat) {
            $sql .= ' WHERE ' . $wherewhat;
        }

        $stmt = $this->db->prepare($sql);

        $i = 0;
        if ($wherewhat) {
            foreach ($bindings as $v) {
                $stmt->bindValue(++ $i, $v);
            }
        }

        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $this->_rs = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            return $stmt->fetchAll();
        }
    }

    public function insert()
    {
        $_pk = $this->_pk;

        $s1 = $s2 = '';

        foreach ($this->_rs as $k => $v) {
            if ($k != $_pk || $v) {
                $s1 .= ',' . $k;
                $s2 .= ',?';
            }
        }

        $sql = 'INSERT INTO ' . $this->_table . ' (' . mb_substr($s1, 1) . ') VALUES (' . mb_substr($s2, 1) . ')';

        $stmt = $this->db->prepare($sql);

        $i = 0;

        foreach ($this->_rs as $k => $v) {
            if ($k != $_pk || $v) {
                $stmt->bindValue(++ $i, is_scalar($v) ? $v : serialize($v));
            }
        }

        $stmt->execute();

        return $this->db->lastInsertId();
    }

    public function update()
    {
        $s = '';

        foreach ($this->_rs as $k => $v) {
            $s .= ',' . $k . '=?';
        }

        $s = mb_substr($s, 1);

        $sql = 'UPDATE ' . $this->_table . ' SET ' . $s . ' WHERE ' . $this->_pk . '=?';

        $stmt = $this->db->prepare($sql);

        $i = 0;

        foreach ($this->_rs as $k => $v) {
            $stmt->bindValue(++ $i, is_scalar($v) ? $v : serialize($v));
        }

        $stmt->bindValue(++ $i, $this->_rs[$this->_pk]);

        return $stmt->execute();
    }

    public function delete()
    {
        $sql = 'DELETE FROM ' . $this->_table . ' WHERE ' . $this->_pk . '=?';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $this->_rs[$this->_pk]);

        return $stmt->execute();
    }

    public function exist(bool $checkdb = false): int
    {
        if ((int) $this->{$this->_pk} >= 1) {
            return 1;
        }

        if ($checkdb) {
            $sql = 'SELECT 1 FROM ' . $this->_table . ' WHERE ' . $this->_pk . '=?';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $this->{$this->_pk}
            ]);
            return $stmt->rowCount();
        }

        return 0;
    }

    public function get()
    {
        return $this->_rs;
    }

    public function assign(array &$arr = array(), bool $checkfield = false)
    {
        foreach ($arr as $key => $val) {
            if ($checkfield) {
                if (isset($this->$key)) {
                    $this->$key = cleanHtml($val);
                }
            } else {
                $this->$key = cleanHtml($val);
            }
        }
    }
}
