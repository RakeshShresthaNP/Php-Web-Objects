/**
 * PWO Autonomous Support System - Strict Auth + File Integrated Edition
 * Optimized for Dynamic Token & Chunked File Uploads
 * Copyright Rakesh Shrestha
 */
(async function() {
    // 1. CONFIGURATION
    const MAX_FILE_SIZE = 25 * 1024 * 1024; // 5MB Limit
    const WS_CLIENT_PATH = 'assets/public/wsclient.js';
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
            loadScript('assets/public/tailwind.css'),
            loadScript(WS_CLIENT_PATH)
        ]);
    } catch (err) { 
        console.error("Failed to load chat dependencies");
        return; 
    }

    // 3. UI INJECTION
    const windowHtml = `
        <div id="pwo-window" class="hidden fixed bottom-24 right-6 w-80 md:w-96 h-[500px] bg-white rounded-2xl shadow-2xl z-[9999] border flex-col overflow-hidden animate-in fade-in slide-in-from-bottom-4">
            <div class="bg-emerald-600 p-4 text-white flex justify-between items-center shrink-0">
                <div>
                    <h3 class="font-bold text-sm">Live Support</h3>
                    <p class="text-[10px] opacity-80" id="pwo-status">Connecting...</p>
                </div>
                <button id="pwo-close" class="text-2xl hover:rotate-90 transition-transform">&times;</button>
            </div>
            
            <div id="pwo-progress-container" class="h-1 bg-emerald-900 hidden shrink-0">
                <div id="pwo-progress-bar" class="h-full bg-yellow-400 transition-all duration-300 w-0"></div>
            </div>

            <div id="chat-box" class="flex-1 overflow-y-auto p-4 bg-slate-50 flex flex-col gap-3 scroll-smooth relative"></div>
            
            <div class="p-4 bg-white border-t flex gap-2 items-center shrink-0">
                <input type="file" id="pwo-file-input" class="hidden" accept=".jpg,.jpeg,.png,.webp,.pdf">
                <button id="pwo-attach" class="text-gray-400 hover:text-emerald-600 transition-colors">
                    <svg viewBox="0 0 24 24" class="w-6 h-6 fill-none stroke-current stroke-2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48"/></svg>
                </button>
                <input id="chat-in" type="text" placeholder="Type a message..." class="flex-1 bg-gray-100 rounded-full px-4 py-2 text-sm outline-none focus:ring-2 focus:ring-emerald-500">
                <button id="chat-send" class="bg-emerald-600 text-white p-2 rounded-full hover:bg-emerald-700 disabled:opacity-50 transition-all">
                    <svg viewBox="0 0 24 24" class="w-5 h-5 fill-current"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
                </button>
            </div>
        </div>
        <button id="pwo-bubble" class="fixed bottom-6 right-6 w-16 h-16 bg-emerald-600 text-white rounded-full shadow-2xl flex items-center justify-center text-2xl z-[9999] hover:scale-110 active:scale-95 transition-all">💬</button>`;

    document.body.insertAdjacentHTML('beforeend', windowHtml);

    // 4. WEBSOCKET INITIALIZATION
    const ws = new WSClient(SOCKET_URL, localStorage.getItem('pwoToken') || "");
    ws.connect();

    const win = document.getElementById('pwo-window');
    const bubble = document.getElementById('pwo-bubble');
    const chatBox = document.getElementById('chat-box');
    const chatIn = document.getElementById('chat-in');
    const sendBtn = document.getElementById('chat-send');
    const attachBtn = document.getElementById('pwo-attach');
    const fileInput = document.getElementById('pwo-file-input');
    const progCont = document.getElementById('pwo-progress-container');
    const progBar = document.getElementById('pwo-progress-bar');

    let pendingFile = null;

    // 5. HELPERS
    const getActiveToken = () => {
        const t = localStorage.getItem('pwoToken');
        return (t && t !== "null" && t !== "undefined") ? t : null;
    };
    const getAuthHeaders = () => ({ 'X-Forwarded-Host': window.location.hostname });

    // 6. FILE ATTACH LOGIC
    attachBtn.onclick = () => fileInput.click();

    fileInput.onchange = () => {
        const file = fileInput.files[0];
        if (!file) return;
        if (file.size > MAX_FILE_SIZE) { 
            alert("File too large (Max 5MB)"); 
            fileInput.value = ""; 
            return; 
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            pendingFile = { data: e.target.result, name: file.name };
            chatIn.placeholder = `📎 Ready: ${file.name}`;
            chatIn.classList.add('ring-2', 'ring-emerald-500');
        };
        reader.readAsDataURL(file);
    };

    // 7. BUBBLE / HISTORY LOGIC
    bubble.onclick = () => {
        const isOpening = win.classList.contains('hidden');
        win.classList.toggle('hidden');
        win.classList.toggle('flex');
        
        if (isOpening) {
            const token = getActiveToken();
            if (token) {
                if (ws.socket && ws.socket.readyState === WebSocket.OPEN) {
                    ws.call('chat', 'history', { token: token }, getAuthHeaders());
                } else {
                    window.addEventListener('ws_connected', () => 
                        ws.call('chat', 'history', { token: token }, getAuthHeaders()), { once: true });
                }
            } else {
                chatBox.innerHTML = '';
                renderMessage({ sender: 'Bot', message: '👋 Please login to sync history.' }, 'bot');
                showLoginOverlay();
            }
        }
        setTimeout(() => chatIn.focus(), 100);
    };

    // 8. SEND LOGIC (CHUNKED SUPPORT)
    const handleSend = async () => {
        const text = chatIn.value.trim();
        const token = getActiveToken();
        
        if (!token) return showLoginOverlay();
        if (!text && !pendingFile) return;

        if (pendingFile) {
            progCont.classList.remove('hidden');
            const CHUNK_SIZE = 32 * 1024; // 32KB per chunk
            const total = Math.ceil(pendingFile.data.length / CHUNK_SIZE);
            const fileId = Date.now();

            for (let i = 0; i < total; i++) {
                const chunk = pendingFile.data.substring(i * CHUNK_SIZE, (i + 1) * CHUNK_SIZE);
                ws.call('chat', 'uploadchunk', { 
                    file_id: fileId, 
                    chunk: chunk, 
                    index: i, 
                    token: token 
                }, getAuthHeaders());

                progBar.style.width = `${Math.round(((i + 1) / total) * 100)}%`;
                // Brief pause to prevent flooding the socket buffer
                await new Promise(r => setTimeout(r, 15));
            }
            
            ws.call('chat', 'send', { 
                message: text, 
                file_id: fileId, 
                file_name: pendingFile.name, 
                token: token 
            }, getAuthHeaders());
        } else {
            ws.call('chat', 'send', { message: text, token: token }, getAuthHeaders());
        }

        // Reset UI
        chatIn.value = '';
        chatIn.placeholder = "Type a message...";
        chatIn.classList.remove('ring-2', 'ring-emerald-500');
        pendingFile = null;
        fileInput.value = '';
        setTimeout(() => { 
            progCont.classList.add('hidden'); 
            progBar.style.width = '0%'; 
        }, 1000);
    };

    sendBtn.onclick = handleSend;
    chatIn.onkeypress = (e) => { if (e.key === 'Enter') handleSend(); };

    // 9. AUTH OVERLAY
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
            try {
                const resp = await fetch('api/auth/login', { 
                    method: 'POST', 
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ username, password }) 
                });
                const result = await resp.json();
                const token = result.data?.accessToken || result.accessToken || result.token || (result.data ? result.data.token : null);

                if (token) {
                    localStorage.setItem('pwoToken', token);
                    location.reload(); 
                } else {
                    alert("Auth failed. Check credentials.");
                }
            } catch (err) { console.error("Login Error:", err); }
        };
    }

    // 10. RENDERER
	// 10. RENDERER (Updated with File Support)
	function renderMessage(data, type) {
	    if (!data) return;
	    const isMe = type === 'me';
	    const isBot = type === 'bot';
	    const msgDiv = document.createElement('div');
	    
	    const displayName = data.sender || (isMe ? "You" : "Support");
	    let bgColor = isMe ? 'bg-emerald-600 text-white' : (isBot ? 'bg-blue-600 text-white' : 'bg-white text-gray-800 border');
	    let align = isMe ? 'self-end rounded-tr-none' : 'self-start rounded-tl-none';
	    
	    // Check if there is an attachment
	    let attachmentHtml = '';
	    if (data.file_path) {
	        const isImage = /\.(jpg|jpeg|png|webp|gif)$/i.test(data.file_path);
	        if (isImage) {
	            attachmentHtml = `
	                <div class="mt-2 mb-1 overflow-hidden rounded-lg border border-black/10">
	                    <img src="${data.file_path}" class="max-w-full h-auto cursor-pointer hover:opacity-90 transition-opacity" onclick="window.open('${data.file_path}', '_blank')">
	                </div>`;
	        } else {
	            attachmentHtml = `
	                <a href="${data.file_path}" target="_blank" class="mt-2 flex items-center gap-2 p-2 rounded bg-black/5 hover:bg-black/10 transition-colors text-[11px] font-medium border border-black/5 decoration-none text-inherit">
	                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
	                    <span class="truncate">${data.file_name || 'Download Attachment'}</span>
	                </a>`;
	        }
	    }

	    msgDiv.className = `max-w-[85%] p-3 rounded-2xl text-sm shadow-sm animate-in fade-in zoom-in-95 duration-200 ${bgColor} ${align}`;
	    msgDiv.innerHTML = `
	        <p class="text-[10px] font-bold mb-1 opacity-75">${displayName}</p>
	        ${attachmentHtml}
	        ${data.message ? `<p class="leading-relaxed whitespace-pre-wrap">${data.message}</p>` : ''}
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

    window.addEventListener('ws_error', (e) => {
        if (e.detail.code === 401) {
            localStorage.removeItem('pwoToken');
            showLoginOverlay();
        }
    });

    window.addEventListener('ws_chat_history', (e) => { 
        chatBox.innerHTML = ''; 
        const history = e.detail.data || [];
        history.forEach(msg => renderMessage(msg, 'remote')); 
        chatBox.scrollTop = chatBox.scrollHeight;
    });

    window.addEventListener('ws_chat_confirmation', (e) => renderMessage(e.detail.data || e.detail, 'me'));
    window.addEventListener('ws_new_message', (e) => renderMessage(e.detail.data || e.detail, 'remote'));
    
    document.getElementById('pwo-close').onclick = () => win.classList.add('hidden');

})();