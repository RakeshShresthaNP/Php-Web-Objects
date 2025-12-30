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
final class user extends model
{

    public function __construct($id = 0)
    {
        parent::__construct('users');

        if ($id)
            $this->select('*', 'id=?', $id);
    }

    public function insert()
    {
        $this->perms = 'user';
        $this->status = '2';
        $this->registerip = getRequestIP();
        $this->created = date('Y-m-d H:i:s');

        return parent::insert();
    }
}
