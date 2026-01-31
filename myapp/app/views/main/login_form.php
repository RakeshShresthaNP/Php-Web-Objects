<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo _t('admin_authorization'); ?> | PWO</title>
<link rel="stylesheet" href="<?php echo getUrl('assets/manage/tailwind.css'); ?>">
<style>
body {
	background-color: #0b0e11;
	font-family: 'Inter', sans-serif;
}

.glass-panel {
	background: rgba(27, 34, 43, 0.9);
	backdrop-filter: blur(10px);
}

#qrcode img, #qrcode canvas {
	margin: 0 auto;
	border-radius: 0.5rem;
}

/* Premium OTP Input Styling */
.otp-input {
	letter-spacing: 0.6em;
	text-indent: 0.3em;
	text-align: center;
	font-family: 'JetBrains Mono', 'Courier New', monospace;
	font-weight: 900;
	font-size: 2rem;
	color: #60a5fa;
	background: rgba(0, 0, 0, 0.4) !important;
	border: 2px solid rgba(255, 255, 255, 0.05) !important;
	transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.otp-input:focus {
	border-color: #3b82f6 !important;
	background: rgba(0, 0, 0, 0.6) !important;
	box-shadow: 0 0 25px rgba(59, 130, 246, 0.3);
	color: #ffffff;
	transform: scale(1.02);
	outline: none;
}

.otp-input::-webkit-outer-spin-button, .otp-input::-webkit-inner-spin-button
	{
	-webkit-appearance: none;
	margin: 0;
}

.otp-input[type=number] {
	-moz-appearance: textfield;
}
</style>
<script src="<?php echo getUrl('assets/manage/qrcode.min.js'); ?>"></script>
</head>
<body class="flex items-center justify-center h-screen overflow-hidden">

	<div id="auth-container"
		class="w-full max-w-md p-10 glass-panel rounded-3xl border border-white/5 shadow-2xl transition-all duration-500">

		<div class="mb-10 text-center">
			<h2 class="text-2xl font-black tracking-tighter text-white uppercase"><?php echo _t('Admin') ?></h2>
			<p
				class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-1">
                <?php echo _t('identity_verification_required'); ?>
            </p>
		</div>

		<div id="responseMsg" class="mt-6 text-center text-[10px] font-bold uppercase min-h-[15px]"></div>

		<form id="form-password" class="space-y-6">
			<input type="email" id="username" required
				placeholder="<?php echo _t('admin_email'); ?>"
				class="w-full p-4 text-sm text-white bg-black/40 border border-white/5 rounded-2xl outline-none focus:border-blue-500 transition-all">
			<input type="password" id="password" required
				placeholder="<?php echo _t('password'); ?>"
				class="w-full p-4 text-sm text-white bg-black/40 border border-white/5 rounded-2xl outline-none focus:border-blue-500 transition-all">
			<button type="submit"
				class="w-full py-4 text-xs font-black text-white uppercase bg-blue-600 rounded-2xl hover:bg-blue-700">
                <?php echo _t('verify_password'); ?>
            </button>
		</form>

		<div id="form-setup" class="hidden space-y-6 text-center">
			<p class="text-[10px] text-blue-400 font-bold uppercase">
                <?php echo _t('scan_with_authenticator'); ?>
            </p>
			<div id="qrcode"
				class="mx-auto border-4 border-white rounded-xl shadow-lg flex justify-center p-2 bg-white w-[200px] h-[200px]"></div>
			<p id="secret-key"
				class="text-[9px] text-gray-500 font-mono break-all px-4 uppercase"></p>
			<input type="number" id="otp-setup" placeholder=""
				inputmode="numeric" maxlength="6"
				class="otp-input w-full p-5 rounded-2xl outline-none mb-4">
			<button id="btn-confirm-setup"
				class="w-full py-4 text-xs font-black text-white uppercase bg-green-600 rounded-2xl">
                <?php echo _t('confirm_setup'); ?>
            </button>
		</div>

		<div id="form-verify" class="hidden space-y-6 text-center">
			<p class="text-[10px] text-gray-400 font-bold uppercase">
                <?php echo _t('enter_6_digit_code'); ?>
            </p>
			<input type="number" id="otp-verify" placeholder=""
				inputmode="numeric" maxlength="6"
				class="otp-input w-full p-5 rounded-2xl outline-none mb-4">
			<button id="btn-authorize"
				class="w-full py-4 text-xs font-black text-white uppercase bg-blue-600 rounded-2xl">
                <?php echo _t('authorize'); ?>
            </button>
		</div>
	</div>

	<script type="module">
    import { OTPHandler } from './assets/manage/auth-otp.js';

    document.querySelector('#form-password').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const payload = {
            username: document.querySelector('#username').value,
            password: document.querySelector('#password').value
        };

        const res = await fetch('api/auth/login', { 
            method: 'POST', 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload) 
        });
        const result = await res.json();

        if (result.code === 200) {
            if (result.data && result.data.step) {
                OTPHandler.showStep(result.data); 
            } else if (result.data && result.data.accessToken) {
                localStorage.setItem('pwoToken', result.data.accessToken);
                localStorage.setItem('pwoUserId', result.data.user_id);
                window.location.href = result.data.homepath || 'manage/supportsystem';
            }
        } else {
            const msg = document.querySelector('#responseMsg');
            // Fallback string also wrapped for JS safety
            msg.innerHTML = result.error +"<br><br>";
            msg.className = "text-red-500 text-center text-[10px] font-bold uppercase";
        }
    });

    document.getElementById('btn-confirm-setup').onclick = () => {
        OTPHandler.verifyOTP('otp-setup');
    };

    document.getElementById('btn-authorize').onclick = () => {
        OTPHandler.verifyOTP('otp-verify');
    };
    </script>
</body>
</html>
