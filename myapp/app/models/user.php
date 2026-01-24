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

final class user extends model
{

    public function __construct($id = 0)
    {
        parent::__construct('mst_users', 'id');

        if ($id > 0) {
            $res = $this->where('id', $id)->first();
        }
    }

    public function insert(): string|false
    {
        $this->perms ??= 'user';
        $this->status ??= '1';

        return parent::insert();
    }
}
