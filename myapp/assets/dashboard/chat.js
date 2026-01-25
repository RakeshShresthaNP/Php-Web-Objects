/**
 * PWO Autonomous Support System - Strict Auth Edition
 * Optimized for Dynamic Token & Partner Injection
 * Copyright Rakesh Shrestha
 */
(async function() {
    // 1. CONFIGURATION
    const WS_CLIENT_PATH = 'assets/dashboard/wsclient.js';
    const SOCKET_URL = window.location.protocol === 'https:' 
        ? `wss://${window.location.hostname}/ws` 
        : `ws://localhost:8080`;

    // 2. DEPENDENCY LOADER
    const loadScript = (src) => {
        return new Promise((resolve, reject) => {
            if (document.querySelector(`script[src="${src}"]`)) return resolve();
            const script = document.createElement('script');
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    };

    try {
        await Promise.all([
            loadScript('https://cdn.tailwindcss.com'),
            loadScript(WS_CLIENT_PATH)
        ]);
    } catch (err) { 
        console.error("Failed to load chat dependencies");
        return; 
    }

    // 3. UI INJECTION
    const windowHtml = `
        <div id="pwo-window" class="hidden fixed bottom-24 right-6 w-80 md:w-96 h-[500px] bg-white rounded-2xl shadow-2xl z-[9999] border flex-col overflow-hidden animate-in fade-in slide-in-from-bottom-4">
            <div class="bg-emerald-600 p-4 text-white flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-sm">Live Support</h3>
                    <p class="text-[10px] opacity-80" id="pwo-status">Connecting...</p>
                </div>
                <button id="pwo-close" class="text-2xl hover:rotate-90 transition-transform">&times;</button>
            </div>
            <div id="chat-box" class="flex-1 overflow-y-auto p-4 bg-slate-50 flex flex-col gap-3 scroll-smooth relative"></div>
            <div class="p-4 bg-white border-t flex gap-2">
                <input id="chat-in" type="text" placeholder="Type a message..." class="flex-1 bg-gray-100 rounded-full px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                <button id="chat-send" class="bg-emerald-600 text-white p-2 rounded-full hover:bg-emerald-700 disabled:opacity-50">
                    <svg viewBox="0 0 24 24" class="w-5 h-5 fill-current"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </div>
        </div>
        <button id="pwo-bubble" class="fixed bottom-6 right-6 w-16 h-16 bg-emerald-600 text-white rounded-full shadow-2xl flex items-center justify-center text-2xl z-[9999] hover:scale-110 active:scale-95 transition-all">💬</button>`;

    document.body.insertAdjacentHTML('beforeend', windowHtml);

    // 4. WEBSOCKET INITIALIZATION
    // We grab the token once here, but we will re-check it during calls
    const ws = new WSClient(SOCKET_URL, localStorage.getItem('pwoToken') || "");
    ws.connect();

    const win = document.getElementById('pwo-window');
    const bubble = document.getElementById('pwo-bubble');
    const chatBox = document.getElementById('chat-box');
    const chatIn = document.getElementById('chat-in');
    const sendBtn = document.getElementById('chat-send');

    // 5. HELPERS
    const getActiveToken = () => {
        const t = localStorage.getItem('pwoToken');
        return (t && t !== "null" && t !== "undefined") ? t : null;
    };
    const getAuthHeaders = () => ({ 'X-Forwarded-Host': window.location.hostname });

    // 6. BUBBLE / HISTORY LOGIC
    bubble.onclick = () => {
        const isOpening = win.classList.contains('hidden');
        win.classList.toggle('hidden');
        win.classList.toggle('flex');
        
        if (isOpening) {
            const token = getActiveToken();
            
            if (token) {
                const requestHistory = () => {
                    if (ws.socket && ws.socket.readyState === WebSocket.OPEN) {
                        console.log("Requesting history with token:", token.substring(0, 10) + "...");
                        ws.call('chat', 'history', { token: token }, getAuthHeaders());
                    } else {
                        window.addEventListener('ws_connected', () => 
                            ws.call('chat', 'history', { token: token }, getAuthHeaders()), { once: true });
                    }
                };
                requestHistory();
            } else {
                // Clear and show login if no token
                chatBox.innerHTML = '';
                renderMessage({ 
                    sender: 'Bot', 
                    message: '👋 Welcome! Please login to sync your chat history.', 
                    time: new Date().toLocaleTimeString() 
                }, 'bot');
                showLoginOverlay();
            }
        }
        setTimeout(() => chatIn.focus(), 100);
    };

    // 7. ERROR INTERCEPTOR
    window.addEventListener('ws_error', (e) => {
        const err = e.detail;
        console.error("🕵️ WS Error:", err);

        if (err.message && err.message.includes("DEBUG")) {
            return; // Don't logout on server config errors
        }

        if (err.code === 401) {
            localStorage.removeItem('pwoToken');
            showLoginOverlay();
        }
    });

    // 8. SEND LOGIC
    const handleSend = () => {
        const text = chatIn.value.trim();
        if (text && ws.socket?.readyState === WebSocket.OPEN) {
            const token = getActiveToken();
            const params = { message: text };
            
            // Critical: Ensure token is attached if available
            if (token) params.token = token;

            ws.call('chat', 'send', params, getAuthHeaders());
            chatIn.value = '';
        }
    };

    sendBtn.onclick = handleSend;
    chatIn.onkeypress = (e) => { if (e.key === 'Enter') handleSend(); };

    // 9. AUTH OVERLAY
	// 9. AUTH OVERLAY (DEBUG VERSION)
	function showLoginOverlay() {
	    if (document.getElementById('pwo-login-box')) return;
	    const loginHtml = `
	        <div id="pwo-login-box" class="absolute inset-0 bg-white/95 backdrop-blur-sm z-[10000] flex items-center justify-center p-4">
	            <div class="bg-white p-6 rounded-2xl shadow-2xl border w-full max-w-[280px]">
	                <p class="text-gray-800 font-bold text-sm mb-4 text-center">Login to your account</p>
	                <input id="pwo-user" type="text" placeholder="Username" class="w-full mb-2 p-2 border rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">
	                <input id="pwo-pass" type="password" placeholder="Password" class="w-full mb-4 p-2 border rounded-lg text-sm outline-none focus:ring-2 focus:ring-emerald-500">
	                <button id="pwo-do-login" class="w-full bg-emerald-600 text-white py-2 rounded-lg font-bold text-sm hover:bg-emerald-700 transition-colors">Login</button>
	            </div>
	        </div>`;
	    win.insertAdjacentHTML('afterbegin', loginHtml);

	    document.getElementById('pwo-do-login').onclick = async () => {
	        const username = document.getElementById('pwo-user').value;
	        const password = document.getElementById('pwo-pass').value;
	        
	        console.log("🚀 Attempting Login for:", username);

	        try {
	            const resp = await fetch('api/auth/login', { 
	                method: 'POST', 
	                headers: {'Content-Type': 'application/json'},
	                body: JSON.stringify({ username, password }) 
	            });
	            
	            const result = await resp.json();
	            
	            // --- THE DEBUG PART ---
	            console.log("📡 FULL API RESPONSE:", result);
	            
	            // Let's try to find a token anywhere in the object
	            const token = result.data?.accessToken || result.accessToken || result.token || (result.data ? result.data.token : null);

	            if (token) {
	                console.log("✅ TOKEN FOUND:", token.substring(0, 15) + "...");
	                localStorage.setItem('pwoToken', token);
	                //alert("Login Success! Refreshing...");
	                //location.reload(); 
	            } else {
	                console.error("❌ TOKEN NOT FOUND IN RESPONSE. Check the 'FULL API RESPONSE' above.");
	                alert("Auth failed. Check Console (F12) to see JSON structure.");
	            }
	        } catch (err) { 
	            console.error("🔥 FETCH ERROR:", err); 
	        }
	    };
	}
	
    // 10. RENDERER
    function renderMessage(data, type) {
        if (!data) return;
        const isMe = type === 'me';
        const isBot = type === 'bot';
        const msgDiv = document.createElement('div');
        
        const displayName = data.sender || (isMe ? "You" : "System");
        let bgColor = isMe ? 'bg-emerald-600 text-white' : (isBot ? 'bg-blue-600 text-white' : 'bg-white text-gray-800 border');
        let align = isMe ? 'self-end rounded-tr-none' : 'self-start rounded-tl-none';
        
        msgDiv.className = `max-w-[85%] p-3 rounded-2xl text-sm shadow-sm animate-in fade-in zoom-in-95 duration-200 ${bgColor} ${align}`;
        msgDiv.innerHTML = `
            <p class="text-[10px] font-bold mb-1 opacity-75">${displayName}</p>
            <p class="leading-relaxed">${data.message}</p>
            <p class="text-[9px] mt-1 opacity-50 text-right font-mono">${data.time || ''}</p>
        `;
        chatBox.appendChild(msgDiv);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    // 11. WS EVENT LISTENERS
    window.addEventListener('ws_connected', () => { 
        document.getElementById('pwo-status').innerText = 'Online'; 
        sendBtn.disabled = false; 
    });

    window.addEventListener('ws_chat_history', (e) => { 
        chatBox.innerHTML = ''; 
        const history = e.detail.data || [];
        if (history.length > 0) {
            document.getElementById('pwo-status').innerText = 'Online • ' + (history[0].sender || 'User');
        }
        history.forEach(msg => renderMessage(msg, 'remote')); 
    });

    window.addEventListener('ws_chat_confirmation', (e) => renderMessage(e.detail.data || e.detail, 'me'));
    window.addEventListener('ws_new_message', (e) => renderMessage(e.detail.data || e.detail, 'remote'));
    document.getElementById('pwo-close').onclick = () => win.classList.add('hidden');

})();