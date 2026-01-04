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
final class cUsers extends cAdminController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $data['pagetitle'] = SITE_TITLE;

        $this->res->view($data);
    }

    public function manage_index()
    {
        $data['pagename'] = 'Users';

        $user = new model('users');
        $data['users'] = $user->select('*', 'perms <> ?', 'superadmin');

        $this->res->view($data);
    }

    public function manage_add()
    {
        $data['pagename'] = 'Add User';

        $user = [];

        if ($this->req->isPost()) {
            $vars = $_POST;

            // Validate password
            if (empty($vars['password']) || strlen($vars['password']) < 6) {
                $this->res->redirect('admin/users/manage_add', 'Password must be at least 6 characters long');
                return;
            }

            $data['user'] = $vars;

            $vars['password'] = password_hash($vars['password'], PASSWORD_DEFAULT);
            $vars['remarks'] = $vars['password'];

            unset($vars['confirm_password']);
            unset($vars['iserror1']);
            unset($vars['iserror2']);
            unset($vars['submit']);

            $user->assign($vars);
            $user->insert();

            $this->res->redirect('manage/users', 'User Added Successfully');
        }

        $data['user'] = $user;

        $this->res->view($data);
    }

    public function manage_edit($id = 0)
    {
        $data['pagename'] = 'Users';

        $user = new user($id);

        $data['user'] = $user;

        if ($this->req->isPost()) {
            $vars = $_POST;
            if (! empty($vars['password'])) {
                // Validate password
                if (strlen($vars['password']) < 6) {
                    $this->res->redirect('manage/users/edit/' . $id, 'Password must be at least 6 characters long');
                    return;
                }
                $vars['password'] = password_hash($vars['password'], PASSWORD_DEFAULT);
                $vars['remarks'] = $vars['password'];
            } else {
                $vars['password'] = $user->password;
            }
            unset($vars['confirm_password']);
            unset($vars['iserror1']);
            unset($vars['iserror2']);
            unset($vars['submit']);

            $user->assign($vars);
            $user->update();

            $this->res->redirect('manage/users', 'User Updated Successfully');
        }

        $this->res->view($data);
    }

    public function manage_disable($userid = 0)
    {
        $data['pagename'] = 'Users';

        $user = new user($userid);
        $user->status = 2;
        $user->update();

        $this->res->redirect('manage/users', 'User Disabled');
    }

    public function manage_enable($userid = 0)
    {
        $data['pagename'] = 'Users';

        $user = new user($userid);
        $user->status = 1;
        $user->update();

        $this->res->redirect('manage/users', 'User Enabled');
    }
}
