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
final class cAuth extends cController
{

    public function __construct()
    {
        parent::__construct();
    }

    private function _generateToken(&$udata)
    {
        if (isset($this->partner->settings[0]->secretkey)) {
            $secret_key = $this->partner->settings[0]->secretkey;
        } else {
            throw new ApiException('Invalid partner setting format.', 401);
        }

        if (! $secret_key) {
            throw new ApiException('Partner setting is blank.', 401);
        }

        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]);

        // Create the token payload
        $payload = json_encode($udata, JSON_UNESCAPED_SLASHES);

        // Encode Header
        $base64UrlHeader = base64_jwt_encode($header);

        // Encode Payload
        $base64UrlPayload = base64_jwt_encode($payload);

        // Create Signature Hash
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret_key, true);

        // Encode Signature to Base64Url String
        $base64UrlSignature = base64_jwt_encode($signature);

        // Create JWT
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

        return $jwt;
    }

    public function api_login()
    {
        $ip = $this->currentuserip;
        $isBlocked = db()->prepare("SELECT 1 FROM sys_blocked_ips WHERE ip_address = ?");
        $isBlocked->execute([
            $ip
        ]);
        if ($isBlocked->fetch()) {
            throw new ApiException("Your IP is blocked.", 503);
        }

        if ($this->req->isPost()) {

            $fdata = getRequestData();

            $rules = [
                'username' => 'required|email',
                'password' => 'required|password'
            ];

            $v = Validator::make($fdata, $rules);

            if ($v->fails()) {
                $errors = json_encode($v->errors());
                throw new ApiException($errors, 405);
                return;
            }

            $muser = new user();

            $username = $fdata['username'];
            $password = $fdata['password'];

            $user = $muser->where('email', $username)->first();

            if ($user) {
                $passwordValid = false;

                if (AuthSecurity::verifyAndUpgrade($fdata['password'], $user->password, $user->id)) {
                    $passwordValid = true;
                }

                if (! $passwordValid) {
                    $this->dispatcher->dispatch(new EventLogin($username, $ip, false));

                    throw new ApiException('Incorrect Password', 405);
                }

                if ($user->status == 2) {
                    $this->dispatcher->dispatch(new EventLogin($username, $ip, false));

                    throw new ApiException('User Disabled', 405);
                }

                if ($user->perms != 'superadmin' && $user->partner_id != $this->partner->id) {
                    $this->dispatcher->dispatch(new EventLogin($username, $ip, false));
                    throw new ApiException('Error Login', 405);
                }

                $udata = array(
                    'id' => $user->id,
                    'realName' => $user->realname,
                    'perms' => $user->perms,
                    'username' => $user->c_name,
                    'homepath' => $user->homepath,
                    'exp' => time() + 24 * 3600
                );

                $this->dispatcher->dispatch(new EventLogin($username, $ip, true));

                $tdata['accessToken'] = $this->_generateToken($udata);

                $data['data'] = $tdata;

                $this->res->json($data);
            } else {
                throw new ApiException('User not found', 405);
            }
        } else {
            throw new ApiException('Invalid request method', 400);
        }
    }

    public function api_refresh()
    {
        $pdata = $this->req->getPayloadData();

        $udata = array(
            'id' => $pdata->id,
            'realName' => $pdata->realName,
            'perms' => $pdata->perms,
            'username' => $pdata->username,
            'exp' => time() + 24 * 3600
        );

        $tdata['accessToken'] = $this->_generateToken($udata);

        $data['data'] = $tdata;

        $this->res->json($data);
    }

    public function api_logout()
    {
        $data['data'] = null;

        $this->res->json($data);
    }

    public function api_codes()
    {
        $data['data'] = array(
            'AC_100010',
            'AC_100020',
            'AC_100030'
        );

        $this->res->json($data);
    }
}
