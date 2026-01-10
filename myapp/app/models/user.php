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

final class User extends model
{

    public function __construct(int $id = 0)
    {
        parent::__construct('mst_users', 'id');

        if ($id > 0) {
            $this->where('id', $id)->find();
        }
    }

    public function insert(): string|false
    {
        $this->perms ??= 'user';
        $this->status ??= '1';

        $now = date('Y-m-d H:i:s');
        $this->d_created = $now;
        $this->d_updated = $now;

        return parent::insert();
    }

    public function update(): bool
    {
        $this->d_updated = date('Y-m-d H:i:s');

        return parent::update();
    }
}
