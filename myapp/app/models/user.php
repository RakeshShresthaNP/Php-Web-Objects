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

    public function __construct(int $id = 0)
    {
        parent::__construct('mst_users');

        if ($id)
            $this->select('*', 'id=?', $id);
    }

    public function insert()
    {
        $this->perms = 'user';
        $this->status = '1';
        $this->d_created = date('Y-m-d H:i:s');
        $this->d_updated = $this->d_created;

        return parent::insert();
    }

    public function update()
    {
        $this->d_updated = date('Y-m-d H:i:s');

        return parent::update();
    }
}
