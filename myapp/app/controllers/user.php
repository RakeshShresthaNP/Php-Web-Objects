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
final class cUser extends cController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function api_info()
    {
        $data['data'] = array(
            'id' => 0,
            'realName' => $this->user->realName,
            'perms' => $this->user->perms,
            'username' => $this->user->username,
            'homePath' => $this->user->homepath
        );

        $this->res->json($data);
    }
}
