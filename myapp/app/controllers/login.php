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
            die("Your IP is blocked.");
        }

        $data = array();

        $data['username'] = '';
        $data['user'] = '';

        if ($this->cusertype != 'none') {
            $this->res->redirect($this->user->homepath);
        }

        if ($this->req->isPost()) {

            $muser = new user();

            // Validate input
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            if (empty($username) || empty($password)) {
                $this->res->redirect('login', '<div class="text-error-500">Username and password are required!</div>');
                return;
            }

            $user = $muser->where('email', $username)->find();

            if (! $user) {
                $this->dispatcher->dispatch(new EventLogin($username, $ip, false));

                $this->res->redirect('login', '<div class="text-error-500">USER NOT FOUND!</div>');
                return;
            }

            // Check if password is hashed with password_hash or old md5
            $passwordValid = false;

            if (password_verify($password, $user->password)) {
                $passwordValid = true;
            }

            if (! $passwordValid) {
                $this->dispatcher->dispatch(new EventLogin($username, $ip, false));

                $this->res->redirect('login', '<div class="text-error-500">WRONG PASSWORD!</div>');
                return;
            }

            if ($user->status == 2) {
                $this->dispatcher->dispatch(new EventLogin($username, $ip, false));

                $this->res->redirect('login', '<div class="text-error-500">USER DISABLED!</div>');
                return;
            }

            if ($user->perms != 'superadmin' && $user->partner_id != $this->partner->id) {
                $this->dispatcher->dispatch(new EventLogin($username, $ip, false));

                $this->res->redirect('login', '<div class="text-error-500">User does not exist</div>');
                return;
            }

            setCurrentUser($user);

            $this->dispatcher->dispatch(new EventLogin($username, $ip, true));

            $cutype = $user->perms;

            $this->res->redirect($user->homepath);

            $data['username'] = $username;
            $data['user'] = $user;

            exit();
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
            die("Your IP is blocked.");
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

                $username = isset($_POST['username']) ? trim($_POST['username']) : '';

                if (empty($username)) {
                    $this->res->redirect('login/forgotpass', '<div class="text-error-500">Username is required!</div>');
                    return;
                }

                $user = $muser->where('email', $username)->find();

                if (! $user) {
                    $this->dispatcher->dispatch(new EventForgotPassword($username, $ip, false));

                    $this->res->redirect('login/forgotpass', '<div class="text-error-500">User does not exist!</div>');
                    return;
                }

                if ($user->status == 2) {
                    $this->dispatcher->dispatch(new EventForgotPassword($username, $ip, false));

                    $this->res->redirect('login/forgotpass', '<div class="text-error-500">User is disableddoes not exist!</div>');
                    return;
                }

                if ($user->perms != 'superadmin' && $user->partner_id != $this->partner->id) {
                    $this->dispatcher->dispatch(new EventForgotPassword($username, $ip, false));

                    $this->res->redirect('login', '<div class="text-error-500">User does not exist</div>');
                    return;
                }

                $this->dispatcher->dispatch(new EventForgotPassword($username, $ip, true, $user, $this->partner));

                $this->res->redirect('login/forgotpass', '<div class="text-brand-500">Your password has been mailed to you!</div>');
            } catch (Exception $e) {
                // Catch the "Please wait 5 minutes" message
                $this->res->redirect('login/forgotpass', '<div class="text-error-500">' . $e->getMessage() . '</div>');
            }
        }

        $this->res->view($data, 'main/login_forgotpass');
    }

    public function logout()
    {
        setCurrentUser();

        $this->res->redirect('login', '<div class="text-brand-500">You have logged out!</div>');
    }
}
