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
final class cPages extends cAdminController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $data['pagetitle'] = SITE_TITLE;

        $this->res->display($data);
    }

    public function dashboard_advancedforms()
    {
        $data['pagename'] = 'Advanced Forms';

        $this->res->display($data);
    }

    public function dashboard_simpletables()
    {
        $data['pagename'] = 'Simple Tables';

        $this->res->display($data);
    }

    public function manage_advancedforms()
    {
        $data['pagename'] = 'Advanced Forms';

        $this->res->display($data);
    }

    public function manage_simpletables()
    {
        $data['pagename'] = 'Simple Tables';

        $this->res->display($data);
    }
}
