export const OTPHandler = {
    showStep(data) {
        document.querySelector('#form-password').classList.add('hidden');
        if (data.step === 'totp_setup') {
            document.querySelector('#form-setup').classList.remove('hidden');
            document.querySelector('#secret-key').textContent = "Secret: " + data.secret;
            
            // Render the QR code using qrcode.js
            const qrBox = document.querySelector('#qrcode');
            qrBox.innerHTML = ""; 
            new QRCode(qrBox, {
                text: data.qr_url,
                width: 180,
                height: 180,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        } else {
            document.querySelector('#form-verify').classList.remove('hidden');
        }
    },

    async verifyOTP(inputId) {
        const code = document.getElementById(inputId).value;
        const res = await fetch('api/auth/verifyotp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ otp_code: code })
        });
        const result = await res.json();

        if (result.code === 200) {
            localStorage.setItem('pwoToken', result.data.accessToken);
            window.location.href = 'manage/supportsystem';
        } else {
            alert(result.message || 'Invalid Code');
        }
    }
};

window.Auth = { verifyOTP: (id) => OTPHandler.verifyOTP(id) };
