// pwo-auth.js

export const Auth = {
    // Check if user is logged in
    isAuthenticated() {
        return !!localStorage.getItem('pwoToken');
    },

    // Get current user ID
    getUserId() {
        return localStorage.getItem('pwoUserId');
    },
	
	forceLogin() {
	    localStorage.removeItem('pwoToken');
	    localStorage.removeItem('pwoUserId');
	    const overlay = document.getElementById('pwo-auth-overlay');
	    if (overlay) {
	        overlay.classList.remove('hidden');
	        overlay.classList.add('flex');
	    }
	},
	
    // Get auth token
    getToken() {
        return localStorage.getItem('pwoToken');
    },

    // Handle login process
    async login(username, password) {
        try {
            const resp = await fetch('api/auth/login', { 
                method: 'POST', 
                headers: { 'Content-Type': 'application/json' }, 
                body: JSON.stringify({ username, password }) 
            });
            
            const res = await resp.json();
            const token = res.data?.accessToken || res.token;
            const userId = res.data?.user_id || res.user_id;

            if (token) {
                localStorage.setItem('pwoToken', token);
                localStorage.setItem('pwoUserId', userId);
                return { success: true };
            }
            return { success: false, error: 'Invalid credentials' };
        } catch (e) {
            console.error("Login Error:", e);
            return { success: false, error: 'Server connection failed' };
        }
    },

    // Handle logout
    logout() {
        localStorage.clear();
        location.reload();
    }
};
