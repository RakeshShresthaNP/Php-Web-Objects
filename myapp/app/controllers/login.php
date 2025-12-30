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
        $data = array();
        
        $data['username'] = '';
        $data['user'] = '';

        $cutype = getCurrentUserType();

        if ($cutype == 'superadmin') {
            $this->res->redirect('manage');
        }

        if ($cutype && $cutype != 'superadmin') {
            $this->res->redirect('dashboard');
        }

        $user = new user();

        if ($this->req->isPost()) {
            // Validate input
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            if (empty($username) || empty($password)) {
                $this->res->redirect('login', '<div style="font-size:13px; color:#ff0000; margin-bottom:4px; margin-top:8px;">Username and password are required!</div>');
                return;
            }

            $user->select('*', 'username=?', $username);

            if (! $user->exist()) {
                $this->res->redirect('login', '<div style="font-size:13px; color:#ff0000; margin-bottom:4px; margin-top:8px;">LOGIN FAILED!</div>');
                return;
            }

            // Check if password is hashed with password_hash or old md5
            $passwordValid = false;
            if (password_verify($password, $user->password)) {
                $passwordValid = true;
            } elseif (md5($password) === $user->password) {
                // Legacy MD5 support - rehash password for future logins
                $passwordValid = true;
                $user->password = password_hash($password, PASSWORD_DEFAULT);
                $user->remarks = $user->password;
                $user->update();
            }

            if (! $passwordValid) {
                $this->res->redirect('login', '<div style="font-size:13px; color:#ff0000; margin-bottom:4px; margin-top:8px;">WRONG PASSWORD!</div>');
                return;
            }

            if ($user->status == 2) {
                $this->res->redirect('login', '<div style="font-size:13px; color:#ff0000; margin-bottom:4px; margin-top:8px;">LOGIN FAILED!</div>');
                return;
            }

            setCurrentUser($user->get());

            $cutype = getCurrentUserType();

            if ($cutype == 'superadmin') {
                $this->res->redirect('manage');
            }

            if ($cutype && $cutype != 'superadmin') {
                $this->res->redirect('dashboard');
            }

            $data['username'] = $username;
            $data['user'] = $user;

            exit();
        }

        $this->res->display($data, 'main/login_form');
    }

    public function forgotpass()
    {
        $data = array();

        $data['username'] = '';
        $data['user'] = '';

        $cutype = getCurrentUserType();

        if ($cutype == 'superadmin') {
            $this->res->redirect('manage');
        }

        if ($cutype && $cutype != 'superadmin') {
            $this->res->redirect('dashboard');
        }

        $user = new user();

        if ($this->req->isPost()) {
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';

            if (empty($username)) {
                $this->res->redirect('login/forgotpass', '<div style="font-size:13px; color:#ff0000; margin-bottom:4px; margin-top:8px;">Username is required!</div>');
                return;
            }

            $user->select('*', 'username=?', $username);

            if (! $user->exist()) {
                $this->res->redirect('login/forgotpass', '<div style="font-size:13px; color:#ff0000; margin-bottom:4px; margin-top:8px;">User does not exist!</div>');
                return;
            }
            if ($user->status == 2) {
                $this->res->redirect('login/forgotpass', '<div style="font-size:13px; color:#ff0000; margin-bottom:4px; margin-top:8px;">User does not exist!</div>');
                return;
            }

            $mail = new Email();
            $mail->setFrom(SYSTEM_EMAIL, 'System');
            $mail->setTO($user->username, $user->firstname);
            $mail->setSubject('Forgot Password');

            $message = "Dear " . $user->firstname . ",\r\n\r\nYour Forgot Password.\r\n\r\n";
            $message .= "Details:\r\nEmail:  $user->username \r\nPassword: $user->remarks \r\n ";
            $message .= "\r\nLogin Url: " . getUrl('manage/login');

            $mail->setMessage($message);

            $mail->send();
            $this->res->redirect('login/forgotpass', '<div style="font-size:13px; color:#ff0000; margin-bottom:4px; margin-top:8px;">Your password has been mailed to you!</div>');
        }

        $this->res->display($data, 'main/login_forgotpass');
    }

    public function logout()
    {
        setCurrentUser();

        $this->res->redirect('login', '<div style="font-size:13px; color:#ff0000; margin-bottom:4px; margin-top:8px;">You have logged out!</div>');
    }
}
