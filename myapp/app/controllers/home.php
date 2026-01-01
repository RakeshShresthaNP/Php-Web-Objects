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

        $this->cusertype = getCurrentUserType();
    }

    public function index()
    {
        $data['pagetitle'] = SITE_TITLE;

        $this->res->display($data);
    }

    public function manage_index()
    {
        if ($this->cusertype != 'superadmin') {
            $this->res->redirect('login', 'Invalid Access');
        }

        $data['pagetitle'] = SITE_TITLE;

        $this->res->display($data);
    }

    public function dashboard_index()
    {
        if ($this->cusertype != 'user') {
            $this->res->redirect('login', 'Invalid Access');
        }

        $data['pagetitle'] = SITE_TITLE;

        $this->res->display($data);
    }
}
