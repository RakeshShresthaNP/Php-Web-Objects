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
        $fdata = json_decode(file_get_contents('php://input'), true);

        /* XSS Prevention */
        $fdata = array_map_recursive($fdata, "cleanHtml");

        if ($this->req->isPost()) {

            // Validate input
            if (empty($fdata['username']) || empty($fdata['password'])) {
                throw new ApiException('Username and password are required', 405);
            }

            $user = new user();

            $username = $fdata['username'];
            $password = $fdata['password'];

            $user->select('*', 'email=?', array(
                $username
            ));

            if ($user->exist()) {
                $passwordValid = false;

                if (password_verify($password, $user->password)) {
                    $passwordValid = true;
                }

                if (! $passwordValid) {
                    throw new ApiException('Error Login', 405);
                }

                if ($user->status == 2) {
                    throw new ApiException('Error Login', 405);
                }

                if ($user->perms != 'superadmin' && $user->partner_id != $this->partner->id) {
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

                $tdata['accessToken'] = $this->_generateToken($udata);

                $data['data'] = $tdata;

                $this->res->json($data);
            } else {
                throw new ApiException('Error Login', 405);
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
