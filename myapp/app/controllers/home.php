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
final class cHome extends cController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $data['pagename'] =  $this->partner->sitetitle;

        $this->res->view($data);
    }

    public function manage_index()
    {
        $data['pagename'] =  $this->partner->sitetitle;

        $this->res->view($data);
    }

    public function dashboard_index()
    {
        $data['pagename'] =  $this->partner->sitetitle;

        $this->res->view($data);
    }
}
