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
final class cLogin extends cController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $ip = $this->currentuserip;
        $isBlocked = db()->prepare("SELECT 1 FROM sys_blocked_ips WHERE ip_address = ?");
        $isBlocked->execute([
            $ip
        ]);

        if ($isBlocked->fetch()) {
            die(_t("your_ip_is_blocked"));
        }

        $data = array();
        $data['username'] = '';
        $data['user'] = '';

        if ($this->cusertype != 'none') {
            $this->res->redirect($this->user->homepath);
        }

        $this->res->view($data, 'main/login_form');
    }

    public function forgotpass()
    {
        $ip = $this->currentuserip;
        $isBlocked = db()->prepare("SELECT 1 FROM sys_blocked_ips WHERE ip_address = ?");
        $isBlocked->execute([
            $ip
        ]);

        if ($isBlocked->fetch()) {
            die(_t("your_ip_is_blocked"));
        }

        $data = array();
        $data['username'] = '';
        $data['user'] = '';

        if ($this->cusertype != 'none') {
            $this->res->redirect($this->user->homepath);
        }

        if ($this->req->isPost()) {
            try {
                $muser = new user();
                $params = getRequestData();
                $rules = [
                    'username' => 'required|email'
                ];
                $v = Validator::make($params, $rules);

                if ($v->fails()) {
                    $this->res->redirect('login/forgotpass', '<div class="text-error-500">' . _t('username_is_required') . '</div>');
                    return;
                }

                $user = $muser->where('email', $params['username'])->first();

                if (! $user) {
                    $this->dispatcher->dispatch(new EventForgotPassword($params['username'], $ip, false));
                    $this->res->redirect('login/forgotpass', '<div class="text-error-500">' . _t('user_does_not_exist') . '</div>');
                    return;
                }

                if ($user->status == 2) {
                    $this->dispatcher->dispatch(new EventForgotPassword($params['username'], $ip, false));
                    $this->res->redirect('login/forgotpass', '<div class="text-error-500">' . _t('user_is_disabled') . '</div>');
                    return;
                }

                if ($user->perms != 'superadmin' && $user->partner_id != $this->partner->id) {
                    $this->dispatcher->dispatch(new EventForgotPassword($params['username'], $ip, false));
                    $this->res->redirect('login', '<div class="text-error-500">' . _t('user_does_not_exist') . '</div>');
                    return;
                }

                $this->dispatcher->dispatch(new EventForgotPassword($params['username'], $ip, true, $user, $this->partner));
                $this->res->redirect('login/forgotpass', '<div class="text-brand-500">' . _t('password_mailed_to_you') . '</div>');
            } catch (Exception $e) {
                $this->res->redirect('login/forgotpass', '<div class="text-error-500">' . $e->getMessage() . '</div>');
            }
        }

        $this->res->view($data, 'main/login_forgotpass');
    }

    public function logout()
    {
        setCurrentUser();
        $this->res->redirect('login', '<div class="text-brand-500">' . _t('logged_out') . '</div>');
    }
}
