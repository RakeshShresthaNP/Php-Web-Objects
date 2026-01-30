<?php

/**
 # Copyright Rakesh Shrestha (rakesh.shrestha@gmail.com)
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
            throw new ApiException("Your IP is blocked.", 503);
        }

        if (! $this->req->isPost()) {
            throw new ApiException('Invalid request method', 400);
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
            throw new ApiException('Invalid Credentials', 405);
        }

        if ($user->status == 2) {
            $this->dispatcher->dispatch(new EventLogin($username, $ip, false));
            throw new ApiException('User Disabled', 405);
        }

        // Check partner constraints
        if ($user->perms != 'superadmin' && $user->partner_id != $this->partner->id) {
            $this->dispatcher->dispatch(new EventLogin($username, $ip, false));
            throw new ApiException('Error Login', 405);
        }

        // --- TOTP LOGIC START ---
        if ($user->totp_enabled == 1) {
            $_SESSION['pending_auth_id'] = $user->id;

            // If enabled but no secret exists, generate one (Setup Phase)
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

            // Secret exists, just verify
            return $this->res->json($data);
        }
        // --- TOTP LOGIC END ---

        // If TOTP is NOT enabled, proceed to generate token immediately
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
            throw new ApiException('OTP code is required', 405);
        }

        if (! isset($_SESSION['pending_auth_id'])) {
            throw new ApiException('Session expired, please login again', 401);
        }

        $user = new user($_SESSION['pending_auth_id']);
        $secret = $_SESSION['temp_secret'] ?? $user->totp_secret;

        if (! $secret) {
            throw new ApiException('No secret found', 401);
        }

        // Verify using the 30s period and Base32 decoding we implemented in Totp class
        if (Totp::verify($otp_code, $secret, 6, 30, 1)) {

            // Save secret if this was setup phase
            if (isset($_SESSION['temp_secret'])) {
                $user->totp_secret = $secret;
                $user->save();
                unset($_SESSION['temp_secret']);
            }

            // Verification success - Prepare JWT Token Data
            $udata = [
                'id' => $user->id,
                'realName' => $user->realname,
                'perms' => $user->perms,
                'username' => $user->c_name,
                'homepath' => $user->homepath,
                'exp' => time() + 24 * 3600
            ];

            // Prepare the final data structure carefully
            $data = []; // Initialize fresh
            $data['data'] = [
                'user_id' => $user->id,
                'homepath' => $user->homepath,
                'accessToken' => $this->_generateToken($udata),
                'status' => 'success'
            ];

            $data['message'] = 'Authorized';
            $data['code'] = 200;

            // Clean up session now that authentication is complete
            unset($_SESSION['pending_auth_id']);

            $udata = $user->getData();

            setCurrentUser($udata);

            return $this->res->json($data);
        }

        throw new ApiException('Invalid OTP', 405);
    }
}
