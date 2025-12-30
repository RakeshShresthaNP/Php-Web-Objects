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
final class cTimezone extends cAuthController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function api_getTimezone()
    {
        $data['data'] = null;

        $this->res->json($data);
    }
}
