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
        $partner = new partner();

        $pusers = $user->select('*')
            ->where('perms', '<>', 'superadmin')
            ->paginate($currentPage, $perPage);
        $partners = $partner->getAllPartnersAsGraph();

        $data['users'] = $pusers->items;
        $data['links'] = links($pusers->meta);
        $data['partners'] = $partners->items;

        $this->res->view($data);
    }

    public function manage_add()
    {
        $data['pagename'] = 'Add User';

        $user = [];

        if ($this->req->isPost()) {

            $vars = getRequestData();

            $rules = [
                'realname' => 'required|alpha_space',
                'homepath' => 'required|alpha',
                'email' => "required|email|unique:mst_users",
                'password' => 'required|min:12|password',
                'confirm_password' => 'matches:password'
            ];

            $messages = [
                'email.required' => 'We need email address to secure your account.',
                'email.unique' => 'This email is already registered with another user.',
                'realname.alpha_space' => 'Names can only contain letters and spaces.',
                'password.min' => 'Your new password must be at least 12 characters long.',
                'confirm_password.matches' => 'The password confirmation does not match the new password.',
                'password.password' => 'Your password must include an uppercase letter, a number, and a special character.'
            ];

            $v = Validator::make($vars, $rules, $messages);

            if ($v->fails()) {
                $_SESSION['flash_errors'] = $v->errors();

                $data['user'] = $vars;

                $this->res->view($data);
                return;
            }

            $vars['password'] = password_hash($vars['password'], PASSWORD_DEFAULT);
            $vars['c_name'] = explode('@', $vars['email'])[0];
            $vars['partner_id'] = $this->partner->id;
            $vars['u_created'] = $this->user->id;
            $vars['u_updated'] = $this->user->id;

            unset($vars['confirm_password']);
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

            $vars = getRequestData();

            $userId = $user->id;

            $rules = [
                'realname' => 'required|alpha_space',
                'homepath' => 'required|alpha',
                'email' => "required|email|unique:mst_users,id,$userId",
                'password' => 'sometimes|min:12|password',
                'confirm_password' => 'matches:password'
            ];

            $messages = [
                'email.required' => 'We need email address to secure your account.',
                'email.unique' => 'This email is already registered with another user.',
                'realname.alpha_space' => 'Names can only contain letters and spaces.',
                'password.min' => 'Your new password must be at least 12 characters long.',
                'confirm_password.matches' => 'The password confirmation does not match the new password.',
                'password.password' => 'Your password must include an uppercase letter, a number, and a special character.'
            ];

            $v = Validator::make($vars, $rules, $messages);

            if ($v->fails()) {
                $_SESSION['flash_errors'] = $v->errors();

                $this->res->redirect('manage/users/edit/' . $id);
                return;
            }

            if (! empty($vars['password'])) {
                $vars['password'] = password_hash($vars['password'], PASSWORD_DEFAULT);
            } else {
                $vars['password'] = $user->password;
            }
            $vars['u_updated'] = $this->user->id;
            unset($vars['confirm_password']);
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
