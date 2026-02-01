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
        $data['users'] = _t('users');

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
        $data['pagename'] = _t('add_user');

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
                'email.required' => _t('email_required_secure'),
                'email.unique' => _t('email_already_registered'),
                'realname.alpha_space' => _t('name_alpha_space_error'),
                'password.min' => _t('password_min_length_error'),
                'confirm_password.matches' => _t('password_mismatch_error'),
                'password.password' => _t('password_complexity_error')
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
            $vars['created_by'] = $this->user->id;
            $vars['updated_by'] = $this->user->id;

            unset($vars['confirm_password']);
            unset($vars['submit']);

            $user = new user();

            $user->assign($vars);
            $user->insert();

            $this->res->redirect('manage/users', _t('user_added_successfully'));
        }

        $data['user'] = $user;

        $this->res->view($data);
    }

    public function manage_edit($id = 0)
    {
        $data['pagename'] = _t('users');

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
                'email.required' => _t('email_required_secure'),
                'email.unique' => _t('email_already_registered'),
                'realname.alpha_space' => _t('name_alpha_space_error'),
                'password.min' => _t('password_min_length_error'),
                'confirm_password.matches' => _t('password_mismatch_error'),
                'password.password' => _t('password_complexity_error')
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
            $vars['updated_by'] = $this->user->id;
            unset($vars['confirm_password']);
            unset($vars['submit']);

            $user->assign($vars);
            $user->update();

            $this->res->redirect('manage/users', _t('user_updated_successfully'));
        }

        $this->res->view($data);
    }

    public function manage_disable($userid = 0)
    {
        $data['pagename'] = _t('users');

        $user = new user($userid);
        $user->status = 2;
        $user->updated_by = $this->user->id;
        $user->update();

        $this->res->redirect('manage/users', _t('user_disabled'));
    }

    public function manage_enable($userid = 0)
    {
        $data['pagename'] = _t('users');

        $user = new user($userid);
        $user->status = 1;
        $user->updated_by = $this->user->id;
        $user->update();

        $this->res->redirect('manage/users', _t('user_enabled'));
    }
}
