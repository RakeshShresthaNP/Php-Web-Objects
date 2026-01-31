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
            // Updated with _t()
            throw new ApiException(_t('invalid_partner_setting_format'), 401);
        }

        if (! $secret_key) {
            // Updated with _t()
            throw new ApiException(_t('partner_setting_is_blank'), 401);
        }

        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]);
        $payload = json_encode($udata, JSON_UNESCAPED_SLASHES);
        $base64UrlHeader = base64_jwt_encode($header);
        $base64UrlPayload = base64_jwt_encode($payload);
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret_key, true);
        $base64UrlSignature = base64_jwt_encode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public function api_login()
    {
        $ip = $this->currentuserip;
        $isBlocked = db()->prepare("SELECT 1 FROM sys_blocked_ips WHERE ip_address = ?");
        $isBlocked->execute([
            $ip
        ]);

        if ($isBlocked->fetch()) {
            // Updated with _t()
            throw new ApiException(_t("your_ip_is_blocked"), 503);
        }

        if (! $this->req->isPost()) {
            // Updated with _t()
            throw new ApiException(_t('invalid_request_method'), 400);
        }

        $fdata = getRequestData();

        $rules = [
            'username' => 'required|email',
            'password' => 'required|password'
        ];

        $v = Validator::make($fdata, $rules);
        if ($v->fails()) {
            $errors = json_encode($v->errors());
            throw new ApiException($errors, 405);
        }

        $muser = new user();
        $username = $fdata['username'];
        $user = $muser->where('email', $username)->first();

        if (! $user || ! AuthSecurity::verifyAndUpgrade($fdata['password'], $user->password, $user->id)) {
            $this->dispatcher->dispatch(new EventLogin($username, $ip, false));
            // Updated with _t()
            throw new ApiException(_t('invalid_credentials'), 405);
        }

        if ($user->status == 2) {
            $this->dispatcher->dispatch(new EventLogin($username, $ip, false));
            // Updated with _t()
            throw new ApiException(_t('user_disabled'), 405);
        }

        // Check partner constraints
        if ($user->perms != 'superadmin' && $user->partner_id != $this->partner->id) {
            $this->dispatcher->dispatch(new EventLogin($username, $ip, false));
            // Updated with _t()
            throw new ApiException(_t('error_login'), 405);
        }

        // --- TOTP LOGIC START ---
        if ($user->totp_enabled == 1) {
            $_SESSION['pending_auth_id'] = $user->id;

            if (empty($user->totp_secret)) {
                $secret = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'), 0, 16);
                $_SESSION['temp_secret'] = $secret;

                $issuer = 'PWO-Local';
                $otp_uri = "otpauth://totp/" . rawurlencode($issuer) . ":" . rawurlencode($user->email) . "?secret=" . $secret . "&issuer=" . rawurlencode($issuer) . "&period=30";

                $data['data'] = [
                    'step' => 'totp_setup',
                    'qr_url' => $otp_uri,
                    'secret' => $secret
                ];

                return $this->res->json($data);
            }

            $data['data'] = [
                'step' => 'totp_verify'
            ];

            return $this->res->json($data);
        }
        // --- TOTP LOGIC END ---

        $this->dispatcher->dispatch(new EventLogin($username, $ip, true));

        $udata = [
            'id' => $user->id,
            'realName' => $user->realname,
            'perms' => $user->perms,
            'username' => $user->c_name,
            'homepath' => $user->homepath,
            'exp' => time() + 24 * 3600
        ];

        $data['data'] = [
            'user_id' => $user->id,
            'homepath' => $user->homepath,
            'accessToken' => $this->_generateToken($udata)
        ];

        setCurrentUser($user);

        return $this->res->json($data);
    }

    public function api_verifyotp()
    {
        $fdata = getRequestData();
        $otp_code = $fdata['otp_code'] ?? null;

        if (! $otp_code) {
            // Updated with _t()
            throw new ApiException(_t('otp_code_is_required'), 405);
        }

        if (! isset($_SESSION['pending_auth_id'])) {
            // Updated with _t()
            throw new ApiException(_t('session_expired_please_login_again'), 401);
        }

        $user = new user($_SESSION['pending_auth_id']);
        $secret = $_SESSION['temp_secret'] ?? $user->totp_secret;

        if (! $secret) {
            // Updated with _t()
            throw new ApiException(_t('no_secret_found'), 401);
        }

        if (Totp::verify($otp_code, $secret, 6, 30, 1)) {

            if (isset($_SESSION['temp_secret'])) {
                $user->totp_secret = $secret;
                $user->save();
                unset($_SESSION['temp_secret']);
            }

            $udata = [
                'id' => $user->id,
                'realName' => $user->realname,
                'perms' => $user->perms,
                'username' => $user->c_name,
                'homepath' => $user->homepath,
                'exp' => time() + 24 * 3600
            ];

            $data = [];
            $data['data'] = [
                'user_id' => $user->id,
                'homepath' => $user->homepath,
                'accessToken' => $this->_generateToken($udata),
                'status' => 'success'
            ];

            // Updated with _t()
            $data['message'] = _t('authorized');
            $data['code'] = 200;

            unset($_SESSION['pending_auth_id']);

            $udata = $user->getData();
            setCurrentUser($udata);

            return $this->res->json($data);
        }

        // Updated with _t()
        throw new ApiException(_t('invalid_otp'), 405);
    }
}
