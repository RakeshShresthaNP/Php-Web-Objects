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
final class cUsers extends cController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function manage_index()
    {
        $data['users'] = 'Users';

        $currentPage = (int) ($_GET['page'] ?? 1);
        $perPage = (int) ($_GET['perpage'] ?? 1);

        $user = new model('mst_users');

        $pusers = $user->select('*')
            ->where('perms', '<>', 'superadmin')
            ->paginate($currentPage, $perPage);

        $data['users'] = $pusers->items;
        $data['links'] = links($pusers->meta);
        
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
            $vars['c_name'] = explode('@', $vars['email'])[0];
            $vars['partner_id'] = $this->partner->id;
            $vars['u_created'] = $this->user->id;
            $vars['u_updated'] = $this->user->id;

            unset($vars['confirm_password']);
            unset($vars['iserror1']);
            unset($vars['iserror2']);
            unset($vars['submit']);

            $user = new user();

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
            } else {
                $vars['password'] = $user->password;
            }
            $vars['u_updated'] = $this->user->id;
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
        $user->u_updated = $this->user->id;

        $user->update();

        $this->res->redirect('manage/users', 'User Disabled');
    }

    public function manage_enable($userid = 0)
    {
        $data['pagename'] = 'Users';

        $user = new user($userid);
        $user->status = 1;
        $user->u_updated = $this->user->id;
        $user->update();

        $this->res->redirect('manage/users', 'User Enabled');
    }
}
