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
final class cTimezone extends cController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function api_gettimezone()
    {
        $timezones = DateTimeZone::listIdentifiers();

        foreach ($timezones as $tz) {
            $tzs[$tz] = $tz;
        }

        $data['data'] = $tzs;

        $this->res->json($data);
    }
}
